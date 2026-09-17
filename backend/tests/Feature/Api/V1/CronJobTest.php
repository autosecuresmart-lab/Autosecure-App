<?php

namespace Tests\Feature\Api\V1;

use App\Models\Device;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CronJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_executes_gprs_telemetry_stream_via_cron_url(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['user_id' => $user->id]);

        Device::factory()->create([
            'type' => Device::TYPE_TRACKER,
            'vehicle_id' => $vehicle->id,
            'user_id' => $user->id,
            'imei' => '865167042871039',
            'status' => Device::STATUS_ACTIVE,
            'is_online' => true,
        ]);

        $response = $this->getJson('/api/v1/cron/gprs-stream?key=autosecure-gprs-cron-secret');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('processed_devices', 1);

        $this->assertDatabaseHas('device_positions', [
            'vehicle_id' => $vehicle->id,
        ]);
    }

    public function test_it_supports_web_cron_url_endpoint(): void
    {
        $response = $this->getJson('/cron/gprs-stream?key=autosecure-gprs-cron-secret');

        $response->assertOk()
            ->assertJsonPath('status', 'success');
    }
}
