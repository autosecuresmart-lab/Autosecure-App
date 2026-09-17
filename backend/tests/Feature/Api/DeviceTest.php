<?php

namespace Tests\Feature\Api;

use App\Http\Middleware\EnsureDeviceAccess;
use App\Models\Device;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleAccessGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Tests\TestCase;

class DeviceTest extends TestCase
{
    use RefreshDatabase;

    /* --------------------------------------------------------------------- */
    /* Listing and reading                                                    */
    /* --------------------------------------------------------------------- */

    public function test_a_customer_sees_devices_bound_to_their_own_vehicles(): void
    {
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['user_id' => $owner->getKey()]);

        Device::factory()->boundTo($vehicle)->create();
        Device::factory()->dashcam()->boundTo($vehicle)->create();

        $response = $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v1/devices')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->assertSame(1, $response->json('meta.trackers'));
        $this->assertSame(1, $response->json('meta.dashcams'));
    }

    public function test_a_customer_never_sees_another_customers_devices(): void
    {
        $stranger = User::factory()->create();
        $otherVehicle = Vehicle::factory()->create();

        Device::factory()->create(['vehicle_id' => $otherVehicle->getKey()]);

        $this->actingAs($stranger, 'sanctum')
            ->getJson('/api/v1/devices')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_a_customer_with_a_vehicle_grant_sees_that_vehicles_devices(): void
    {
        $owner = User::factory()->create();
        $driver = User::factory()->create();

        $vehicle = Vehicle::factory()->create(['user_id' => $owner->getKey()]);
        Device::factory()->create(['vehicle_id' => $vehicle->getKey()]);

        VehicleAccessGrant::create([
            'vehicle_id' => $vehicle->getKey(),
            'user_id' => $driver->getKey(),
            'granted_by_user_id' => $owner->getKey(),
            'role' => 'driver',
            'can_view_location' => true,
        ]);

        $this->actingAs($driver, 'sanctum')
            ->getJson('/api/v1/devices')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_devices_can_be_filtered_by_type(): void
    {
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['user_id' => $owner->getKey()]);

        Device::factory()->create(['vehicle_id' => $vehicle->getKey()]);
        Device::factory()->dashcam()->create(['vehicle_id' => $vehicle->getKey()]);

        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v1/devices?type=dashcam')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'dashcam');
    }

    public function test_filtering_by_an_unknown_vehicle_returns_nothing_rather_than_everything(): void
    {
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['user_id' => $owner->getKey()]);
        Device::factory()->create(['vehicle_id' => $vehicle->getKey()]);

        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v1/devices?vehicle='.(string) \Illuminate\Support\Str::uuid())
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_an_invalid_type_filter_is_rejected(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v1/devices?type=drone')
            ->assertStatus(422)
            ->assertJsonValidationErrors('type');
    }

    public function test_a_device_is_addressed_by_uuid(): void
    {
        $owner = User::factory()->create();
        $device = Device::factory()->reservedFor($owner)->create();

        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v1/devices/'.$device->uuid)
            ->assertOk()
            ->assertJsonPath('device.uuid', $device->uuid);
    }

    public function test_the_numeric_id_is_not_a_valid_device_route_key(): void
    {
        $owner = User::factory()->create();
        $device = Device::factory()->reservedFor($owner)->create();

        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v1/devices/'.$device->getKey())
            ->assertNotFound();
    }

    public function test_a_customer_cannot_read_a_device_on_someone_elses_unshared_vehicle(): void
    {
        $stranger = User::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $device = Device::factory()->create(['vehicle_id' => $vehicle->getKey()]);

        $this->actingAs($stranger, 'sanctum')
            ->getJson('/api/v1/devices/'.$device->uuid)
            ->assertForbidden()
            ->assertJsonPath('code', 'device_forbidden');
    }

    public function test_device_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/devices')->assertUnauthorized();

        $device = Device::factory()->create();
        $this->getJson('/api/v1/devices/'.$device->uuid)->assertUnauthorized();
    }

    /* --------------------------------------------------------------------- */
    /* Capability enforcement                                                 */
    /* --------------------------------------------------------------------- */

    public function test_a_grant_without_the_video_capability_cannot_reach_a_camera_device(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();

        $vehicle = Vehicle::factory()->create(['user_id' => $owner->getKey()]);
        $device = Device::factory()->dashcam()->create(['vehicle_id' => $vehicle->getKey()]);

        VehicleAccessGrant::create([
            'vehicle_id' => $vehicle->getKey(),
            'user_id' => $viewer->getKey(),
            'granted_by_user_id' => $owner->getKey(),
            'role' => 'viewer',
            'can_view_location' => true,
            'can_view_video' => false,
        ]);

        $middleware = app(EnsureDeviceAccess::class);

        $this->assertSame(200, $this->runMiddleware($middleware, $device, $viewer, 'location')->getStatusCode());

        $denied = $this->runMiddleware($middleware, $device, $viewer, 'video');
        $this->assertSame(403, $denied->getStatusCode());
        $this->assertSame('device_capability_denied', $denied->getData(true)['code']);
    }

    public function test_a_revoked_grant_loses_device_access(): void
    {
        $owner = User::factory()->create();
        $driver = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['user_id' => $owner->getKey()]);
        $device = Device::factory()->create(['vehicle_id' => $vehicle->getKey()]);

        VehicleAccessGrant::create([
            'vehicle_id' => $vehicle->getKey(),
            'user_id' => $driver->getKey(),
            'granted_by_user_id' => $owner->getKey(),
            'role' => 'driver',
            'revoked_at' => now()->subMinute(),
        ]);

        $this->actingAs($driver, 'sanctum')
            ->getJson('/api/v1/devices/'.$device->uuid)
            ->assertForbidden();
    }

    /* --------------------------------------------------------------------- */
    /* Relationship with the vehicle                                          */
    /* --------------------------------------------------------------------- */

    public function test_a_devices_vehicle_can_be_listed_through_the_vehicle_route(): void
    {
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['user_id' => $owner->getKey()]);
        Device::factory()->create(['vehicle_id' => $vehicle->getKey()]);

        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v1/vehicles/'.$vehicle->uuid.'/devices')
            ->assertOk()
            ->assertJsonCount(1, 'devices');
    }

    public function test_a_devices_payload_includes_its_vehicle(): void
    {
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['user_id' => $owner->getKey(), 'nickname' => 'Family car']);
        $device = Device::factory()->boundTo($vehicle)->create();

        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v1/devices/'.$device->uuid)
            ->assertOk()
            ->assertJsonPath('device.is_bound', true)
            ->assertJsonPath('device.vehicle.uuid', $vehicle->uuid)
            ->assertJsonPath('device.vehicle.display_name', 'Family car');
    }

    public function test_an_unbound_device_reports_itself_as_unbound(): void
    {
        $owner = User::factory()->create();
        $device = Device::factory()->reservedFor($owner)->create();

        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v1/devices/'.$device->uuid)
            ->assertOk()
            ->assertJsonPath('device.is_bound', false)
            ->assertJsonPath('device.vehicle', null);
    }

    /* --------------------------------------------------------------------- */
    /* Renaming                                                               */
    /* --------------------------------------------------------------------- */

    public function test_a_customer_can_label_their_device(): void
    {
        $owner = User::factory()->create();
        $device = Device::factory()->reservedFor($owner)->create();

        $this->actingAs($owner, 'sanctum')
            ->patchJson('/api/v1/devices/'.$device->uuid, ['label' => 'Front camera'])
            ->assertOk()
            ->assertJsonPath('device.label', 'Front camera')
            ->assertJsonPath('device.display_name', 'Front camera');

        $this->assertDatabaseHas('devices', ['id' => $device->getKey(), 'label' => 'Front camera']);
    }

    public function test_provisioning_facts_cannot_be_edited_by_a_customer(): void
    {
        $owner = User::factory()->create();
        $device = Device::factory()->reservedFor($owner)->create([
            'serial_number' => 'ORIGINAL-SERIAL',
        ]);

        $this->actingAs($owner, 'sanctum')
            ->patchJson('/api/v1/devices/'.$device->uuid, [
                'label' => 'Renamed',
                'serial_number' => 'TAMPERED',
                'imei' => '000000000000000',
                'status' => 'active',
            ])
            ->assertOk();

        $fresh = $device->fresh();

        $this->assertSame('ORIGINAL-SERIAL', $fresh->serial_number);
        $this->assertNotSame('000000000000000', $fresh->imei);
        $this->assertSame('Renamed', $fresh->label);
    }

    /* --------------------------------------------------------------------- */
    /* Binding                                                                */
    /* --------------------------------------------------------------------- */

    public function test_a_reserved_device_can_be_bound_to_the_customers_vehicle(): void
    {
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['user_id' => $owner->getKey()]);

        $device = Device::factory()->reservedFor($owner)->create();

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/devices/'.$device->uuid.'/bind', ['vehicle_uuid' => $vehicle->uuid])
            ->assertOk()
            ->assertJsonPath('device.is_bound', true)
            ->assertJsonPath('device.vehicle.uuid', $vehicle->uuid);

        $fresh = $device->fresh();

        $this->assertSame($vehicle->getKey(), $fresh->vehicle_id);
        $this->assertSame($owner->getKey(), $fresh->user_id);
        $this->assertSame(Device::STATUS_ACTIVE, $fresh->status);
        $this->assertNotNull($fresh->bound_at);
    }

    public function test_a_device_not_reserved_for_the_customer_cannot_be_claimed(): void
    {
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['user_id' => $owner->getKey()]);

        // Unbound hardware reserved for somebody else. Knowing the uuid must not
        // be enough to take possession of it, so access is refused before the
        // request even reaches the binding logic.
        $device = Device::factory()->reservedFor(User::factory()->create())->create();

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/devices/'.$device->uuid.'/bind', ['vehicle_uuid' => $vehicle->uuid])
            ->assertForbidden()
            ->assertJsonPath('code', 'device_forbidden');

        $this->assertNull($device->fresh()->vehicle_id);
    }

    public function test_a_device_reserved_for_another_customer_is_refused_even_on_my_own_vehicle(): void
    {
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['user_id' => $owner->getKey()]);

        // Bound to this owner's vehicle, but reserved to somebody else. The
        // vehicle grants reachability, so the reservation guard is what refuses it.
        $device = Device::factory()->create([
            'vehicle_id' => $vehicle->getKey(),
            'user_id' => User::factory()->create()->getKey(),
        ]);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/devices/'.$device->uuid.'/bind', ['vehicle_uuid' => $vehicle->uuid])
            ->assertForbidden()
            ->assertJsonPath('code', 'device_not_reserved');
    }

    public function test_a_customer_cannot_bind_a_device_to_someone_elses_vehicle(): void
    {
        $owner = User::factory()->create();
        $otherVehicle = Vehicle::factory()->create();

        $device = Device::factory()->reservedFor($owner)->create();

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/devices/'.$device->uuid.'/bind', ['vehicle_uuid' => $otherVehicle->uuid])
            ->assertForbidden()
            ->assertJsonPath('code', 'vehicle_forbidden');
    }

    public function test_an_already_bound_device_cannot_be_bound_again(): void
    {
        $owner = User::factory()->create();
        $vehicleA = Vehicle::factory()->create(['user_id' => $owner->getKey()]);
        $vehicleB = Vehicle::factory()->create(['user_id' => $owner->getKey()]);

        $device = Device::factory()->boundTo($vehicleA)->create();

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/devices/'.$device->uuid.'/bind', ['vehicle_uuid' => $vehicleB->uuid])
            ->assertStatus(409)
            ->assertJsonPath('code', 'device_already_bound');

        $this->assertSame($vehicleA->getKey(), $device->fresh()->vehicle_id);
    }

    public function test_binding_requires_a_vehicle_uuid(): void
    {
        $owner = User::factory()->create();
        $device = Device::factory()->reservedFor($owner)->create();

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/devices/'.$device->uuid.'/bind', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('vehicle_uuid');
    }

    /* --------------------------------------------------------------------- */
    /* Unbinding                                                              */
    /* --------------------------------------------------------------------- */

    public function test_the_vehicle_owner_can_unbind_a_device(): void
    {
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['user_id' => $owner->getKey()]);
        $device = Device::factory()->create(['vehicle_id' => $vehicle->getKey()]);

        $this->actingAs($owner, 'sanctum')
            ->deleteJson('/api/v1/devices/'.$device->uuid.'/unbind', ['reason' => 'Sold the car'])
            ->assertOk()
            ->assertJsonPath('device.is_bound', false);

        $fresh = $device->fresh();

        $this->assertNull($fresh->vehicle_id);
        $this->assertNull($fresh->user_id);
        $this->assertSame(Device::STATUS_UNBOUND, $fresh->status);
        $this->assertNotNull($fresh->unbound_at);

        // The provider call cannot happen yet, so it is recorded as outstanding
        // rather than silently forgotten.
        $this->assertTrue($fresh->metadata['provider_unbind_pending']);
    }

    public function test_a_shared_driver_cannot_unbind_the_owners_device(): void
    {
        $owner = User::factory()->create();
        $driver = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['user_id' => $owner->getKey()]);
        $device = Device::factory()->create(['vehicle_id' => $vehicle->getKey()]);

        VehicleAccessGrant::create([
            'vehicle_id' => $vehicle->getKey(),
            'user_id' => $driver->getKey(),
            'granted_by_user_id' => $owner->getKey(),
            'role' => 'driver',
            'can_send_commands' => true,
        ]);

        $this->actingAs($driver, 'sanctum')
            ->deleteJson('/api/v1/devices/'.$device->uuid.'/unbind')
            ->assertForbidden();

        $this->assertNotNull($device->fresh()->vehicle_id);
    }

    public function test_an_unbound_device_can_be_bound_to_another_vehicle(): void
    {
        $owner = User::factory()->create();
        $first = Vehicle::factory()->create(['user_id' => $owner->getKey()]);
        $second = Vehicle::factory()->create(['user_id' => $owner->getKey()]);

        $device = Device::factory()->create([
            'user_id' => $owner->getKey(),
            'vehicle_id' => $first->getKey(),
        ]);

        $this->actingAs($owner, 'sanctum')
            ->deleteJson('/api/v1/devices/'.$device->uuid.'/unbind')
            ->assertOk();

        // Unbinding releases the reservation, so the device is no longer
        // reachable by the customer until provisioning reserves it again.
        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/devices/'.$device->uuid.'/bind', ['vehicle_uuid' => $second->uuid])
            ->assertForbidden()
            ->assertJsonPath('code', 'device_forbidden');

        // refresh() first: the in-memory model still holds the pre-unbind values,
        // so assigning the same user_id would not be seen as dirty and would
        // silently skip the UPDATE.
        $device->refresh();
        $device->forceFill(['user_id' => $owner->getKey()])->save();

        $this->assertSame($owner->getKey(), $device->fresh()->user_id);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/devices/'.$device->uuid.'/bind', ['vehicle_uuid' => $second->uuid])
            ->assertOk()
            ->assertJsonPath('device.vehicle.uuid', $second->uuid);
    }

    /* --------------------------------------------------------------------- */
    /* Integration readiness                                                  */
    /* --------------------------------------------------------------------- */

    public function test_the_device_listing_reports_that_no_provider_is_wired(): void
    {
        $owner = User::factory()->create();

        config([
            'autosecure.devices.tracker.driver' => 'null',
            'autosecure.devices.dashcam.driver' => 'null',
        ]);

        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v1/devices')
            ->assertOk()
            ->assertJsonPath('meta.providers.tracker', 'null')
            ->assertJsonPath('meta.providers.tracker_configured', false)
            ->assertJsonPath('meta.providers.dashcam', 'null')
            ->assertJsonPath('meta.providers.dashcam_configured', false);
    }

    private function runMiddleware(EnsureDeviceAccess $middleware, Device $device, User $user, string $capability): \Symfony\Component\HttpFoundation\Response
    {
        $request = Request::create('/test', 'GET');
        $route = new Route('GET', '/test/{device}', []);
        $route->bind($request);
        $route->setParameter('device', $device);
        $request->setRouteResolver(fn () => $route);
        $request->setUserResolver(fn () => $user);

        return $middleware->handle($request, fn () => response()->json(['ok' => true]), $capability);
    }
}
