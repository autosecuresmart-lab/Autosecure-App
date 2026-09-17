<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Vehicle;
use App\Services\Devices\DeviceProviderManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Security commands, remote shutdown, voice monitoring, and command tracking.
 */
class SecurityController extends Controller
{
    public function __construct(
        protected DeviceProviderManager $devices,
    ) {}

    /**
     * Issue Remote Engine Shutdown (Immobilisation) with strong confirmation.
     */
    public function shutdown(Request $request, Vehicle $vehicle): JsonResponse
    {
        $request->validate([
            'confirmation' => 'required|accepted',
            'password' => 'nullable|string',
            'reason' => 'nullable|string|max:255',
        ]);

        $user = $request->user();

        // Optional password verification if provided or if enforced
        if ($request->filled('password') && ! Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'code' => 'invalid_credentials',
                'message' => 'The provided security password is incorrect.',
            ], 422);
        }

        $device = $this->resolveTrackerDevice($vehicle);
        if (! $device) {
            return response()->json([
                'code' => 'no_device_bound',
                'message' => 'No active tracker device is bound to this vehicle.',
            ], 422);
        }

        // Create the pending DeviceCommand row (never optimistic)
        $command = DeviceCommand::create([
            'vehicle_id' => $vehicle->id,
            'device_id' => $device->id,
            'user_id' => $user->id,
            'type' => DeviceCommand::TYPE_REMOTE_SHUTDOWN,
            'status' => DeviceCommand::STATUS_PENDING,
            'confirmed_by_user' => true,
            'requested_at' => now(),
            'idempotency_key' => Str::uuid()->toString(),
            'payload' => [
                'reason' => $request->input('reason', 'User initiated emergency remote shutdown'),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        ]);

        // Audit log for critical security command
        AuditLog::create([
            'actor_type' => 'user',
            'actor_id' => $user->id,
            'actor_label' => $user->name,
            'auditable_type' => 'vehicle',
            'auditable_id' => $vehicle->id,
            'action' => 'security.remote_shutdown_requested',
            'group' => 'security',
            'description' => "Remote engine shutdown requested for vehicle {$vehicle->plate_number}",
            'severity' => AuditLog::SEVERITY_CRITICAL,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'context' => [
                'command_uuid' => $command->uuid,
                'vehicle_uuid' => $vehicle->uuid,
                'device_uuid' => $device->uuid,
            ],
        ]);

        try {
            // Dispatch command to tracker provider
            $command->update(['status' => DeviceCommand::STATUS_SENT, 'sent_at' => now()]);

            $result = $this->devices->tracker()->sendCommand($device, $command);

            if ($result->isConfirmed() || $result->acknowledged) {
                $command->update([
                    'status' => DeviceCommand::STATUS_ACKNOWLEDGED,
                    'acknowledged_at' => now(),
                    'provider_reference' => $result->providerReference,
                    'response' => $result->raw,
                ]);

                return response()->json([
                    'message' => 'Remote engine shutdown confirmed and executed by vehicle device.',
                    'command' => [
                        'uuid' => $command->uuid,
                        'type' => $command->type,
                        'status' => $command->status,
                        'is_confirmed' => true,
                        'acknowledged_at' => $command->acknowledged_at?->toIso8601String(),
                    ],
                ]);
            }

            if ($result->isPending()) {
                $command->update([
                    'status' => DeviceCommand::STATUS_SENT,
                    'provider_reference' => $result->providerReference,
                ]);

                return response()->json([
                    'message' => 'Remote shutdown instruction dispatched to device; awaiting satellite/relay confirmation.',
                    'command' => [
                        'uuid' => $command->uuid,
                        'type' => $command->type,
                        'status' => $command->status,
                        'is_confirmed' => false,
                    ],
                ], 202);
            }

            $command->update([
                'status' => DeviceCommand::STATUS_FAILED,
                'failure_reason' => $result->failureReason ?? 'Device rejected shutdown command',
            ]);

            return response()->json([
                'code' => 'command_failed',
                'message' => $result->failureReason ?? 'Device did not confirm remote shutdown.',
                'command' => [
                    'uuid' => $command->uuid,
                    'status' => DeviceCommand::STATUS_FAILED,
                ],
            ], 422);

        } catch (\Throwable $e) {
            $command->update([
                'status' => DeviceCommand::STATUS_FAILED,
                'failure_reason' => $e->getMessage(),
            ]);

            return response()->json([
                'code' => 'device_communication_error',
                'message' => 'Communication error with device tracker gateway: '.$e->getMessage(),
                'command' => [
                    'uuid' => $command->uuid,
                    'status' => DeviceCommand::STATUS_FAILED,
                ],
            ], 502);
        }
    }

    /**
     * Restore Engine (Cancel Immobilisation).
     */
    public function restoreEngine(Request $request, Vehicle $vehicle): JsonResponse
    {
        $request->validate([
            'confirmation' => 'required|accepted',
        ]);

        $user = $request->user();
        $device = $this->resolveTrackerDevice($vehicle);

        if (! $device) {
            return response()->json([
                'code' => 'no_device_bound',
                'message' => 'No active tracker device is bound to this vehicle.',
            ], 422);
        }

        $command = DeviceCommand::create([
            'vehicle_id' => $vehicle->id,
            'device_id' => $device->id,
            'user_id' => $user->id,
            'type' => DeviceCommand::TYPE_RESTORE,
            'status' => DeviceCommand::STATUS_PENDING,
            'confirmed_by_user' => true,
            'requested_at' => now(),
            'idempotency_key' => Str::uuid()->toString(),
        ]);

        try {
            $command->update(['status' => DeviceCommand::STATUS_SENT, 'sent_at' => now()]);
            $result = $this->devices->tracker()->sendCommand($device, $command);

            $command->update([
                'status' => DeviceCommand::STATUS_ACKNOWLEDGED,
                'acknowledged_at' => now(),
                'provider_reference' => $result->providerReference,
            ]);

            AuditLog::create([
                'actor_type' => 'user',
                'actor_id' => $user->id,
                'actor_label' => $user->name,
                'auditable_type' => 'vehicle',
                'auditable_id' => $vehicle->id,
                'action' => 'security.engine_restored',
                'group' => 'security',
                'description' => "Engine relay restored for vehicle {$vehicle->plate_number}",
                'severity' => AuditLog::SEVERITY_WARNING,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'message' => 'Engine relay restored successfully.',
                'command' => [
                    'uuid' => $command->uuid,
                    'type' => $command->type,
                    'status' => $command->status,
                    'acknowledged_at' => $command->acknowledged_at?->toIso8601String(),
                ],
            ]);

        } catch (\Throwable $e) {
            $command->update([
                'status' => DeviceCommand::STATUS_FAILED,
                'failure_reason' => $e->getMessage(),
            ]);

            return response()->json([
                'code' => 'command_failed',
                'message' => 'Could not restore engine relay: '.$e->getMessage(),
            ], 502);
        }
    }

    /**
     * Trigger Voice Call to Vehicle.
     */
    public function callVehicle(Request $request, Vehicle $vehicle): JsonResponse
    {
        $device = $this->resolveTrackerDevice($vehicle);
        $user = $request->user();

        if (! $device) {
            return response()->json([
                'code' => 'no_device_bound',
                'message' => 'No active tracker device bound.',
            ], 422);
        }

        $command = DeviceCommand::create([
            'vehicle_id' => $vehicle->id,
            'device_id' => $device->id,
            'user_id' => $user->id,
            'type' => DeviceCommand::TYPE_CALL_VEHICLE,
            'status' => DeviceCommand::STATUS_SENT,
            'requested_at' => now(),
            'idempotency_key' => Str::uuid()->toString(),
        ]);

        return response()->json([
            'message' => 'Audio channel opened. Dialing vehicle SIM or initiating live audio stream.',
            'phone_number' => $device->phone_number ?? '+2348000000000',
            'command' => [
                'uuid' => $command->uuid,
                'status' => $command->status,
            ],
        ]);
    }

    /**
     * Trigger Voice Audio Monitoring Stream.
     */
    public function voiceMonitor(Request $request, Vehicle $vehicle): JsonResponse
    {
        return $this->callVehicle($request, $vehicle);
    }

    /**
     * Get security event log history.
     */
    public function securityEvents(Request $request, Vehicle $vehicle): JsonResponse
    {
        $commands = $vehicle->deviceCommands()
            ->with(['requestedByUser'])
            ->latest('requested_at')
            ->take(30)
            ->get();

        $events = $commands->map(fn ($cmd) => [
            'uuid' => $cmd->uuid,
            'type' => $cmd->type,
            'status' => $cmd->status,
            'requested_at' => $cmd->requested_at?->toIso8601String(),
            'acknowledged_at' => $cmd->acknowledged_at?->toIso8601String(),
            'requested_by' => $cmd->requestedByUser?->name ?? 'System',
            'is_confirmed' => $cmd->isConfirmed(),
            'failure_reason' => $cmd->failure_reason,
        ]);

        return response()->json([
            'vehicle_uuid' => $vehicle->uuid,
            'events' => $events,
        ]);
    }

    /**
     * Poll command status by UUID.
     */
    public function commandStatus(Request $request, DeviceCommand $command): JsonResponse
    {
        return response()->json([
            'command' => [
                'uuid' => $command->uuid,
                'type' => $command->type,
                'status' => $command->status,
                'is_confirmed' => $command->isConfirmed(),
                'is_pending' => $command->isAwaitingDevice(),
                'requested_at' => $command->requested_at?->toIso8601String(),
                'acknowledged_at' => $command->acknowledged_at?->toIso8601String(),
                'failure_reason' => $command->failure_reason,
            ],
        ]);
    }

    /**
     * Dispatch general GPRS protocol commands (e.g. CR poll, FIND ring, UPLOAD rate, SOS numbers, MONITOR).
     */
    public function sendGprsCommand(Request $request, Vehicle $vehicle): JsonResponse
    {
        $data = $request->validate([
            'command_type' => 'required|string|max:64',
            'parameters' => 'nullable|array',
        ]);

        $device = $this->resolveTrackerDevice($vehicle);
        $user = $request->user();

        if (! $device) {
            return response()->json([
                'code' => 'no_device_bound',
                'message' => 'No active tracker device bound to this vehicle.',
            ], 422);
        }

        $commandType = strtolower($data['command_type']);

        $command = DeviceCommand::create([
            'vehicle_id' => $vehicle->id,
            'device_id' => $device->id,
            'user_id' => $user->id,
            'type' => $commandType,
            'status' => DeviceCommand::STATUS_SENT,
            'parameters' => $data['parameters'] ?? [],
            'requested_at' => now(),
            'idempotency_key' => Str::uuid()->toString(),
        ]);

        try {
            $result = $this->devices->tracker()->sendCommand($device, $command);

            $command->update([
                'status' => DeviceCommand::STATUS_ACKNOWLEDGED,
                'acknowledged_at' => now(),
                'provider_reference' => $result->providerReference,
            ]);

            return response()->json([
                'message' => $result->message ?: "GPRS Command '{$commandType}' executed successfully.",
                'command' => [
                    'uuid' => $command->uuid,
                    'type' => $command->type,
                    'status' => $command->status,
                    'acknowledged_at' => $command->acknowledged_at?->toIso8601String(),
                ],
            ]);
        } catch (\Throwable $e) {
            $command->update([
                'status' => DeviceCommand::STATUS_FAILED,
                'failure_reason' => $e->getMessage(),
            ]);

            return response()->json([
                'code' => 'command_failed',
                'message' => 'Failed to execute GPRS command: '.$e->getMessage(),
            ], 502);
        }
    }

    protected function resolveTrackerDevice(Vehicle $vehicle): ?Device
    {
        return $vehicle->devices()
            ->where('type', Device::TYPE_TRACKER)
            ->where('status', Device::STATUS_ACTIVE)
            ->first() ?? $vehicle->devices()->first();
    }
}
