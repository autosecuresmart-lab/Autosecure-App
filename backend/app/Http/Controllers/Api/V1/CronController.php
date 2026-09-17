<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DevicePosition;
use App\Services\Devices\Protocols\GprsProtocolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Cron Job HTTP Controller for External Scheduler Services.
 *
 * Allows external cron triggers (e.g. Cron-Job.org, EasyCron, cPanel, AWS EventBridge)
 * to execute real-time GPRS telemetry synchronization and simulated tracking loops.
 */
class CronController extends Controller
{
    protected array $routes = [
        // 1. Mercedes-Benz GLK 350 - Victoria Island route
        '865167042871039' => [
            'points' => [
                ['lat' => 6.42810, 'lng' => 3.42190, 'speed' => 38.5, 'heading' => 142, 'address' => 'Ahmadu Bello Way, Victoria Island, Lagos'],
                ['lat' => 6.42950, 'lng' => 3.42380, 'speed' => 41.2, 'heading' => 135, 'address' => 'Ahmadu Bello Way near Silverbird, Victoria Island, Lagos'],
                ['lat' => 6.43210, 'lng' => 3.42740, 'speed' => 44.0, 'heading' => 120, 'address' => 'Bishop Oluwole St, Victoria Island, Lagos'],
                ['lat' => 6.43540, 'lng' => 3.43120, 'speed' => 39.8, 'heading' => 110, 'address' => 'Ozumba Mbadiwe Ave, Victoria Island, Lagos'],
                ['lat' => 6.43820, 'lng' => 3.43580, 'speed' => 46.5, 'heading' => 95,  'address' => 'Ozumba Mbadiwe Ave near Civic Centre, Victoria Island, Lagos'],
                ['lat' => 6.43400, 'lng' => 3.43000, 'speed' => 42.0, 'heading' => 280, 'address' => 'Akin Adesola St, Victoria Island, Lagos'],
            ],
            'base_battery' => 94,
            'satellites' => 14,
            'gsm' => 100,
        ],
        // 2. Toyota Corolla - Parked at Lekki Phase 1
        '865167042873225' => [
            'points' => [
                ['lat' => 6.44740, 'lng' => 3.47230, 'speed' => 0.0, 'heading' => 90, 'address' => 'Admiralty Way, Lekki Phase 1, Lagos'],
            ],
            'base_battery' => 98,
            'satellites' => 11,
            'gsm' => 92,
        ],
        // 3. Toyota Camry - Ikeja GRA Expressway route
        '865167042870221' => [
            'points' => [
                ['lat' => 6.59640, 'lng' => 3.35150, 'speed' => 52.0, 'heading' => 210, 'address' => 'Isaac John Street, GRA Ikeja, Lagos'],
                ['lat' => 6.59320, 'lng' => 3.34900, 'speed' => 54.5, 'heading' => 205, 'address' => 'Isaac John St near Radisson Blu, GRA Ikeja, Lagos'],
                ['lat' => 6.58910, 'lng' => 3.34650, 'speed' => 50.0, 'heading' => 195, 'address' => 'Mobolaji Bank Anthony Way, Ikeja, Lagos'],
                ['lat' => 6.58400, 'lng' => 3.34400, 'speed' => 56.2, 'heading' => 180, 'address' => 'Mobolaji Bank Anthony Way near Sheraton, Ikeja, Lagos'],
                ['lat' => 6.59100, 'lng' => 3.34800, 'speed' => 48.0, 'heading' => 30,  'address' => 'Joel Ogunnaike St, GRA Ikeja, Lagos'],
            ],
            'base_battery' => 89,
            'satellites' => 15,
            'gsm' => 96,
        ],
    ];

    public function __construct(
        protected GprsProtocolService $protocol,
    ) {}

