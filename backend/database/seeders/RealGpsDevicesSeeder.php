<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\DevicePosition;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RealGpsDevicesSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create or retrieve a primary customer user
        $user = User::firstOrCreate(
            ['email' => 'client@autosecure.ng'],
            [
                'name' => 'Dr. Babatunde Adeleke',
                'phone' => '+234 916 986 0996',
                'password' => Hash::make('123456789'),
                'account_type' => 'individual',
                'status' => 'active',
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
            ]
        );

        // 2. Define the 3 GPS trackers from the spreadsheet
        $devicesData = [
            [
                'vehicle' => [
                    'make' => 'Mercedes-Benz',
                    'model' => 'GLK 350',
                    'plate_number' => 'BWR-829SB',
                    'year' => 2015,
                    'colour' => 'Obsidian Black',
                    'odometer_km' => 84200,
                    'status' => 'active',
                ],
                'device' => [
                    'type' => Device::TYPE_TRACKER,
                    'model' => 'GG402',
                    'brand' => 'Shenzhen Sanjitongchuang',
                    'imei' => '865167042871039',
                    'serial_number' => 'GG402-865167042871039',
                    'sim_number' => '0700079956',
                    'phone_number' => '09169860996',
                    'label' => 'Mercedes GLK Primary Tracker',
                    'status' => Device::STATUS_ACTIVE,
                    'is_online' => true,
                    'last_known_latitude' => 6.4281000,
                    'last_known_longitude' => 3.4219000,
                    'last_seen_at' => now(),
                ],
                'telemetry' => [
                    'latitude' => 6.4281000,
                    'longitude' => 3.4219000,
                    'speed_kph' => 38.5,
                    'heading' => 142,
                    'altitude_m' => 12.0,
                    'ignition' => true,
                    'moving' => true,
                    'source' => 'gps',
                    'address' => 'Ahmadu Bello Way, Victoria Island, Lagos',
                ],
            ],
            [
                'vehicle' => [
                    'make' => 'Toyota',
                    'model' => 'Corolla',
                    'plate_number' => 'GWA228CM',
                    'year' => 2018,
                    'colour' => 'Silver Metallic',
                    'odometer_km' => 62150,
                    'status' => 'active',
                ],
                'device' => [
                    'type' => Device::TYPE_TRACKER,
                    'model' => 'GG402',
                    'brand' => 'Shenzhen Sanjitongchuang',
                    'imei' => '865167042873225',
                    'serial_number' => 'GG402-865167042873225',
                    'sim_number' => '07079596284',
                    'phone_number' => '+234 916 986 0996',
                    'label' => 'Corolla Security Tracker',
                    'status' => Device::STATUS_ACTIVE,
                    'is_online' => true,
                    'last_known_latitude' => 6.4474000,
                    'last_known_longitude' => 3.4723000,
                    'last_seen_at' => now(),
                ],
                'telemetry' => [
                    'latitude' => 6.4474000,
                    'longitude' => 3.4723000,
                    'speed_kph' => 0.0,
                    'heading' => 90,
                    'altitude_m' => 8.0,
                    'ignition' => false,
                    'moving' => false,
                    'source' => 'gps',
                    'address' => 'Admiralty Way, Lekki Phase 1, Lagos',
                ],
            ],
            [
                'vehicle' => [
                    'make' => 'Toyota',
                    'model' => 'Camry Muscle',
                    'plate_number' => 'KJA-982XY',
                    'year' => 2008,
                    'colour' => 'Dark Grey',
                    'odometer_km' => 148900,
                    'status' => 'active',
                ],
                'device' => [
                    'type' => Device::TYPE_TRACKER,
                    'model' => 'GG402',
                    'brand' => 'Shenzhen Sanjitongchuang',
                    'imei' => '865167042870221',
                    'serial_number' => 'GG402-865167042870221',
                    'sim_number' => '08102553631',
                    'phone_number' => '+234 916 986 0996',
                    'label' => 'Camry Muscle Tracker',
                    'status' => Device::STATUS_ACTIVE,
                    'is_online' => true,
                    'last_known_latitude' => 6.5964000,
                    'last_known_longitude' => 3.3515000,
                    'last_seen_at' => now(),
                ],
                'telemetry' => [
                    'latitude' => 6.5964000,
                    'longitude' => 3.3515000,
                    'speed_kph' => 52.0,
                    'heading' => 210,
                    'altitude_m' => 24.0,
                    'ignition' => true,
                    'moving' => true,
                    'source' => 'gps',
                    'address' => 'Isaac John Street, GRA Ikeja, Lagos',
                ],
            ],
        ];

        foreach ($devicesData as $data) {
            // Check or create vehicle
            $vehicle = Vehicle::firstOrCreate(
                [
                    'plate_number' => $data['vehicle']['plate_number'],
                    'user_id' => $user->id,
                ],
                $data['vehicle']
            );

            // Check or create device
            $device = Device::firstOrNew(['imei' => $data['device']['imei']]);
            $device->fill($data['device']);
            $device->user_id = $user->id;
            $device->vehicle_id = $vehicle->id;
            $device->bound_at = now();
            $device->save();

            // Record initial GPS telemetry fix
            DevicePosition::create([
                'device_id' => $device->id,
                'vehicle_id' => $vehicle->id,
                'latitude' => $data['telemetry']['latitude'],
                'longitude' => $data['telemetry']['longitude'],
                'speed_kph' => $data['telemetry']['speed_kph'],
                'heading' => $data['telemetry']['heading'],
                'altitude_m' => $data['telemetry']['altitude_m'],
                'accuracy_m' => 2.5,
                'ignition' => $data['telemetry']['ignition'],
                'moving' => $data['telemetry']['moving'],
                'source' => $data['telemetry']['source'],
                'recorded_at' => now(),
            ]);

            // Add 3 historical breadcrumb positions for route playback
            for ($i = 3; $i >= 1; $i--) {
                DevicePosition::create([
                    'device_id' => $device->id,
                    'vehicle_id' => $vehicle->id,
                    'latitude' => $data['telemetry']['latitude'] - ($i * 0.0025),
                    'longitude' => $data['telemetry']['longitude'] - ($i * 0.0020),
                    'speed_kph' => max(15.0, $data['telemetry']['speed_kph'] - ($i * 5)),
                    'heading' => $data['telemetry']['heading'],
                    'altitude_m' => $data['telemetry']['altitude_m'],
                    'accuracy_m' => 3.0,
                    'ignition' => true,
                    'moving' => true,
                    'source' => 'gps',
                    'recorded_at' => now()->subMinutes($i * 5),
                ]);
            }
        }
    }
}
