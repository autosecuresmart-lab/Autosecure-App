<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_can_register_and_receives_a_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Amina Bello',
            'email' => 'amina@example.com',
            'phone' => '+2348012345678',
            'password' => 'Password!2345',
            'password_confirmation' => 'Password!2345',
            'device_name' => 'Pixel 8',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'token',
                'user' => ['uuid', 'name', 'email'],
                'entitlements' => ['is_premium', 'features'],
            ]);

        $this->assertDatabaseHas('users', ['email' => 'amina@example.com']);

        // Every customer gets a Coins wallet up front.
        $this->assertNotNull(User::where('email', 'amina@example.com')->first()->coinWallet);
    }

    public function test_the_numeric_id_is_never_exposed_in_api_payloads(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Numeric Check',
            'email' => 'numeric@example.com',
            'password' => 'Password!2345',
            'password_confirmation' => 'Password!2345',
        ]);

        $response->assertCreated();

        $this->assertArrayNotHasKey('id', $response->json('user'));
        $this->assertNotEmpty($response->json('user.uuid'));
    }

    public function test_a_customer_can_log_in_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'Password!2345']);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Password!2345',
        ])->assertOk()->assertJsonStructure(['token', 'user', 'entitlements']);

        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_login_is_rejected_for_invalid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'Password!2345']);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_a_suspended_customer_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'password' => 'Password!2345',
            'status' => 'suspended',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Password!2345',
        ])->assertStatus(403)->assertJsonPath('code', 'account_inactive');
    }

    public function test_protected_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/vehicles')->assertUnauthorized();
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_an_authenticated_customer_can_read_their_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.uuid', $user->uuid);
    }

    public function test_logging_out_revokes_only_the_current_token(): void
    {
        $user = User::factory()->create();

        $first = $user->createToken('phone')->plainTextToken;
        $user->createToken('tablet');

        $this->withToken($first)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertSame(1, $user->tokens()->count());
    }
}
