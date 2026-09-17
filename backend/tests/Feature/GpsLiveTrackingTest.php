<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GpsLiveTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_gps_location_and_telemetry_api_works_for_real_devices(): void
    {
        $this->seed(\Database\Seeders\RealGpsDevicesSeeder::class);

        $user = User::where('email', 'client@autosecure.ng')->first();
        $this->assertNotNull($user);
        Sanctum::actingAs($user);


        $vehicle = Vehicle::where('user_id', $user->id)->first();
        $this->assertNotNull($vehicle, 'Vehicle should exist');

        // 1. Test GPS Live Location endpoint
        $locResponse = $this->getJson("/api/v1/vehicles/{$vehicle->uuid}/location");
        $locResponse->assertStatus(200);
        $locResponse->assertJsonStructure([
            'location' => [
                'latitude',
                'longitude',
                'speed_kph',
                'heading',
                'altitude_m',
                'accuracy_m',
                'ignition',
                'moving',
                'source',
                'recorded_at',
            ],
            'device' => [
                'uuid',
                'type',
                'imei',
                'model',
                'status',
            ],
        ]);

        $this->assertEquals($vehicle->devices()->where('type', 'tracker')->first()->imei, $locResponse->json('device.imei'));

        // 2. Test Telemetry Status endpoint
        $statusResponse = $this->getJson("/api/v1/vehicles/{$vehicle->uuid}/status");
        $statusResponse->assertStatus(200);
        $statusResponse->assertJsonStructure([
            'status' => [
                'is_online',
                'battery_percentage',
                'voltage',
                'ignition_on',
                'relay_cut',
                'gsm_signal',
                'satellites',
                'last_heartbeat',
            ],
            'device' => [
                'uuid',
                'serial_number',
                'type',
            ],
        ]);


        // 3. Test Route Playback endpoint
        $playbackResponse = $this->getJson("/api/v1/vehicles/{$vehicle->uuid}/playback");
        $playbackResponse->assertStatus(200);
        $playbackResponse->assertJsonStructure([
            'from',
            'to',
            'count',
            'points',
        ]);
        $this->assertGreaterThanOrEqual(1, $playbackResponse->json('count'));

        // 4. Test Remote Security Shutdown Command
        $shutdownResponse = $this->postJson("/api/v1/vehicles/{$vehicle->uuid}/shutdown", [
            'confirmation' => true,
            'reason' => 'Testing engine cutoff protocol',
        ]);
        $shutdownResponse->assertStatus(200);
        $shutdownResponse->assertJsonStructure([
            'message',
            'command' => [
                'uuid',
                'type',
                'status',
            ],
        ]);
        $this->assertEquals('remote_shutdown', $shutdownResponse->json('command.type'));

        // 5. Test Remote Restore Engine Command
        $restoreResponse = $this->postJson("/api/v1/vehicles/{$vehicle->uuid}/restore-engine", [
            'confirmation' => true,
        ]);
        $restoreResponse->assertStatus(200);
        $restoreResponse->assertJsonStructure([
            'message',
            'command' => [
                'uuid',
                'type',
                'status',
            ],
        ]);
        $this->assertEquals('restore', $restoreResponse->json('command.type'));
    }
}
