<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\DevicePosition;
use App\Models\Vehicle;
use App\Services\Devices\Protocols\GprsProtocolService;
use Illuminate\Console\Command;

class SimulateGprsTelemetryCommand extends Command
{
    protected $signature = 'autosecure:simulate-gprs
                            {--once : Run a single tick and exit}
                            {--interval=3 : Polling delay in seconds for daemon mode}
                            {--count=1 : Number of ticks to run}';

    protected $description = 'Simulate real-time GPRS telemetry packets for active GPS trackers along real Lagos roads';

    /**
     * Waypoints routes for realistic movement simulation.
     */
    protected array $routes = [
        // 1. Mercedes-Benz GLK 350 - Victoria Island route
        '865167042871039' => [
            'name' => 'Victoria Island Loop',
            'points' => [
                ['lat' => 6.42810, 'lng' => 3.42190, 'speed' => 38.5, 'heading' => 142, 'address' => 'Ahmadu Bello Way, Victoria Island, Lagos'],
                ['lat' => 6.42950, 'lng' => 3.42380, 'speed' => 41.2, 'heading' => 135, 'address' => 'Ahmadu Bello Way near Silverbird, Victoria Island, Lagos'],
                ['lat' => 6.43210, 'lng' => 3.42740, 'speed' => 44.0, 'heading' => 120, 'address' => 'Bishop Oluwole St, Victoria Island, Lagos'],
                ['lat' => 6.43540, 'lng' => 3.43120, 'speed' => 39.8, 'heading' => 110, 'address' => 'Ozumba Mbadiwe Ave, Victoria Island, Lagos'],
                ['lat' => 6.43820, 'lng' => 3.43580, 'speed' => 46.5, 'heading' => 95,  'address' => 'Ozumba Mbadiwe Ave near Civic Centre, Victoria Island, Lagos'],
                ['lat' => 6.43400, 'lng' => 3.43000, 'speed' => 42.0, 'heading' => 280, 'address' => 'Akin Adesola St, Victoria Island, Lagos'],
            ],
            'battery' => 94,
            'satellites' => 14,
            'gsm' => 100,
        ],
        // 2. Toyota Corolla - Parked at Lekki Phase 1
        '865167042873225' => [
            'name' => 'Admiralty Way Safe Zone',
            'points' => [
                ['lat' => 6.44740, 'lng' => 3.47230, 'speed' => 0.0, 'heading' => 90, 'address' => 'Admiralty Way, Lekki Phase 1, Lagos'],
            ],
            'battery' => 98,
            'satellites' => 11,
            'gsm' => 92,
        ],
        // 3. Toyota Camry - Ikeja GRA Expressway route
        '865167042870221' => [
            'name' => 'Ikeja GRA Transit',
            'points' => [
                ['lat' => 6.59640, 'lng' => 3.35150, 'speed' => 52.0, 'heading' => 210, 'address' => 'Isaac John Street, GRA Ikeja, Lagos'],
                ['lat' => 6.59320, 'lng' => 3.34900, 'speed' => 54.5, 'heading' => 205, 'address' => 'Isaac John St near Radisson Blu, GRA Ikeja, Lagos'],
                ['lat' => 6.58910, 'lng' => 3.34650, 'speed' => 50.0, 'heading' => 195, 'address' => 'Mobolaji Bank Anthony Way, Ikeja, Lagos'],
                ['lat' => 6.58400, 'lng' => 3.34400, 'speed' => 56.2, 'heading' => 180, 'address' => 'Mobolaji Bank Anthony Way near Sheraton, Ikeja, Lagos'],
                ['lat' => 6.59100, 'lng' => 3.34800, 'speed' => 48.0, 'heading' => 30,  'address' => 'Joel Ogunnaike St, GRA Ikeja, Lagos'],
            ],
            'battery' => 89,
            'satellites' => 15,
            'gsm' => 96,
        ],
    ];

    public function handle(GprsProtocolService $protocol): int
    {
        $once = $this->option('once');
        $interval = max(1, (int) $this->option('interval'));
        $targetCount = $once ? 1 : (int) $this->option('count');
        $stepIndex = cache()->get('gprs_sim_step_index', 0);

        $this->info('Starting GPRS Real-Time Telemetry Stream Simulation...');

        $activeDevices = Device::where('type', Device::TYPE_TRACKER)
            ->where('status', Device::STATUS_ACTIVE)
            ->get();

        if ($activeDevices->isEmpty()) {
            $this->warn('No active GPS tracker devices found in database.');
            return Command::SUCCESS;
        }

        $iterations = 0;
        while ($targetCount === 0 || $iterations < $targetCount) {
            $iterations++;
            $stepIndex++;
            cache()->put('gprs_sim_step_index', $stepIndex, 3600);

            $now = now();
            $dateStr = $now->format('dmy'); // DDMMYY
            $timeStr = $now->format('His'); // HHMMSS

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
                    $battery = (int) $route['battery'];
                    $satellites = (int) $route['satellites'];
                    $gsm = (int) $route['gsm'];
                } else {
                    // Any new custom or user-added device in the system
                    $baseLat = (float) ($device->last_known_latitude ?: 6.4281);
                    $baseLng = (float) ($device->last_known_longitude ?: 3.4219);
                    $offset = (($stepIndex % 10) - 5) * 0.0004;

                    $lat = $baseLat + $offset;
                    $lng = $baseLng + ($offset * 0.8);
                    $speed = $stepIndex % 4 === 0 ? 0.0 : (35.0 + ($stepIndex % 20));
                    $heading = ($stepIndex * 35) % 360;
                    $address = $device->vehicle ? "Near {$device->vehicle->displayName()} Operating Zone" : 'Live GPS Area';
                    $battery = max(20, 100 - ($stepIndex % 15));
                    $satellites = 12;
                    $gsm = 95;
                }

                $latDir = $lat >= 0 ? 'N' : 'S';
                $lngDir = $lng >= 0 ? 'E' : 'W';

                // Format Appendix 1 position payload
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

                $packet = $protocol->buildPacket($imei, 'UD,'.$payload, '3G');

                // Update device state
                $device->update([
                    'last_known_latitude' => $lat,
                    'last_known_longitude' => $lng,
                    'last_seen_at' => $now,
                    'is_online' => true,
                ]);

                // Record position
                DevicePosition::create([
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
                    $incrementKm = max(1, (int) round($speed * ($interval / 3600)));
                    $device->vehicle->increment('odometer_km', $incrementKm);
                }

                $this->line(sprintf(
                    '[%s] IMEI: %s | Pos: (%.5f, %.5f) | Speed: %.1f km/h | %s',
                    $now->toTimeString(),
                    $imei,
                    $lat,
                    $lng,
                    $speed,
                    $address
                ));
            }

            if (! $once && ($targetCount === 0 || $iterations < $targetCount)) {
                sleep($interval);
            }
        }

        $this->info('GPRS simulation tick complete.');

        return Command::SUCCESS;
    }
}
