<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\DevicePosition;
use App\Models\FuelRecord;
use App\Models\MaintenanceRecord;
use App\Models\MaintenanceReminder;
use App\Models\Notification;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo Data Seeder.
 *
 * Populates realistic customer accounts, vehicles, and bound hardware devices
 * (GPS Trackers & Dual Dashcams) with live telemetry and care records.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Customer User (Iyanu Adebayo)
        $user = User::firstOrCreate(
            ['email' => 'user@autosecure.ng'],
            [
                'name' => 'Iyanu Adebayo',
                'phone' => '+2348012345678',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Alias account for iyanu@autosecure.ng
        User::firstOrCreate(
            ['email' => 'iyanu@autosecure.ng'],
            [
                'name' => 'Iyanu Adebayo',
                'phone' => '+2348087654321',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // 2. Vehicle 1: Toyota Corolla (Primary)
        $corolla = Vehicle::firstOrCreate(
            ['plate_number' => 'ABC-123DE'],
            [
                'user_id' => $user->id,
                'nickname' => 'Daily Commute',
                'make' => 'Toyota',
                'model' => 'Corolla',
                'year' => 2024,
                'colour' => 'White',
                'vin' => '1HGCR2F83HA001234',
                'fuel_type' => 'petrol',
                'transmission' => 'automatic',
                'odometer_km' => 45200,
                'odometer_source' => 'tracker',
                'odometer_updated_at' => now(),
                'is_primary' => true,
                'status' => 'active',
                'autodoc_vehicle_ref' => 'DOC-VEH-001',
            ]
        );

        $corolla->accessGrants()->firstOrCreate(
            ['user_id' => $user->id, 'role' => 'owner'],
            [
                'granted_by_user_id' => $user->id,
                'can_view_location' => true,
                'can_view_video' => true,
                'can_send_commands' => true,
                'can_manage_care_records' => true,
            ]
        );

        // Devices for Corolla
        $corollaTracker = Device::firstOrCreate(
            ['serial_number' => 'TRK-418-0091'],
            [
                'type' => Device::TYPE_TRACKER,
                'vehicle_id' => $corolla->id,
                'user_id' => $user->id,
                'provider' => 'topshine',
                'label' => '4G Real-time GPS Tracker',
                'brand' => 'Topshine',
                'model' => 'TK418 4G',
                'imei' => '867530901234567',
                'sim_number' => '8923401234567890123',
                'phone_number' => '+2348099887766',
                'firmware_version' => 'v3.4.12',
                'status' => Device::STATUS_ACTIVE,
                'is_online' => true,
                'last_seen_at' => now(),
                'last_known_latitude' => 6.5244,
                'last_known_longitude' => 3.3792,
                'bound_at' => now()->subMonths(6),
                'capabilities' => ['remote_shutdown', 'voice_monitor', 'sos_alert', 'geofence'],
            ]
        );

        $corollaDashcam = Device::firstOrCreate(
            ['serial_number' => 'DC-JC400-8821'],
            [
                'type' => Device::TYPE_DASHCAM,
                'vehicle_id' => $corolla->id,
                'user_id' => $user->id,
                'provider' => 'jimi',
                'label' => '4G Dual HD Dashcam',
                'brand' => 'Jimi IoT',
                'model' => 'JC400 Dual Cam',
                'imei' => '867530902233445',
                'firmware_version' => 'v2.1.8',
                'status' => Device::STATUS_ACTIVE,
                'is_online' => true,
                'last_seen_at' => now(),
                'last_known_latitude' => 6.5244,
                'last_known_longitude' => 3.3792,
                'bound_at' => now()->subMonths(6),
                'capabilities' => ['live_stream', 'cabin_cam', 'front_cam', 'emergency_video', 'snapshot'],
            ]
        );

        // 3. Vehicle 2: Toyota Camry
        $camry = Vehicle::firstOrCreate(
            ['plate_number' => 'KJA-892XY'],
            [
                'user_id' => $user->id,
                'nickname' => 'Family Cruiser',
                'make' => 'Toyota',
                'model' => 'Camry',
                'year' => 2023,
                'colour' => 'Black',
                'vin' => '4T1BF1FK8JU129845',
                'fuel_type' => 'petrol',
                'transmission' => 'automatic',
                'odometer_km' => 62800,
                'odometer_source' => 'tracker',
                'odometer_updated_at' => now(),
                'is_primary' => false,
                'status' => 'active',
                'autodoc_vehicle_ref' => 'DOC-VEH-002',
            ]
        );

        $camry->accessGrants()->firstOrCreate(
            ['user_id' => $user->id, 'role' => 'owner'],
            [
                'granted_by_user_id' => $user->id,
                'can_view_location' => true,
                'can_view_video' => true,
                'can_send_commands' => true,
                'can_manage_care_records' => true,
            ]
        );

        Device::firstOrCreate(
            ['serial_number' => 'TRK-103-5542'],
            [
                'type' => Device::TYPE_TRACKER,
                'vehicle_id' => $camry->id,
                'user_id' => $user->id,
                'provider' => 'topshine',
                'label' => '4G GPS Tracker',
                'brand' => 'Coban',
                'model' => 'TK103B 4G',
                'imei' => '867530909876543',
                'firmware_version' => 'v1.9.4',
                'status' => Device::STATUS_ACTIVE,
                'is_online' => true,
                'last_seen_at' => now(),
                'last_known_latitude' => 6.4540,
                'last_known_longitude' => 3.4246,
                'bound_at' => now()->subMonths(3),
                'capabilities' => ['remote_shutdown', 'geofence'],
            ]
        );

        // 4. Vehicle 3: Lexus RX 350
        $lexus = Vehicle::firstOrCreate(
            ['plate_number' => 'LND-456TZ'],
            [
                'user_id' => $user->id,
                'nickname' => 'Executive SUV',
                'make' => 'Lexus',
                'model' => 'RX 350',
                'year' => 2022,
                'colour' => 'Silver',
                'vin' => '2T2HZMCA4KC098123',
                'fuel_type' => 'petrol',
                'transmission' => 'automatic',
                'odometer_km' => 78500,
                'odometer_source' => 'manual',
                'odometer_updated_at' => now()->subDays(3),
                'is_primary' => false,
                'status' => 'active',
                'autodoc_vehicle_ref' => 'DOC-VEH-003',
            ]
        );

        $lexus->accessGrants()->firstOrCreate(
            ['user_id' => $user->id, 'role' => 'owner'],
            [
                'granted_by_user_id' => $user->id,
                'can_view_location' => true,
                'can_view_video' => false,
                'can_send_commands' => true,
                'can_manage_care_records' => true,
            ]
        );

        Device::firstOrCreate(
            ['serial_number' => 'TRK-GT06-3391'],
            [
                'type' => Device::TYPE_TRACKER,
                'vehicle_id' => $lexus->id,
                'user_id' => $user->id,
                'provider' => 'topshine',
                'label' => 'Standard GPS Tracker',
                'brand' => 'Concox',
                'model' => 'GT06N',
                'imei' => '867530904455667',
                'firmware_version' => 'v1.2.0',
                'status' => Device::STATUS_OFFLINE,
                'is_online' => false,
                'last_seen_at' => now()->subDays(2),
                'last_known_latitude' => 6.6018,
                'last_known_longitude' => 3.3515,
                'bound_at' => now()->subMonths(8),
                'capabilities' => ['remote_shutdown', 'geofence'],
            ]
        );

        // 5. Seed Maintenance Reminders
        MaintenanceReminder::firstOrCreate(
            ['vehicle_id' => $corolla->id, 'title' => 'Engine Oil & Filter Change'],
            [
                'user_id' => $user->id,
                'category' => 'oil_change',
                'due_at' => now()->addDays(14)->toDateString(),
                'due_odometer_km' => 48000,
                'status' => MaintenanceReminder::STATUS_PENDING,
            ]
        );

        MaintenanceReminder::firstOrCreate(
            ['vehicle_id' => $corolla->id, 'title' => 'Brake Pad Inspection'],
            [
                'user_id' => $user->id,
                'category' => 'brakes',
                'due_at' => now()->addDays(30)->toDateString(),
                'due_odometer_km' => 50000,
                'status' => MaintenanceReminder::STATUS_PENDING,
            ]
        );

        // 6. Seed Fuel Records
        FuelRecord::firstOrCreate(
            ['vehicle_id' => $corolla->id, 'odometer_km' => 44950],
            [
                'user_id' => $user->id,
                'filled_at' => now()->subDays(4)->toDateString(),
                'litres' => 42.50,
                'price_per_litre' => 680.00,
                'total_amount' => 28900.00,
                'is_full_tank' => true,
                'station' => 'TotalEnergies Lekki',
            ]
        );

        // 7. Seed Notifications
        Notification::firstOrCreate(
            ['user_id' => $user->id, 'title' => 'Geofence Exit Alert'],
            [
                'type' => 'alert',
                'category' => 'security',
                'body' => 'Toyota Corolla (ABC-123DE) exited the designated Lekki Phase 1 perimeter.',
                'channel' => Notification::CHANNEL_IN_APP,
                'status' => Notification::STATUS_DELIVERED,
                'data' => ['vehicle_id' => $corolla->id, 'vehicle_name' => 'Toyota Corolla'],
                'sent_at' => now()->subHours(2),
                'delivered_at' => now()->subHours(2),
            ]
        );

        Notification::firstOrCreate(
            ['user_id' => $user->id, 'title' => 'Vehicle Online'],
            [
                'type' => 'online',
                'category' => 'device',
                'body' => 'Toyota Camry (KJA-892XY) tracker connected to 4G LTE network.',
                'channel' => Notification::CHANNEL_IN_APP,
                'status' => Notification::STATUS_DELIVERED,
                'data' => ['vehicle_id' => $camry->id, 'vehicle_name' => 'Toyota Camry'],
                'sent_at' => now()->subHours(5),
                'delivered_at' => now()->subHours(5),
            ]
        );
    }
}
