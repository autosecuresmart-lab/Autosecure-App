<?php

namespace Tests\Feature\Api;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Subscriptions\SubscriptionService;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected SubscriptionPlan $monthlyPlan;
    protected SubscriptionPlan $yearlyPlan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SubscriptionPlanSeeder::class);

        $this->user = User::factory()->create();
        $this->monthlyPlan = SubscriptionPlan::where('slug', 'monthly')->first();
        $this->yearlyPlan = SubscriptionPlan::where('slug', 'yearly')->first();
    }

    public function test_can_list_subscription_plans_with_proposal_prices(): void
    {
        $response = $this->getJson('/api/v1/subscriptions/plans');

        $response->assertOk()
            ->assertJsonStructure([
                'plans' => [
                    '*' => ['uuid', 'name', 'slug', 'price', 'duration_days', 'discount_percent', 'is_free', 'features'],
                ],
            ]);

        $plans = collect($response->json('plans'))->keyBy('slug');

        $this->assertEquals(4200.0, $plans['monthly']['price']);
        $this->assertEquals(23940.0, $plans['half-yearly']['price']);
        $this->assertEquals(45360.0, $plans['yearly']['price']);
    }

    public function test_can_initialize_subscription_payment(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/payments/initialize', [
                'purpose' => 'subscription',
                'plan_uuid' => $this->monthlyPlan->uuid,
                'channel' => 'card',
                'idempotency_key' => 'idem-sub-12345',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'initialized')
            ->assertJsonPath('data.payment.amount', 4200)
            ->assertJsonPath('data.payment.purpose', 'subscription')
            ->assertJsonPath('data.payment.status', 'pending');

        $this->assertDatabaseHas('payments', [
            'user_id' => $this->user->id,
            'amount' => 4200.0,
            'purpose' => 'subscription',
            'idempotency_key' => 'idem-sub-12345',
        ]);
    }

    public function test_idempotency_key_prevents_duplicate_charge(): void
    {
        $payload = [
            'purpose' => 'subscription',
            'plan_uuid' => $this->monthlyPlan->uuid,
            'idempotency_key' => 'same-key-999',
        ];

        $first = $this->actingAs($this->user)->postJson('/api/v1/payments/initialize', $payload);
        $second = $this->actingAs($this->user)->postJson('/api/v1/payments/initialize', $payload);

        $first->assertCreated();
        $second->assertCreated();

        $this->assertEquals($first->json('data.payment.reference'), $second->json('data.payment.reference'));
        $this->assertEquals(1, Payment::where('idempotency_key', 'same-key-999')->count());
    }

    public function test_server_side_verification_activates_subscription(): void
    {
        $init = $this->actingAs($this->user)->postJson('/api/v1/payments/initialize', [
            'purpose' => 'subscription',
            'plan_uuid' => $this->monthlyPlan->uuid,
        ]);

        $reference = $init->json('data.payment.reference');

        $verify = $this->actingAs($this->user)->postJson('/api/v1/payments/verify', [
            'reference' => $reference,
        ]);

        $verify->assertOk()
            ->assertJsonPath('data.status', 'successful');

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $this->user->id,
            'subscription_plan_id' => $this->monthlyPlan->id,
            'status' => 'active',
        ]);

        $me = $this->actingAs($this->user)->getJson('/api/v1/subscriptions/me');
        $me->assertOk()
            ->assertJsonPath('subscription.is_premium', true)
            ->assertJsonPath('subscription.plan.name', 'Monthly');
    }

    public function test_subscription_renewal_extends_from_future_end_date(): void
    {
        // 1. Initial 30-day subscription
        $init1 = $this->actingAs($this->user)->postJson('/api/v1/payments/initialize', [
            'purpose' => 'subscription',
            'plan_uuid' => $this->monthlyPlan->uuid,
        ]);
        $this->actingAs($this->user)->postJson('/api/v1/payments/verify', [
            'reference' => $init1->json('data.payment.reference'),
        ]);

        $firstSub = Subscription::where('user_id', $this->user->id)->first();
        $firstEndsAt = $firstSub->ends_at;

        // 2. Renew with yearly plan (365 days)
        $init2 = $this->actingAs($this->user)->postJson('/api/v1/subscriptions/renew', [
            'plan_uuid' => $this->yearlyPlan->uuid,
        ]);
        $this->actingAs($this->user)->postJson('/api/v1/payments/verify', [
            'reference' => $init2->json('data.payment.reference'),
        ]);

        $updatedSub = $firstSub->fresh();
        $this->assertEquals($this->yearlyPlan->id, $updatedSub->subscription_plan_id);
        $this->assertEquals($firstEndsAt->copy()->addDays(365)->toDateString(), $updatedSub->ends_at->toDateString());
    }

    public function test_can_cancel_auto_renew_without_immediately_losing_access(): void
    {
        $init = $this->actingAs($this->user)->postJson('/api/v1/payments/initialize', [
            'purpose' => 'subscription',
            'plan_uuid' => $this->monthlyPlan->uuid,
        ]);
        $this->actingAs($this->user)->postJson('/api/v1/payments/verify', [
            'reference' => $init->json('data.payment.reference'),
        ]);

        $cancel = $this->actingAs($this->user)->postJson('/api/v1/subscriptions/cancel', [
            'reason' => 'Too expensive this month',
        ]);

        $cancel->assertOk()
            ->assertJsonPath('data.auto_renew', false);

        $sub = Subscription::where('user_id', $this->user->id)->first();
        $this->assertFalse($sub->auto_renew);
        $this->assertTrue($sub->grantsPremiumAccess());
    }

    public function test_expired_subscription_preserves_core_security_and_dashcam_access(): void
    {
        $sub = Subscription::create([
            'user_id' => $this->user->id,
            'subscription_plan_id' => $this->monthlyPlan->id,
            'status' => Subscription::STATUS_EXPIRED,
            'starts_at' => now()->subDays(60),
            'ends_at' => now()->subDays(30),
            'grace_ends_at' => now()->subDays(23),
            'auto_renew' => false,
        ]);

        $this->assertFalse($sub->grantsPremiumAccess());

        $me = $this->actingAs($this->user)->getJson('/api/v1/subscriptions/me');
        $me->assertOk()
            ->assertJsonPath('subscription.is_premium', false);

        $entitlements = $me->json('subscription.features');

        // Free core entitlements MUST be present
        $this->assertContains('security.live_location', $entitlements);
        $this->assertContains('security.remote_shutdown', $entitlements);
        $this->assertContains('security.playback', $entitlements);
        $this->assertContains('dashcam.live', $entitlements);
        $this->assertContains('dashcam.playback', $entitlements);

        // Premium-only entitlements MUST NOT be present
        $this->assertNotContains('care.oil_change', $entitlements);
        $this->assertNotContains('care.mileage_insights', $entitlements);
    }

    public function test_can_view_subscription_history(): void
    {
        $init = $this->actingAs($this->user)->postJson('/api/v1/payments/initialize', [
            'purpose' => 'subscription',
            'plan_uuid' => $this->monthlyPlan->uuid,
        ]);
        $this->actingAs($this->user)->postJson('/api/v1/payments/verify', [
            'reference' => $init->json('data.payment.reference'),
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/subscriptions/history');

        $response->assertOk()
            ->assertJsonPath('data.current.status', 'active')
            ->assertJsonCount(1, 'data.history');
    }

    public function test_webhook_triggers_subscription_activation(): void
    {
        $init = $this->actingAs($this->user)->postJson('/api/v1/payments/initialize', [
            'purpose' => 'subscription',
            'plan_uuid' => $this->monthlyPlan->uuid,
        ]);
        $reference = $init->json('data.payment.reference');

        $response = $this->postJson('/api/v1/payments/webhook', [
            'event' => 'charge.success',
            'data' => [
                'reference' => $reference,
                'amount' => 420000, // in kobo
                'currency' => 'NGN',
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);
    }
}