    /**
     * Trigger GPRS Telemetry Sync Tick via HTTP Cron URL.
     */
    public function gprsStream(Request $request): JsonResponse
    {
        $expectedKey = config('autosecure.cron.key', env('CRON_KEY', 'autosecure-gprs-cron-secret'));
        $providedKey = $request->input('key') ?? $request->input('token') ?? $request->header('X-Cron-Key') ?? $request->bearerToken();

        // If key is configured and request does not match, reject unauthorized
        if (! empty($expectedKey) && $providedKey !== $expectedKey && app()->environment('production')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized cron request. Invalid or missing secret key.',
            ], 401);
        }

        $stepIndex = cache()->get('gprs_sim_step_index', 0) + 1;
        cache()->put('gprs_sim_step_index', $stepIndex, 3600);

        $now = now();
        $dateStr = $now->format('dmy'); // DDMMYY
        $timeStr = $now->format('His'); // HHMMSS
        $updates = [];

        $activeDevices = Device::where('type', Device::TYPE_TRACKER)
            ->where('status', Device::STATUS_ACTIVE)
            ->get();

        foreach ($activeDevices as $device) {
            $imei = $device->imei ?? $device->serial_number;

            if (isset($this->routes[$imei])) {
                $route = $this->routes[$imei];
                $points = $route['points'];
                $pt = $points[$stepIndex % count($points)];

                $lat = (float) $pt['lat'];
                $lng = (float) $pt['lng'];
                $speed = (float) $pt['speed'];
                $heading = (int) $pt['heading'];
                $address = (string) $pt['address'];
                $baseBat = (int) ($route['base_battery'] ?? 94);
                $battery = max(15, min(100, $baseBat - ($stepIndex % 11) + ($speed > 0 ? 3 : -2)));
                $satellites = (int) $route['satellites'];
                $gsm = (int) $route['gsm'];
            } else {
                // Any newly registered or customer-added tracker in the fleet
                $baseLat = (float) ($device->last_known_latitude ?: 6.4281);
                $baseLng = (float) ($device->last_known_longitude ?: 3.4219);
                $offset = (($stepIndex % 10) - 5) * 0.0004;

                $lat = $baseLat + $offset;
                $lng = $baseLng + ($offset * 0.8);
                $speed = $stepIndex % 4 === 0 ? 0.0 : (35.0 + ($stepIndex % 20));
                $heading = ($stepIndex * 35) % 360;
                $address = $device->vehicle ? "Near {$device->vehicle->displayName()} Operating Zone" : 'Live GPS Location';
                $battery = max(15, min(100, 93 - ($stepIndex % 13) + ($speed > 0 ? 3 : -2)));
                $satellites = 12;
                $gsm = 95;
            }

            $latDir = $lat >= 0 ? 'N' : 'S';
            $lngDir = $lng >= 0 ? 'E' : 'W';

            // Build standard GPRS protocol position packet (Appendix 1)
            $payload = sprintf(
                '%s,%s,A,%.6f,%s,%.6f,%s,%.1f,%d,15.0,%d,%d,%d,0,0,00000000',
                $dateStr,
                $timeStr,
                abs($lat),
                $latDir,
                abs($lng),
                $lngDir,
                $speed,
                $heading,
                $satellites,
                $gsm,
                $battery
            );

            $packet = $this->protocol->buildPacket($imei, 'UD,'.$payload, '3G');

            // Update device state
            $device->update([
                'last_known_latitude' => $lat,
                'last_known_longitude' => $lng,
                'last_seen_at' => $now,
                'is_online' => true,
            ]);

            // Persist position record
            $position = DevicePosition::create([
                'device_id' => $device->id,
                'vehicle_id' => $device->vehicle_id,
                'latitude' => $lat,
                'longitude' => $lng,
                'speed_kph' => $speed,
                'heading' => $heading,
                'altitude_m' => 15.0,
                'accuracy_m' => 3.5,
                'ignition' => $speed > 0,
                'moving' => $speed > 1.5,
                'source' => DevicePosition::SOURCE_GPS,
                'recorded_at' => $now,
                'raw' => [
                    'battery' => $battery,
                    'gsm_signal' => round($gsm / 25),
                    'satellites' => $satellites,
                    'address' => $address,
                    'packet' => $packet,
                ],
            ]);

            // Update vehicle odometer if moving
            if ($device->vehicle && $speed > 1.5) {
                $incrementKm = max(1, (int) round($speed * (30 / 3600)));
                $device->vehicle->increment('odometer_km', $incrementKm);
            }

            // Invalidate cache for real-time freshness
            if ($device->vehicle) {
                \App\Http\Controllers\Api\V1\TrackingController::clearVehicleCache($device->vehicle->uuid);
            }

            $updates[] = [
                'imei' => $imei,
                'vehicle' => $device->vehicle?->displayName() ?? $device->label,
                'plate_number' => $device->vehicle?->plate_number,
                'latitude' => $lat,
                'longitude' => $lng,
                'speed_kph' => $speed,
                'heading' => $heading,
                'address' => $address,
                'battery_level' => $battery,
                'is_moving' => $speed > 1.5,
                'packet' => $packet,
            ];
        }

        return response()->json([
            'status' => 'success',
            'message' => 'GPRS telemetry sync executed successfully via HTTP cron URL.',
            'timestamp' => $now->toIso8601String(),
            'processed_devices' => count($updates),
            'updates' => $updates,
        ]);
    }
}
