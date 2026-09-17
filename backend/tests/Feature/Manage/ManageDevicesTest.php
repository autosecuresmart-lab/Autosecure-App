<?php

namespace Tests\Feature\Manage;

use App\Models\Admin;
use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManageDevicesTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create([
            'email' => 'admin@autosecure.ng',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_devices_index_page(): void
    {
        Device::factory()->create([
            'imei' => '865167042871039',
            'model' => 'GG402',
            'type' => 'tracker',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('manage.devices.index'));

        $response->assertOk();
        $response->assertSee('865167042871039');
        $response->assertSee('GG402');
    }

    public function test_admin_can_register_new_device(): void
    {
        $payload = [
            'imei' => '865167042879999',
            'type' => 'tracker',
            'model' => 'GG402',
            'brand' => 'AUTOSECURE',
            'label' => 'Toyota Tracker 99',
            'sim_number' => '08012345678',
            'phone_number' => '09012345678',
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('manage.devices.store'), $payload);

        $device = Device::where('imei', '865167042879999')->first();
        $this->assertNotNull($device);

        $response->assertRedirect(route('manage.devices.show', $device));
    }

    public function test_admin_can_view_device_show_page(): void
    {
        $user = User::factory()->create(['name' => 'Aliko Dangote']);
        $vehicle = Vehicle::factory()->create([
            'user_id' => $user->id,
            'plate_number' => 'BWR-829SB',
            'make' => 'Mercedes-Benz',
            'model' => 'GLK 350',
        ]);

        $device = Device::factory()->create([
            'vehicle_id' => $vehicle->id,
            'user_id' => $user->id,
            'imei' => '865167042871039',
            'model' => 'GG402',
            'sim_number' => '0700079956',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('manage.devices.show', $device));

        $response->assertOk();
        $response->assertSee('865167042871039');
        $response->assertSee('Mercedes-Benz GLK 350');
        $response->assertSee('BWR-829SB');
        $response->assertSee('0700079956');
    }

    public function test_admin_can_update_device_metadata(): void
    {
        $device = Device::factory()->create([
            'imei' => '865167042871039',
            'model' => 'GG402',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('manage.devices.update', $device), [
                'label' => 'Updated Fleet Tracker',
                'model' => 'GG402-V2',
                'status' => 'active',
                'sim_number' => '09099998888',
            ]);

        $response->assertRedirect(route('manage.devices.show', $device));
        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'label' => 'Updated Fleet Tracker',
            'model' => 'GG402-V2',
            'sim_number' => '09099998888',
        ]);
    }

    public function test_admin_can_send_command_to_device(): void
    {
        $device = Device::factory()->create([
            'imei' => '865167042871039',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('manage.devices.command', $device), [
                'type' => 'remote_shutdown',
                'reason' => 'Emergency immobilize',
            ]);

        $response->assertRedirect(route('manage.devices.show', $device));
        $this->assertDatabaseHas('device_commands', [
            'device_id' => $device->id,
            'type' => 'remote_shutdown',
            'status' => DeviceCommand::STATUS_ACKNOWLEDGED,
        ]);
    }

    public function test_admin_can_unbind_device(): void
    {
        $vehicle = Vehicle::factory()->create(['plate_number' => 'KJA-982XY']);
        $device = Device::factory()->create([
            'vehicle_id' => $vehicle->id,
            'imei' => '865167042870221',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('manage.devices.unbind', $device));

        $response->assertRedirect(route('manage.devices.show', $device));
        $device->refresh();
        $this->assertNull($device->vehicle_id);
        $this->assertEquals(Device::STATUS_UNBOUND, $device->status);
    }
}
