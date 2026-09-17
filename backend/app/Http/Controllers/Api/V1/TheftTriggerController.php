<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Notification;
use App\Models\TheftEvent;
use App\Models\Vehicle;
use App\Services\Devices\DeviceProviderManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Car Theft Trigger workflow (proposal section 04).
 */
class TheftTriggerController extends Controller
{
    public function __construct(
        protected DeviceProviderManager $devices,
    ) {}

    /**
     * Activate the emergency Car Theft Trigger.
     */
    public function trigger(Request $request, Vehicle $vehicle): JsonResponse
    {
        $request->validate([
            'pin' => 'nullable|string',
            'password' => 'nullable|string',
            'biometric_verified' => 'nullable|boolean',
        ]);

        $user = $request->user();

        // Optional PIN / Password verification
        if ($request->filled('password') && ! Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'code' => 'invalid_credentials',
                'message' => 'The provided security password is incorrect.',
            ], 422);
        }

        $device = $vehicle->devices()->first();

        // Query latest known position
        $lastPosition = $vehicle->positions()->latest('recorded_at')->first();
        $lat = $lastPosition ? (float) $lastPosition->latitude : 6.4382;
        $lng = $lastPosition ? (float) $lastPosition->longitude : 3.4721;

        // Try getting fresh live fix if possible
        if ($device) {
            try {
                $fix = $this->devices->tracker()->latestLocation($device);
                $lat = $fix->latitude;
                $lng = $fix->longitude;
            } catch (\Throwable $e) {
                // Keep last known database fix
            }
        }

        // Create open TheftEvent
        $theftEvent = TheftEvent::create([
            'vehicle_id' => $vehicle->id,
            'user_id' => $user->id,
            'device_id' => $device?->id,
            'status' => TheftEvent::STATUS_OPEN,
            'severity' => 'critical',
            'pin_verified' => $request->filled('pin') || $request->filled('password'),
            'biometric_verified' => (bool) $request->input('biometric_verified', false),
            'triggered_at' => now(),
            'last_known_latitude' => $lat,
            'last_known_longitude' => $lng,
            'metadata' => [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'initial_speed' => $lastPosition?->speed_kph ?? 0,
            ],
        ]);

        // Issue high-priority theft alert command to tracker
        if ($device) {
            $cmd = DeviceCommand::create([
                'vehicle_id' => $vehicle->id,
                'device_id' => $device->id,
                'user_id' => $user->id,
                'theft_event_id' => $theftEvent->id,
                'type' => DeviceCommand::TYPE_THEFT_ALERT,
                'status' => DeviceCommand::STATUS_SENT,
                'sent_at' => now(),
                'idempotency_key' => Str::uuid()->toString(),
            ]);

            try {
                $this->devices->tracker()->sendCommand($device, $cmd);
            } catch (\Throwable $e) {
                // Command dispatch failed, theft event remains open
            }
        }

        // Create in-app high-priority notification
        Notification::create([
            'user_id' => $user->id,
            'type' => 'security.theft_alert',
            'category' => 'security',
            'title' => 'Emergency: Vehicle Theft Mode Activated',
            'body' => "Theft mode has been triggered for {$vehicle->display_name} ({$vehicle->plate_number}). Live GPS tracking and security dispatch are active.",
            'channel' => 'in_app',
            'data' => [
                'theft_event_uuid' => $theftEvent->uuid,
                'vehicle_uuid' => $vehicle->uuid,
                'plate_number' => $vehicle->plate_number,
            ],
        ]);

        // Audit Trail
        AuditLog::create([
            'actor_type' => 'user',
            'actor_id' => $user->id,
            'actor_label' => $user->name,
            'auditable_type' => 'vehicle',
            'auditable_id' => $vehicle->id,
            'action' => 'security.theft_triggered',
            'group' => 'security',
            'description' => "Emergency theft trigger activated for vehicle {$vehicle->plate_number}",
            'severity' => AuditLog::SEVERITY_CRITICAL,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'context' => [
                'theft_event_uuid' => $theftEvent->uuid,
                'last_known_lat' => $lat,
                'last_known_lng' => $lng,
            ],
        ]);

        return response()->json([
            'message' => 'Emergency theft trigger activated successfully. Rapid recovery tracking enabled.',
            'theft_event' => [
                'uuid' => $theftEvent->uuid,
                'status' => $theftEvent->status,
                'severity' => $theftEvent->severity,
                'triggered_at' => $theftEvent->triggered_at?->toIso8601String(),
                'last_known_location' => [
                    'latitude' => $lat,
                    'longitude' => $lng,
                ],
                'available_actions' => [
                    'remote_shutdown' => true,
                    'live_tracking' => true,
                    'call_vehicle' => true,
                    'police_share' => true,
                    'resolve' => true,
                ],
            ],
        ], 201);
    }

    /**
     * List theft events for a vehicle.
     */
    public function index(Request $request, Vehicle $vehicle): JsonResponse
    {
        $events = $vehicle->theftEvents()
            ->latest('triggered_at')
            ->get();

        return response()->json([
            'vehicle_uuid' => $vehicle->uuid,
            'theft_events' => $events->map(fn ($ev) => [
                'uuid' => $ev->uuid,
                'status' => $ev->status,
                'severity' => $ev->severity,
                'triggered_at' => $ev->triggered_at?->toIso8601String(),
                'resolved_at' => $ev->resolved_at?->toIso8601String(),
                'resolution_note' => $ev->resolution_note,
                'is_open' => $ev->isOpen(),
            ]),
        ]);
    }

    /**
     * Show live details and telemetry for a specific theft event.
     */
    public function show(Request $request, TheftEvent $theft_event): JsonResponse
    {
        $vehicle = $theft_event->vehicle;

        // Fetch fresh position
        $lastPos = $vehicle->positions()->latest('recorded_at')->first();

        return response()->json([
            'theft_event' => [
                'uuid' => $theft_event->uuid,
                'status' => $theft_event->status,
                'severity' => $theft_event->severity,
                'triggered_at' => $theft_event->triggered_at?->toIso8601String(),
                'resolved_at' => $theft_event->resolved_at?->toIso8601String(),
                'resolution_note' => $theft_event->resolution_note,
                'is_open' => $theft_event->isOpen(),
                'vehicle' => [
                    'uuid' => $vehicle->uuid,
                    'display_name' => $vehicle->display_name,
                    'plate_number' => $vehicle->plate_number,
                    'make' => $vehicle->make,
                    'model' => $vehicle->model,
                    'colour' => $vehicle->colour,
                ],
                'live_telemetry' => [
                    'latitude' => $lastPos ? (float) $lastPos->latitude : (float) $theft_event->last_known_latitude,
                    'longitude' => $lastPos ? (float) $lastPos->longitude : (float) $theft_event->last_known_longitude,
                    'speed_kph' => (float) ($lastPos->speed_kph ?? 0),
                    'heading' => (int) ($lastPos->heading ?? 0),
                    'ignition' => (bool) ($lastPos->ignition ?? false),
                    'recorded_at' => $lastPos?->recorded_at?->toIso8601String() ?? now()->toIso8601String(),
                ],
                'commands' => $theft_event->commands()->latest('requested_at')->get()->map(fn ($cmd) => [
                    'uuid' => $cmd->uuid,
                    'type' => $cmd->type,
                    'status' => $cmd->status,
                    'requested_at' => $cmd->requested_at?->toIso8601String(),
                    'acknowledged_at' => $cmd->acknowledged_at?->toIso8601String(),
                ]),
            ],
        ]);
    }

    /**
     * Resolve an active theft event with a resolution note.
     */
    public function resolve(Request $request, TheftEvent $theft_event): JsonResponse
    {
        $request->validate([
            'resolution_note' => 'required|string|min:5|max:1000',
            'status' => 'nullable|in:resolved,false_alarm',
        ]);

        $user = $request->user();
        $status = $request->input('status', TheftEvent::STATUS_RESOLVED);

        $theft_event->update([
            'status' => $status,
            'resolved_at' => now(),
            'resolved_by_user_id' => $user->id,
            'resolution_note' => $request->input('resolution_note'),
        ]);

        // Audit log
        AuditLog::create([
            'actor_type' => 'user',
            'actor_id' => $user->id,
            'actor_label' => $user->name,
            'auditable_type' => 'vehicle',
            'auditable_id' => $theft_event->vehicle_id,
            'action' => 'security.theft_resolved',
            'group' => 'security',
            'description' => "Theft event {$theft_event->uuid} resolved: {$request->input('resolution_note')}",
            'severity' => AuditLog::SEVERITY_NOTICE,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Theft event resolved successfully.',
            'theft_event' => [
                'uuid' => $theft_event->uuid,
                'status' => $theft_event->status,
                'resolved_at' => $theft_event->resolved_at?->toIso8601String(),
                'resolution_note' => $theft_event->resolution_note,
            ],
        ]);
    }
}
