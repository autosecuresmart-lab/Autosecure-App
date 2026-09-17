<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use App\Models\DevicePosition;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_get_live_location_for_their_vehicle(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user, 'user')->create();
        $device = Device::factory()->create([
            'vehicle_id' => $vehicle->id,
            'type' => Device::TYPE_TRACKER,
            'status' => Device::STATUS_ACTIVE,
        ]);

        DevicePosition::create([
            'device_id' => $device->id,
            'vehicle_id' => $vehicle->id,
            'latitude' => 6.4382,
            'longitude' => 3.4721,
            'speed_kph' => 45.5,
            'heading' => 180,
            'ignition' => true,
            'moving' => true,
            'source' => DevicePosition::SOURCE_GPS,
            'recorded_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/vehicles/{$vehicle->uuid}/location");

        $response->assertOk()
            ->assertJsonStructure([
                'location' => [
                    'latitude',
                    'longitude',
                    'speed_kph',
                    'heading',
                    'ignition',
                    'moving',
                    'source',
                    'recorded_at',
                ],
            ]);
    }

    public function test_a_user_cannot_access_live_location_of_another_users_vehicle(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $vehicle = Vehicle::factory()->for($owner, 'user')->create();

        $response = $this->actingAs($intruder)
            ->getJson("/api/v1/vehicles/{$vehicle->uuid}/location");

        $response->assertForbidden();
    }

    public function test_an_authenticated_user_can_retrieve_vehicle_status_telemetry(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user, 'user')->create();
        Device::factory()->create([
            'vehicle_id' => $vehicle->id,
            'type' => Device::TYPE_TRACKER,
            'status' => Device::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/vehicles/{$vehicle->uuid}/status");

        $response->assertOk()
            ->assertJsonStructure([
                'status' => [
                    'is_online',
                    'battery_percentage',
                    'ignition_on',
                    'gsm_signal',
                ],
            ]);
    }

    public function test_an_authenticated_user_can_retrieve_route_playback_breadcrumbs(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user, 'user')->create();
        $device = Device::factory()->create([
            'vehicle_id' => $vehicle->id,
            'type' => Device::TYPE_TRACKER,
        ]);

        DevicePosition::create([
            'device_id' => $device->id,
            'vehicle_id' => $vehicle->id,
            'latitude' => 6.4380,
            'longitude' => 3.4720,
            'speed_kph' => 30.0,
            'heading' => 90,
            'ignition' => true,
            'moving' => true,
            'source' => DevicePosition::SOURCE_GPS,
            'recorded_at' => now()->subHours(2),
        ]);

        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response([
                'status' => 0,
                'items' => [
                    [
                        'lat' => 6.4380,
                        'lng' => 3.4720,
                        'speed' => 30.0,
                        'course' => 90,
                        'acc' => 1,
                        'gps_time' => now()->subHours(2)->format('Y-m-d H:i:s'),
                    ],
                ],
            ], 200),
        ]);

        $from = urlencode(now()->subHours(5)->toIso8601String());
        $to = urlencode(now()->toIso8601String());

        $response = $this->actingAs($user)
            ->getJson("/api/v1/vehicles/{$vehicle->uuid}/playback?from={$from}&to={$to}");

        $response->assertOk()
            ->assertJsonStructure([
                'from',
                'to',
                'count',
                'points',
            ]);
    }
}
