<?php

namespace Tests\Feature\Api;

use App\Http\Middleware\EnsureEntitlement;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Subscriptions\EntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * The product rule that matters most commercially:
 * core security stays free, premium lapses without taking it away.
 */
class SubscriptionEntitlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_security_features_are_available_without_any_subscription(): void
    {
        $user = User::factory()->create();

        $entitlements = app(EntitlementService::class);

        $this->assertFalse($entitlements->isPremium($user));
        $this->assertTrue($entitlements->can($user, 'security.live_location'));
        $this->assertTrue($entitlements->can($user, 'security.remote_shutdown'));
        $this->assertTrue($entitlements->can($user, 'security.theft_trigger'));
        $this->assertTrue($entitlements->can($user, 'dashcam.live'));
    }

    public function test_premium_features_are_unavailable_on_the_free_tier(): void
    {
        $user = User::factory()->create();

        $this->assertFalse(app(EntitlementService::class)->can($user, 'care.oil_change'));
    }

    public function test_an_active_premium_subscription_unlocks_care_features(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        $plan->features()->create(['feature_key' => 'care.oil_change']);

        $user = User::factory()->create();
        Subscription::factory()->create([
            'user_id' => $user->getKey(),
            'subscription_plan_id' => $plan->getKey(),
        ]);

        $this->assertTrue(app(EntitlementService::class)->can($user->fresh(), 'care.oil_change'));
    }

    public function test_an_expired_subscription_removes_premium_but_keeps_security(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        $plan->features()->create(['feature_key' => 'care.oil_change']);

        $user = User::factory()->create();
        Subscription::factory()->expired()->create([
            'user_id' => $user->getKey(),
            'subscription_plan_id' => $plan->getKey(),
        ]);

        $entitlements = app(EntitlementService::class);
        $user = $user->fresh();

        $this->assertFalse($entitlements->isPremium($user));
        $this->assertFalse($entitlements->can($user, 'care.oil_change'));

        // The essential tracker/dashcam controls must survive a downgrade.
        $this->assertTrue($entitlements->can($user, 'security.remote_shutdown'));
        $this->assertTrue($entitlements->can($user, 'security.theft_trigger'));
        $this->assertTrue($entitlements->can($user, 'dashcam.live'));
        $this->assertTrue($entitlements->can($user, 'dashcam.playback'));
    }

    public function test_access_continues_during_the_grace_period(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        $plan->features()->create(['feature_key' => 'care.oil_change']);

        $user = User::factory()->create();
        Subscription::factory()->inGrace()->create([
            'user_id' => $user->getKey(),
            'subscription_plan_id' => $plan->getKey(),
        ]);

        $this->assertTrue(app(EntitlementService::class)->can($user->fresh(), 'care.oil_change'));
    }

    public function test_the_entitlement_middleware_blocks_premium_routes_with_payment_required(): void
    {
        $user = User::factory()->create();

        $response = $this->runMiddleware($user, 'care.oil_change');

        $this->assertSame(402, $response->getStatusCode());
        $this->assertSame('premium_required', $response->getData(true)['code']);
    }

    public function test_the_entitlement_middleware_allows_core_features_through(): void
    {
        $user = User::factory()->create();

        $this->assertSame(200, $this->runMiddleware($user, 'security.live_location')->getStatusCode());
    }

    private function runMiddleware(User $user, string $feature): \Symfony\Component\HttpFoundation\Response
    {
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        return app(EnsureEntitlement::class)->handle(
            $request,
            fn () => response()->json(['ok' => true]),
            $feature,
        );
    }
}
