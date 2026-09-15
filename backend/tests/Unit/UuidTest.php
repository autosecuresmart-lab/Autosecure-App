<?php

namespace Tests\Unit;

use App\Models\Admin;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Every AUTOSECURE table carries id + uuid, and uuid is what routes resolve.
 */
class UuidTest extends TestCase
{
    use RefreshDatabase;

    public function test_models_generate_a_uuid_on_create(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->assertNotNull($vehicle->uuid);
        $this->assertTrue(Str::isUuid($vehicle->uuid));
    }

    public function test_uuid_is_generated_even_when_model_events_are_muted(): void
    {
        // DatabaseSeeder runs inside Model::withoutEvents(); a uuid hook based on
        // the `creating` event would silently produce null there.
        $vehicle = Model::withoutEvents(fn () => Vehicle::factory()->create());

        $this->assertNotNull($vehicle->uuid);
        $this->assertTrue(Str::isUuid($vehicle->uuid));
    }

    public function test_uuid_is_unique_across_rows(): void
    {
        $uuids = Vehicle::factory()->count(5)->create()->pluck('uuid');

        $this->assertCount(5, $uuids->unique());
    }

    public function test_uuid_is_the_route_key(): void
    {
        $this->assertSame('uuid', (new Vehicle)->getRouteKeyName());
        $this->assertSame('uuid', (new User)->getRouteKeyName());
        $this->assertSame('uuid', (new Admin)->getRouteKeyName());
        $this->assertSame('uuid', (new Role)->getRouteKeyName());
    }

    public function test_models_resolve_by_uuid(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->assertTrue($vehicle->is(Vehicle::whereUuid($vehicle->uuid)->first()));
        $this->assertNull(Vehicle::whereUuid((string) Str::uuid())->first());
    }

    public function test_the_uuid_is_not_mass_assignable(): void
    {
        $vehicle = Vehicle::factory()->create();
        $original = $vehicle->uuid;

        $vehicle->fill(['uuid' => 'attacker-controlled']);

        $this->assertSame($original, $vehicle->uuid);
    }

    public function test_pivot_models_are_addressable_too(): void
    {
        $role = Role::create(['name' => 'Ops', 'slug' => 'ops', 'guard' => 'admin']);
        $permission = Permission::create(['name' => 'View', 'slug' => 'ops.view', 'group' => 'ops']);

        $role->syncPermissions([$permission->getKey()]);

        $link = $role->permissions()->first()->pivot;

        $this->assertNotNull($link->uuid);
        $this->assertTrue(Str::isUuid($link->uuid));
    }
}
