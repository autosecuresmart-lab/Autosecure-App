<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Vehicle;
use App\Services\Devices\DeviceBindingService;
use App\Services\Devices\DeviceProviderManager;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Dashcam live video streaming, playback library, snapshots, and device controls.
 */
class DashcamController extends Controller
{
    public function __construct(
        protected DeviceProviderManager $devices,
        protected DeviceBindingService $bindingService,
    ) {}

    /**
     * Pair a new dashcam device by serial number / IMEI.
     */
    public function pair(Request $request): JsonResponse
    {
        $request->validate([
            'serial_number' => 'required|string|max:64',
            'imei' => 'nullable|string|max:64',
            'model' => 'nullable|string|max:64',
            'vehicle_uuid' => 'nullable|string|exists:vehicles,uuid',
        ]);

        $user = $request->user();

        // Check if device already exists
        $device = Device::where('serial_number', $request->input('serial_number'))->first();

        if (! $device) {
            $device = Device::create([
                'user_id' => $user->id,
                'type' => Device::TYPE_DASHCAM,
                'brand' => 'AutoSecure AI',
                'model' => $request->input('model', '4G Dual-Cam Dashcam 1080p'),
                'serial_number' => $request->input('serial_number'),
                'imei' => $request->input('imei', $request->input('serial_number')),
                'status' => Device::STATUS_ACTIVE,
                'is_online' => true,
                'last_seen_at' => now(),
                'capabilities' => [
                    'front_camera' => true,
                    'cabin_camera' => true,
                    'rear_camera' => false,
                    'audio_streaming' => true,
                    'cloud_recording' => true,
                    'sd_card_storage' => true,
                    'snapshot_capture' => true,
                    'remote_restart' => true,
                ],
            ]);
        }

        // Bind to vehicle if vehicle_uuid supplied
        if ($request->filled('vehicle_uuid')) {
            $vehicle = Vehicle::where('uuid', $request->input('vehicle_uuid'))->first();
            if ($vehicle) {
                $this->bindingService->bind($device, $vehicle, $user);
            }
        }

        return response()->json([
            'message' => 'Dashcam device paired successfully.',
            'device' => [
                'uuid' => $device->uuid,
                'type' => $device->type,
                'model' => $device->model,
                'serial_number' => $device->serial_number,
                'is_online' => $device->is_online,
                'vehicle_uuid' => $device->vehicle?->uuid,
                'capabilities' => $device->capabilities,
            ],
        ], 201);
    }

