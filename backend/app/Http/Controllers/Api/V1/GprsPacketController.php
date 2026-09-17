<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DevicePosition;
use App\Models\Notification;
use App\Models\SecurityEvent;
use App\Services\Devices\Protocols\GprsProtocolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Inbound GPRS Packet Gateway Controller.
 *
 * Receives raw GPRS terminal messages and telemetry packets according to the
 * Shenzhen Sanjitongchuang protocol specification.
 */
class GprsPacketController extends Controller
{
    public function __construct(
        protected GprsProtocolService $protocol,
    ) {}

    /**
     * Handle inbound raw GPRS packet or simulated telemetry stream.
     */
    public function handle(Request $request): JsonResponse
    {
        $rawPacket = $request->input('packet') ?? $request->input('raw') ?? $request->getContent();

        if (empty($rawPacket) || ! is_string($rawPacket)) {
            return response()->json([
                'error' => 'Empty or invalid GPRS packet',
            ], 422);
        }

        $parsed = $this->protocol->parsePacket($rawPacket);

        if (! $parsed) {
            return response()->json([
                'error' => 'Malformed GPRS packet framing',
            ], 422);
        }

        $deviceId = $parsed['device_id'];
        $command = strtoupper($parsed['command']);
        $payload = $parsed['payload'];
        $manufacturer = $parsed['manufacturer'] ?: '3G';

        // Lookup device by IMEI or Serial Number
        $device = Device::where('imei', $deviceId)
            ->orWhere('serial_number', $deviceId)
            ->first();

        $ackPacket = match ($command) {
            'LK' => $this->protocol->buildLoginAckPacket($deviceId, $manufacturer),
            'TK' => $this->protocol->buildHeartbeatAckPacket($deviceId, $manufacturer),
            default => $this->protocol->buildPacket($deviceId, $command, $manufacturer),
        };

        $resultData = [
            'device_id' => $deviceId,
            'command' => $command,
            'ack_packet' => $ackPacket,
        ];

        // Process positioning packets: UD (normal), UD2 (blind zone), AL (alarm), CR (location poll)
        if (in_array($command, ['UD', 'UD2', 'AL', 'CR', 'POLL']) && ! empty($payload)) {
            $posData = $this->protocol->parsePositionData($payload);

            if ($posData && $device) {
                $loc = $posData['location'];
                $telemetry = $posData['telemetry'];

                // Update device last seen state
                $device->update([
                    'last_known_latitude' => $loc->latitude,
                    'last_known_longitude' => $loc->longitude,
                    'last_seen_at' => now(),
                    'is_online' => true,
                ]);

                // Create recorded position
                $position = DevicePosition::create([
                    'device_id' => $device->id,
                    'vehicle_id' => $device->vehicle_id,
                    'latitude' => $loc->latitude,
                    'longitude' => $loc->longitude,
                    'speed_kph' => $loc->speedKph,
                    'heading' => $loc->heading,
                    'altitude_m' => $loc->altitudeMetres,
                    'accuracy_m' => $loc->accuracyMetres,
                    'ignition' => $loc->ignition,
                    'moving' => $loc->moving,
                    'source' => $loc->source,
                    'recorded_at' => $loc->recordedAt,
                    'raw' => $telemetry,
                ]);

                // Process vehicle odometer increment if moving
                if ($device->vehicle && $loc->moving && $loc->speedKph > 5) {
                    $incrementKm = max(1, (int) round(($loc->speedKph * (30 / 3600))));
                    $device->vehicle->increment('odometer_km', $incrementKm);
                }

                if ($device->vehicle) {
                    \App\Http\Controllers\Api\V1\TrackingController::clearVehicleCache($device->vehicle->uuid);
                }

                // Process hardware alarms
                $alarms = $telemetry['alarms'] ?? [];
                $user = $device->user ?? $device->vehicle?->user;

                if (! empty($alarms['sos']) && $user) {
                    Notification::create([
                        'user_id' => $user->id,
                        'type' => 'security.sos_alarm',
                        'title' => '🚨 Emergency SOS Triggered',
                        'body' => "SOS panic button pressed on device {$device->label} ({$device->imei}).",
                        'data' => [
                            'device_id' => $device->uuid,
                            'latitude' => $loc->latitude,
                            'longitude' => $loc->longitude,
                        ],
                    ]);
                }

                if (! empty($alarms['out_of_fence']) && $user) {
                    Notification::create([
                        'user_id' => $user->id,
                        'type' => 'security.geofence_breach',
                        'title' => '⚠️ Geofence Perimeter Exited',
                        'body' => "Vehicle {$device->vehicle?->displayName()} has moved outside its safe geofence radius.",
                        'data' => [
                            'device_id' => $device->uuid,
                            'vehicle_id' => $device->vehicle?->uuid,
                            'latitude' => $loc->latitude,
                            'longitude' => $loc->longitude,
                        ],
                    ]);
                }

                $resultData['position'] = [
                    'id' => $position->id,
                    'latitude' => $loc->latitude,
                    'longitude' => $loc->longitude,
                    'speed_kph' => $loc->speedKph,
                    'heading' => $loc->heading,
                    'is_online' => true,
                    'recorded_at' => $loc->recordedAt->format(DATE_ATOM),
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $resultData,
        ]);
    }
}
