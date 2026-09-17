<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CoinTransaction;
use App\Models\SubscriptionPlan;
use App\Services\Payments\PaymentService;
use App\Services\Subscriptions\EntitlementService;
use App\Services\Subscriptions\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Subscriptions + Coins endpoints.
 *
 * Entitlements are resolved server side here and again by the `entitlement`
 * middleware — the client never decides what it is allowed to use.
 */
class SubscriptionController extends Controller
{
    public function __construct(
        protected EntitlementService $entitlements,
        protected SubscriptionService $subscriptionService,
        protected PaymentService $paymentService,
    ) {}

    public function plans(): JsonResponse
    {
        $plans = SubscriptionPlan::query()
            ->active()
            ->with('features')
            ->get()
            ->map(fn (SubscriptionPlan $plan) => [
                'uuid' => $plan->uuid,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'price' => (float) $plan->price,
                'base_price' => $plan->base_price !== null ? (float) $plan->base_price : null,
                'currency' => $plan->currency,
                'interval' => $plan->interval,
                'duration_days' => $plan->duration_days,
                'discount_percent' => (float) $plan->discount_percent,
                'is_free' => (bool) $plan->is_free,
                'features' => $plan->features->pluck('feature_key'),
            ]);

        return response()->json(['plans' => $plans]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'subscription' => $this->entitlements->summaryFor($request->user()),
        ]);
    }

    /**
     * Subscription history and receipt trail.
     * GET /api/v1/subscriptions/history
     */
    public function history(Request $request): JsonResponse
    {
        $data = $this->subscriptionService->getUserHistory($request->user());

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Cancel auto-renew on active subscription.
     * POST /api/v1/subscriptions/cancel
     */
    public function cancel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $subscription = $this->subscriptionService->cancelAutoRenew($request->user(), $validated['reason'] ?? null);

        return response()->json([
            'message' => 'Subscription auto-renew cancelled.',
            'data' => [
                'uuid' => $subscription->uuid,
                'status' => $subscription->status,
                'auto_renew' => (bool) $subscription->auto_renew,
                'cancelled_at' => $subscription->cancelled_at?->toIso8601String(),
                'ends_at' => $subscription->ends_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Initiate subscription renewal or upgrade checkout.
     * POST /api/v1/subscriptions/renew
     */
    public function renew(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_uuid' => ['required', 'string', 'exists:subscription_plans,uuid'],
            'channel' => ['nullable', 'string', 'in:card,bank_transfer,ussd,qr,wallet'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
            'callback_url' => ['nullable', 'string'],
        ]);

        $result = $this->paymentService->initialize($request->user(), array_merge($validated, [
            'purpose' => \App\Models\Payment::PURPOSE_SUBSCRIPTION,
        ]));

        return response()->json([
            'message' => 'Subscription payment initialized.',
            'data' => $result,
        ], 201);
    }

    /**
     * Coins wallet + recent ledger entries.
     */
    public function wallet(Request $request): JsonResponse
    {
        $user = $request->user();

        $wallet = $user->coinWallet()->firstOrCreate([]);

        $transactions = CoinTransaction::query()
            ->where('coin_wallet_id', $wallet->getKey())
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (CoinTransaction $tx) => [
                'uuid' => $tx->uuid,
                'type' => $tx->type,
                'coins' => $tx->coins,
                'balance_after' => $tx->balance_after,
                'description' => $tx->description,
                'expires_at' => $tx->expires_at?->toIso8601String(),
                'created_at' => $tx->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'wallet' => [
                'uuid' => $wallet->uuid,
                'balance' => $wallet->availableBalance(),
                'pending_balance' => $wallet->pending_balance,
                'lifetime_earned' => $wallet->lifetime_earned,
                'lifetime_redeemed' => $wallet->lifetime_redeemed,
                'lifetime_expired' => $wallet->lifetime_expired,
            ],
            'redemption_rules' => config('autosecure.coins'),
            'transactions' => $transactions,
        ]);
    }
}