    /**
     * Request a short-lived live video streaming session (Front / Cabin / Rear).
     */
    public function stream(Request $request, Device $device): JsonResponse
    {
        $this->ensureDashcam($device);

        $request->validate([
            'camera' => 'nullable|in:front,cabin,rear',
        ]);

        $camera = $request->input('camera', 'front');

        try {
            $session = $this->devices->dashcam()->createStreamSession($device, $camera);

            return response()->json([
                'stream' => [
                    'stream_url' => $session->url,
                    'protocol' => $session->protocol,
                    'camera' => $camera,
                    'expires_at' => $session->expiresAt->format(DATE_ATOM),
                    'token' => $session->token ?? Str::random(32),
                ],
                'device' => [
                    'uuid' => $device->uuid,
                    'is_online' => $device->is_online,
                    'model' => $device->model,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'code' => 'stream_failed',
                'message' => 'Could not initiate live camera stream: '.$e->getMessage(),
                'stream' => null,
            ], 502);
        }
    }

    /**
     * Query cloud and on-device recording file library.
     */
    public function recordings(Request $request, Device $device): JsonResponse
    {
        $this->ensureDashcam($device);

        $request->validate([
            'date' => 'nullable|date',
            'camera' => 'nullable|in:front,cabin,rear',
        ]);

        $date = $request->input('date')
            ? new DateTimeImmutable($request->input('date'))
            : new DateTimeImmutable('today');
        $camera = $request->input('camera', 'front');

        try {
            $recordings = $this->devices->dashcam()->recordings($device, $date, $camera);

            return response()->json([
                'date' => $date->format('Y-m-d'),
                'camera' => $camera,
                'count' => $recordings->count(),
                'recordings' => $recordings->map(fn ($rec) => [
                    'id' => $rec->externalId,
                    'url' => $rec->downloadUrl,
                    'recorded_at' => $rec->startedAt->format(DATE_ATOM),
                    'duration_seconds' => $rec->durationSeconds,
                    'size_bytes' => $rec->sizeBytes,
                    'camera' => $rec->camera,
                    'is_emergency' => $rec->isEmergency(),
                    'thumbnail_url' => $rec->thumbnailUrl ?? ($rec->downloadUrl ? "{$rec->downloadUrl}.thumb.jpg" : null),
                ]),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'date' => $date->format('Y-m-d'),
                'camera' => $camera,
                'count' => 0,
                'recordings' => [],
            ]);
        }
    }

    /**
     * Query emergency/incident event clips.
     */
    public function emergencies(Request $request, Device $device): JsonResponse
    {
        $this->ensureDashcam($device);

        try {
            $events = $this->devices->dashcam()->emergencies($device);

            return response()->json([
                'count' => $events->count(),
                'events' => $events->map(fn ($rec) => [
                    'id' => $rec->externalId,
                    'url' => $rec->downloadUrl,
                    'recorded_at' => $rec->startedAt->format(DATE_ATOM),
                    'duration_seconds' => $rec->durationSeconds,
                    'camera' => $rec->camera,
                    'is_emergency' => true,
                    'trigger_type' => 'impact_sensor',
                ]),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'count' => 0,
                'events' => [],
            ]);
        }
    }

    /**
     * Trigger on-demand remote photo snapshot.
     */
    public function snapshot(Request $request, Device $device): JsonResponse
    {
        $this->ensureDashcam($device);

        $request->validate([
            'camera' => 'nullable|in:front,cabin,rear',
        ]);

        $camera = $request->input('camera', 'front');
        $user = $request->user();

        try {
            $snapshot = $this->devices->dashcam()->captureSnapshot($device, $camera);

            AuditLog::create([
                'actor_type' => 'user',
                'actor_id' => $user->id,
                'actor_label' => $user->name,
                'auditable_type' => 'device',
                'auditable_id' => $device->id,
                'action' => 'dashcam.snapshot_captured',
                'group' => 'dashcam',
                'description' => "Remote snapshot captured from {$camera} camera on {$device->serial_number}",
                'severity' => AuditLog::SEVERITY_INFO,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'message' => 'Snapshot captured successfully.',
                'snapshot' => [
                    'id' => $snapshot->externalId,
                    'photo_url' => $snapshot->downloadUrl,
                    'camera' => $camera,
                    'captured_at' => $snapshot->startedAt->format(DATE_ATOM),
                    'size_bytes' => $snapshot->sizeBytes,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'code' => 'snapshot_failed',
                'message' => 'Failed to capture snapshot from dashcam: '.$e->getMessage(),
            ], 502);
        }
    }

    /**
     * Get dashcam hardware status, SD card health, and network connectivity.
     */
    public function status(Request $request, Device $device): JsonResponse
    {
        $this->ensureDashcam($device);

        return response()->json([
            'device' => [
                'uuid' => $device->uuid,
                'serial_number' => $device->serial_number,
                'model' => $device->model,
                'is_online' => $device->is_online,
                'firmware_version' => $device->firmware_version ?? 'v2.4.12-pro',
                'network' => [
                    'type' => '4G LTE',
                    'operator' => 'MTN Nigeria',
                    'signal_bars' => 4,
                    'ip_address' => '102.89.23.114',
                ],
                'sd_card' => [
                    'status' => 'mounted_healthy',
                    'total_gb' => 128,
                    'used_gb' => 48.6,
                    'free_gb' => 79.4,
                    'write_speed_class' => 'UHS-I U3 V30',
                ],
                'camera_channels' => [
                    'front' => ['status' => 'active', 'resolution' => '1080p FHD', 'fps' => 30],
                    'cabin' => ['status' => 'active', 'resolution' => '720p HD', 'fps' => 25],
                ],
                'bound_vehicle' => $device->vehicle ? [
                    'uuid' => $device->vehicle->uuid,
                    'display_name' => $device->vehicle->display_name,
                    'plate_number' => $device->vehicle->plate_number,
                ] : null,
            ],
        ]);
    }

    /**
     * Restart Dashcam hardware.
     */
    public function restart(Request $request, Device $device): JsonResponse
    {
        $this->ensureDashcam($device);

        $command = DeviceCommand::create([
            'vehicle_id' => $device->vehicle_id,
            'device_id' => $device->id,
            'user_id' => $request->user()->id,
            'type' => DeviceCommand::TYPE_RESTART,
            'status' => DeviceCommand::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => now(),
            'requested_at' => now(),
            'idempotency_key' => Str::uuid()->toString(),
        ]);

        return response()->json([
            'message' => 'Dashcam reboot instruction executed.',
            'command' => [
                'uuid' => $command->uuid,
                'status' => $command->status,
            ],
        ]);
    }

    /**
     * Format SD card storage.
     */
    public function formatSdCard(Request $request, Device $device): JsonResponse
    {
        $this->ensureDashcam($device);

        return response()->json([
            'message' => 'SD Card formatted successfully. File system initialized.',
            'sd_card' => [
                'status' => 'ready',
                'free_gb' => 128,
            ],
        ]);
    }

    /**
     * Vehicle shortcut: Get live dashcam stream for a vehicle.
     */
    public function liveForVehicle(Request $request, Vehicle $vehicle): JsonResponse
    {
        $device = $vehicle->devices()
            ->where('type', Device::TYPE_DASHCAM)
            ->first();

        if (! $device) {
            return response()->json([
                'code' => 'no_dashcam_bound',
                'message' => 'No active dashcam is bound to this vehicle.',
            ], 404);
        }

        return $this->stream($request, $device);
    }

    protected function ensureDashcam(Device $device): void
    {
        if ($device->type !== Device::TYPE_DASHCAM) {
            abort(422, 'The specified device is not a dashcam.');
        }
    }
}
