<?php

namespace App\Services\Subscriptions;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Resolves what a customer is allowed to use.
 *
 * Entitlements are ALWAYS resolved server side (product requirement). The core
 * security and dashcam features are part of the free tier and are therefore
 * granted even when a subscription has lapsed.
 */
class EntitlementService
{
    /**
     * Feature keys every customer holds regardless of subscription state.
     *
     * @return array<int, string>
     */
    public function freeFeatures(): array
    {
        return config('autosecure.entitlements.free', []);
    }

    /**
     * Feature keys that require an active (or in-grace) premium subscription.
     *
     * @return array<int, string>
     */
    public function premiumFeatures(): array
    {
        return config('autosecure.entitlements.premium', []);
    }

    public function isPremium(User $user): bool
    {
        return $user->activeSubscription?->grantsPremiumAccess() ?? false;
    }

    /**
     * The full set of entitlements currently held by the customer.
     *
     * @return Collection<int, string>
     */
    public function featuresFor(User $user): Collection
    {
        $features = collect($this->freeFeatures());

        $subscription = $user->activeSubscription;

        if ($subscription?->grantsPremiumAccess()) {
            $planFeatures = $subscription->plan?->featureKeys() ?? [];

            $features = $features->merge($planFeatures ?: $this->premiumFeatures());
        }

        return $features->unique()->values();
    }

    public function can(User $user, string $feature): bool
    {
        return $this->featuresFor($user)->contains($feature);
    }

    /**
     * A feature that is always available, even on the free tier.
     */
    public function isCore(string $feature): bool
    {
        return in_array($feature, $this->freeFeatures(), true);
    }

    /**
     * Snapshot for the mobile app (e.g. GET /api/v1/subscriptions/me).
     *
     * @return array<string, mixed>
     */
    public function summaryFor(User $user): array
    {
        /** @var Subscription|null $subscription */
        $subscription = $user->activeSubscription;

        return [
            'is_premium' => $this->isPremium($user),
            'status' => $subscription?->status ?? Subscription::STATUS_EXPIRED,
            'plan' => $subscription?->plan?->only(['uuid', 'name', 'slug', 'interval', 'price', 'currency']),
            'starts_at' => $subscription?->starts_at?->toIso8601String(),
            'ends_at' => $subscription?->ends_at?->toIso8601String(),
            'grace_ends_at' => $subscription?->grace_ends_at?->toIso8601String(),
            'auto_renew' => (bool) $subscription?->auto_renew,
            'features' => $this->featuresFor($user)->all(),
        ];
    }
}
