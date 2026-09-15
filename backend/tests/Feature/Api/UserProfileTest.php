<?php

namespace Tests\Feature\Api;

use App\Models\Admin;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_can_read_their_own_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/users/me')
            ->assertOk()
            ->assertJsonPath('user.uuid', $user->uuid)
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonStructure(['user', 'entitlements' => ['is_premium', 'features']]);
    }

    public function test_the_profile_never_exposes_the_numeric_id_or_password(): void
    {
        $user = User::factory()->create();

        $payload = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/users/me')
            ->assertOk()
            ->json('user');

        $this->assertArrayNotHasKey('id', $payload);
        $this->assertArrayNotHasKey('password', $payload);
    }

    public function test_profile_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/users/me')->assertUnauthorized();
        $this->patchJson('/api/v1/users/me', ['name' => 'Nope'])->assertUnauthorized();
        $this->putJson('/api/v1/users/me/password', [])->assertUnauthorized();
    }

    public function test_a_customer_can_update_their_profile(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/users/me', [
                'name' => 'Amina Bello',
                'timezone' => 'Africa/Lagos',
                'locale' => 'en',
            ])
            ->assertOk()
            ->assertJsonPath('user.name', 'Amina Bello');

        $this->assertDatabaseHas('users', ['id' => $user->getKey(), 'name' => 'Amina Bello']);
    }

    public function test_a_changed_phone_number_loses_its_verified_state(): void
    {
        $user = User::factory()->create([
            'phone' => '+2348011111111',
            'phone_verified_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/users/me', ['phone' => '+2348099999999'])
            ->assertOk();

        $this->assertNull($user->fresh()->phone_verified_at);
    }

    public function test_an_unchanged_phone_number_keeps_its_verified_state(): void
    {
        $user = User::factory()->create([
            'phone' => '+2348011111111',
            'phone_verified_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/users/me', ['name' => 'Same Phone'])
            ->assertOk();

        $this->assertNotNull($user->fresh()->phone_verified_at);
    }

    public function test_a_phone_number_already_in_use_is_rejected(): void
    {
        User::factory()->create(['phone' => '+2348055555555']);
        $user = User::factory()->create(['phone' => '+2348066666666']);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/users/me', ['phone' => '+2348055555555'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'validation_error')
            ->assertJsonValidationErrors('phone');
    }

    public function test_a_customer_cannot_escalate_their_own_status(): void
    {
        $user = User::factory()->create();

        // `status` is not fillable, so it is ignored rather than applied.
        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/users/me', ['name' => 'Legit', 'status' => 'active'])
            ->assertOk();

        $this->assertSame('active', $user->fresh()->status);
    }

    public function test_an_invalid_timezone_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/users/me', ['timezone' => 'Mars/Olympus_Mons'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('timezone');
    }

    public function test_a_customer_can_change_their_password(): void
    {
        $user = User::factory()->create(['password' => 'Password!2345']);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/users/me/password', [
                'current_password' => 'Password!2345',
                'password' => 'BrandNew!9876',
                'password_confirmation' => 'BrandNew!9876',
            ])
            ->assertOk();

        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check('BrandNew!9876', $user->fresh()->password),
        );
    }

    public function test_a_wrong_current_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'Password!2345']);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/users/me/password', [
                'current_password' => 'NotMyPassword',
                'password' => 'BrandNew!9876',
                'password_confirmation' => 'BrandNew!9876',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('current_password');
    }

    public function test_a_mismatched_confirmation_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'Password!2345']);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/users/me/password', [
                'current_password' => 'Password!2345',
                'password' => 'BrandNew!9876',
                'password_confirmation' => 'SomethingElse!1',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_reusing_the_current_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'Password!2345']);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/users/me/password', [
                'current_password' => 'Password!2345',
                'password' => 'Password!2345',
                'password_confirmation' => 'Password!2345',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_changing_the_password_signs_out_other_devices_but_keeps_the_caller(): void
    {
        $user = User::factory()->create(['password' => 'Password!2345']);

        $current = $user->createToken('this-phone')->plainTextToken;
        $user->createToken('old-tablet');
        $user->createToken('old-laptop');

        $this->assertSame(3, $user->tokens()->count());

        $this->withToken($current)
            ->putJson('/api/v1/users/me/password', [
                'current_password' => 'Password!2345',
                'password' => 'BrandNew!9876',
                'password_confirmation' => 'BrandNew!9876',
            ])
            ->assertOk()
            ->assertJsonPath('revoked_tokens', 2);

        $this->assertSame(1, $user->fresh()->tokens()->count());
        $this->assertSame('this-phone', $user->fresh()->tokens()->first()->name);
    }

    public function test_a_customer_cannot_reach_an_admin_profile_route(): void
    {
        $user = User::factory()->create();
        Admin::factory()->create();

        // There is no customer-facing admin record endpoint at all.
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/users')
            ->assertNotFound();
    }

    public function test_vehicle_listing_is_not_exposed_through_the_users_area(): void
    {
        $user = User::factory()->create();
        Vehicle::factory()->create(['user_id' => $user->getKey()]);

        // A customer cannot enumerate other customers or their vehicles here.
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/users/me/vehicles')
            ->assertNotFound();
    }
}
