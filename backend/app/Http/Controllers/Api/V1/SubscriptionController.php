<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CoinTransaction;
use App\Models\SubscriptionPlan;
use App\Services\Subscriptions\EntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Subscriptions + Coins read endpoints.
 *
 * Entitlements are resolved server side here and again by the `entitlement`
 * middleware — the client never decides what it is allowed to use.
 */
class SubscriptionController extends Controller
{
    public function __construct(protected EntitlementService $entitlements) {}

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
