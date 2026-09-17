<?php

namespace Tests\Feature\Api\V1;

use App\Models\Device;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GprsTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Vehicle $vehicle;
    protected Device $device;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->vehicle = Vehicle::factory()->create([
            'user_id' => $this->user->id,
            'plate_number' => 'BWR-829SB',
        ]);

        $this->device = Device::factory()->create([
            'type' => Device::TYPE_TRACKER,
            'vehicle_id' => $this->vehicle->id,
            'user_id' => $this->user->id,
            'imei' => '865167042871039',
            'serial_number' => 'GG402-865167042871039',
            'status' => Device::STATUS_ACTIVE,
            'is_online' => true,
        ]);
    }

    public function test_it_receives_and_parses_inbound_gprs_position_packet(): void
    {
        // 120414,101930,A,6.428100,N,3.421900,E,42.5,140,15.0,14,100,94,0,0,00000000
        $payload = '120414,101930,A,6.428100,N,3.421900,E,42.5,140,15.0,14,100,94,0,0,00000000';
        $packet = sprintf('[3G*865167042871039*%04X*UD,%s]', strlen("UD,{$payload}"), $payload);

        $response = $this->postJson('/api/v1/gprs/packet', [
            'packet' => $packet,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.device_id', '865167042871039')
            ->assertJsonPath('data.command', 'UD');

        $this->assertDatabaseHas('device_positions', [
            'device_id' => $this->device->id,
            'vehicle_id' => $this->vehicle->id,
            'speed_kph' => 42.5,
            'heading' => 140,
        ]);
    }

    public function test_it_dispatches_gprs_command(): void
    {
        Sanctum::actingAs($this->user);

        $this->vehicle->accessGrants()->create([
            'user_id' => $this->user->id,
            'granted_by_user_id' => $this->user->id,
            'role' => 'owner',
            'can_send_commands' => true,
        ]);

        $response = $this->postJson("/api/v1/vehicles/{$this->vehicle->uuid}/gprs-command", [
            'command_type' => 'cr',
        ]);

        $response->assertOk()
            ->assertJsonPath('command.type', 'cr')
            ->assertJsonPath('command.status', 'acknowledged');
    }
}
