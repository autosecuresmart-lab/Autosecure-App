<?php

namespace Tests\Feature\Manage;

use App\Models\Admin;
use App\Models\Device;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManageUsersTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->admin = Admin::factory()->superAdmin()->create();
    }

    public function test_admin_can_view_users_index_page(): void
    {
        $user1 = User::factory()->create(['name' => 'Alice Johnson', 'status' => 'active']);
        $user2 = User::factory()->create(['name' => 'Bob Smith', 'status' => 'suspended']);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('manage.users.index'));

        $response->assertOk()
            ->assertSee('Users Management')
            ->assertSee('Alice Johnson')
            ->assertSee('Bob Smith')
            ->assertSee('Create New User');
    }

    public function test_admin_can_filter_users_by_search_query_and_status(): void
    {
        User::factory()->create(['name' => 'Michael Scott', 'email' => 'michael@dunder.com', 'status' => 'active']);
        User::factory()->create(['name' => 'Dwight Schrute', 'email' => 'dwight@dunder.com', 'status' => 'suspended']);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('manage.users.index', ['search' => 'Dwight', 'status' => 'suspended']));

        $response->assertOk()
            ->assertSee('Dwight Schrute')
            ->assertDontSee('Michael Scott');
    }

    public function test_admin_can_create_user_with_initial_vehicle(): void
    {
        $payload = [
            'name' => 'Sarah Connor',
            'email' => 'sarah@skynet.com',
            'phone' => '+2348099887766',
            'account_type' => 'business',
            'company_name' => 'Resistance Fleet Ltd',
            'status' => 'active',
            'password' => 'secret123',
            'attach_vehicle' => '1',
            'vehicle_make' => 'Toyota',
            'vehicle_model' => 'Hilux',
            'vehicle_year' => 2024,
            'vehicle_plate' => 'KJA-992-AZ',
            'vehicle_colour' => 'Matte Black',
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('manage.users.store'), $payload);

        $createdUser = User::where('email', 'sarah@skynet.com')->first();
        $this->assertNotNull($createdUser);
        $response->assertRedirect(route('manage.users.show', $createdUser));

        $this->assertDatabaseHas('users', [
            'email' => 'sarah@skynet.com',
            'name' => 'Sarah Connor',
            'account_type' => 'business',
            'company_name' => 'Resistance Fleet Ltd',
        ]);

        $this->assertDatabaseHas('vehicles', [
            'user_id' => $createdUser->id,
            'plate_number' => 'KJA-992-AZ',
            'make' => 'Toyota',
            'model' => 'Hilux',
        ]);
    }

    public function test_admin_can_view_user_show_page_with_col8_col4_details(): void
    {
        $user = User::factory()->create(['name' => 'James Bond']);
        $vehicle = Vehicle::factory()->create([
            'user_id' => $user->id,
            'make' => 'Aston Martin',
            'model' => 'DB5',
            'plate_number' => 'JB-007-MI6',
        ]);

        Device::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'type' => 'tracker',
            'serial_number' => 'TRK-007',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('manage.users.show', $user));

        $response->assertOk()
            ->assertSee('James Bond')
            ->assertSee('Aston Martin DB5')
            ->assertSee('JB-007-MI6')
            ->assertSee('TRK-007')
            ->assertSee('Quick Actions')
            ->assertSee('Edit Customer Profile')
            ->assertSee('Assign / Add Vehicle')
            ->assertSee('Bind Hardware Device')
            ->assertSee('Remote Security Command');
    }

    public function test_admin_can_update_user_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('manage.users.update', $user), [
                'name' => 'New Name',
                'email' => 'new@example.com',
                'phone' => '+2348011223344',
                'account_type' => 'individual',
                'status' => 'suspended',
            ]);

        $response->assertRedirect(route('manage.users.show', $user));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
            'status' => 'suspended',
        ]);
    }

    public function test_admin_can_add_vehicle_to_existing_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('manage.users.vehicles.store', $user), [
                'make' => 'Honda',
                'model' => 'Accord',
                'year' => 2023,
                'plate_number' => 'ABC-456-XY',
                'colour' => 'Blue',
                'fuel_type' => 'Petrol',
                'transmission' => 'Automatic',
                'odometer_km' => 15000,
            ]);

        $response->assertRedirect(route('manage.users.show', $user));

        $this->assertDatabaseHas('vehicles', [
            'user_id' => $user->id,
            'plate_number' => 'ABC-456-XY',
            'make' => 'Honda',
            'model' => 'Accord',
        ]);
    }

    public function test_admin_can_bind_device_to_user_and_vehicle(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('manage.users.devices.store', $user), [
                'type' => 'tracker',
                'vehicle_id' => $vehicle->id,
                'brand' => 'Teltonika',
                'model' => 'FMB920',
                'serial_number' => 'TELT-99281',
                'imei' => '359827102938475',
                'sim_number' => '+234800000000',
            ]);

        $response->assertRedirect(route('manage.users.show', $user));

        $this->assertDatabaseHas('devices', [
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'serial_number' => 'TELT-99281',
            'type' => 'tracker',
        ]);
    }

    public function test_admin_can_send_security_command(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['user_id' => $user->id]);
        Device::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'type' => 'tracker',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('manage.users.security-command', $user), [
                'vehicle_id' => $vehicle->id,
                'command_type' => 'engine_cut',
                'reason' => 'Emergency theft report',
            ]);

        $response->assertRedirect(route('manage.users.show', $user));

        $this->assertDatabaseHas('device_commands', [
            'vehicle_id' => $vehicle->id,
            'type' => \App\Models\DeviceCommand::TYPE_REMOTE_SHUTDOWN,
        ]);
    }

    public function test_admin_can_toggle_user_status(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('manage.users.status', $user));

        $response->assertRedirect();
        $this->assertSame('suspended', $user->fresh()->status);

        $this->actingAs($this->admin, 'admin')
            ->post(route('manage.users.status', $user));

        $this->assertSame('active', $user->fresh()->status);
    }

    public function test_admin_can_soft_delete_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->delete(route('manage.users.destroy', $user));

        $response->assertRedirect(route('manage.users.index'));
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_admin_can_view_live_map_with_vehicles_and_devices(): void
    {
        $this->seed(\Database\Seeders\RealGpsDevicesSeeder::class);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('manage.live-map.index'));

        $response->assertOk()
            ->assertSee('Mercedes-Benz')
            ->assertSee('BWR-829SB')
            ->assertSee('Toyota Corolla')
            ->assertSee('GWA228CM');
    }
}

