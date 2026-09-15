<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionRevocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/auth/sessions')->assertUnauthorized();
        $this->deleteJson('/api/v1/auth/sessions/'.(string) \Illuminate\Support\Str::uuid())
            ->assertUnauthorized();
    }

    public function test_a_customer_can_list_their_signed_in_devices(): void
    {
        $user = User::factory()->create();
        $current = $user->createToken('this-phone')->plainTextToken;
        $user->createToken('old-tablet');

        $response = $this->withToken($current)
            ->getJson('/api/v1/auth/sessions')
            ->assertOk()
            ->assertJsonCount(2, 'sessions');

        $sessions = collect($response->json('sessions'));

        $this->assertSame(1, $sessions->where('is_current', true)->count());
        $this->assertSame('this-phone', $sessions->firstWhere('is_current', true)['name']);
    }

    public function test_a_session_payload_never_exposes_the_numeric_id_or_the_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('phone')->plainTextToken;

        $session = $this->withToken($token)
            ->getJson('/api/v1/auth/sessions')
            ->assertOk()
            ->json('sessions.0');

        $this->assertArrayHasKey('uuid', $session);
        $this->assertArrayNotHasKey('id', $session);
        $this->assertArrayNotHasKey('token', $session);
    }

    public function test_a_customer_can_revoke_one_of_their_own_sessions_by_uuid(): void
    {
        $user = User::factory()->create();
        $current = $user->createToken('this-phone')->plainTextToken;
        $otherToken = $user->createToken('old-tablet')->plainTextToken;

        $otherUuid = $user->tokens()->where('name', 'old-tablet')->first()->uuid;

        $this->withToken($current)
            ->deleteJson('/api/v1/auth/sessions/'.$otherUuid)
            ->assertOk()
            ->assertJsonPath('was_current', false);

        $this->assertSame(1, $user->fresh()->tokens()->count());
        $this->assertDatabaseMissing('personal_access_tokens', ['uuid' => $otherUuid]);

        // The revoked token is genuinely dead.
        //
        // Guards are resolved once per application instance, and Laravel's
        // RequestGuard caches the user it resolved, so within a single test the
        // second request would otherwise still be authenticated by the first
        // token. Production resolves a fresh guard per request; forgetting the
        // guards here reproduces that.
        $this->app['auth']->forgetGuards();

        $this->withToken($otherToken)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'unauthenticated');
    }

    public function test_a_customer_cannot_revoke_another_customers_session(): void
    {
        $victim = User::factory()->create();
        $attacker = User::factory()->create();

        $victim->createToken('victim-phone');
        $victimUuid = $victim->tokens()->first()->uuid;

        $attackerToken = $attacker->createToken('attacker-phone')->plainTextToken;

        $this->withToken($attackerToken)
            ->deleteJson('/api/v1/auth/sessions/'.$victimUuid)
            ->assertNotFound()
            ->assertJsonPath('code', 'not_found');

        $this->assertSame(1, $victim->fresh()->tokens()->count());
    }

    public function test_revoking_the_current_session_reports_itself_as_current(): void
    {
        $user = User::factory()->create();
        $current = $user->createToken('this-phone')->plainTextToken;

        $uuid = $user->tokens()->first()->uuid;

        $this->withToken($current)
            ->deleteJson('/api/v1/auth/sessions/'.$uuid)
            ->assertOk()
            ->assertJsonPath('was_current', true);

        $this->assertSame(0, $user->fresh()->tokens()->count());
    }

    public function test_an_unknown_session_uuid_is_a_clean_not_found(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/auth/sessions/'.(string) \Illuminate\Support\Str::uuid())
            ->assertNotFound()
            ->assertJsonStructure(['message', 'code']);
    }
}
