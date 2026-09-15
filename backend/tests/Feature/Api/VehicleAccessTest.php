<?php

namespace Tests\Feature\Api;

use App\Http\Middleware\EnsureVehicleAccess;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleAccessGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Tests\TestCase;

class VehicleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_only_sees_their_own_vehicles(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        Vehicle::factory()->count(2)->create(['user_id' => $owner->getKey()]);
        Vehicle::factory()->create(['user_id' => $stranger->getKey()]);

        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v1/vehicles')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_a_vehicle_is_addressed_by_uuid(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['user_id' => $user->getKey()]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/vehicles/'.$vehicle->uuid)
            ->assertOk()
            ->assertJsonPath('vehicle.uuid', $vehicle->uuid);
    }

    public function test_the_numeric_id_is_not_a_valid_route_key(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['user_id' => $user->getKey()]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/vehicles/'.$vehicle->getKey())
            ->assertNotFound();
    }

    public function test_a_customer_cannot_read_someone_elses_vehicle(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['user_id' => $owner->getKey()]);

        $this->actingAs($stranger, 'sanctum')
            ->getJson('/api/v1/vehicles/'.$vehicle->uuid)
            ->assertForbidden()
            ->assertJsonPath('code', 'vehicle_forbidden');
    }

    public function test_only_the_owner_may_delete_a_vehicle(): void
    {
        $owner = User::factory()->create();
        $driver = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['user_id' => $owner->getKey()]);

        VehicleAccessGrant::create([
            'vehicle_id' => $vehicle->getKey(),
            'user_id' => $driver->getKey(),
            'granted_by_user_id' => $owner->getKey(),
            'role' => 'driver',
            'can_send_commands' => true,
        ]);

        $this->actingAs($driver, 'sanctum')
            ->deleteJson('/api/v1/vehicles/'.$vehicle->uuid)
            ->assertForbidden();

        $this->assertNotSoftDeleted($vehicle);
    }

    public function test_creating_a_vehicle_takes_ownership_of_the_first_one(): void
    {
        $user = User::factory()->create();

        $first = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/vehicles', ['plate_number' => 'LAG-111-AA'])
            ->assertCreated();

        $this->assertTrue($first->json('vehicle.is_primary'));

        $second = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/vehicles', ['plate_number' => 'LAG-222-BB'])
            ->assertCreated();

        $this->assertFalse($second->json('vehicle.is_primary'));

        // The owner is recorded as an explicit owner grant.
        $this->assertDatabaseHas('vehicle_access_grants', [
            'vehicle_id' => Vehicle::whereUuid($first->json('vehicle.uuid'))->value('id'),
            'user_id' => $user->getKey(),
            'role' => 'owner',
        ]);
    }

    /**
     * A shared user without the specific capability must be refused, even though
     * they can reach the vehicle at all.
     */
    public function test_a_grant_without_the_required_capability_is_refused(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['user_id' => $owner->getKey()]);

        VehicleAccessGrant::create([
            'vehicle_id' => $vehicle->getKey(),
            'user_id' => $viewer->getKey(),
            'granted_by_user_id' => $owner->getKey(),
            'role' => 'viewer',
            'can_view_location' => true,
            'can_view_video' => false,
        ]);

        $middleware = app(EnsureVehicleAccess::class);

        $this->assertSame(200, $this->runMiddleware($middleware, $vehicle, $viewer, 'location')->getStatusCode());

        $denied = $this->runMiddleware($middleware, $vehicle, $viewer, 'video');
        $this->assertSame(403, $denied->getStatusCode());
        $this->assertSame('vehicle_capability_denied', $denied->getData(true)['code']);
    }

    public function test_a_revoked_grant_no_longer_grants_access(): void
    {
        $owner = User::factory()->create();
        $driver = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['user_id' => $owner->getKey()]);

        VehicleAccessGrant::create([
            'vehicle_id' => $vehicle->getKey(),
            'user_id' => $driver->getKey(),
            'granted_by_user_id' => $owner->getKey(),
            'role' => 'driver',
            'revoked_at' => now()->subMinute(),
        ]);

        $this->actingAs($driver, 'sanctum')
            ->getJson('/api/v1/vehicles/'.$vehicle->uuid)
            ->assertForbidden();
    }

    private function runMiddleware(EnsureVehicleAccess $middleware, Vehicle $vehicle, User $user, string $capability): \Symfony\Component\HttpFoundation\Response
    {
        $request = Request::create('/test', 'GET');
        $route = new Route('GET', '/test/{vehicle}', []);
        $route->bind($request);
        $route->setParameter('vehicle', $vehicle);
        $request->setRouteResolver(fn () => $route);
        $request->setUserResolver(fn () => $user);

        return $middleware->handle($request, fn () => response()->json(['ok' => true]), $capability);
    }
}
