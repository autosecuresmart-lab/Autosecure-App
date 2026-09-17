<?php

namespace App\Services\Subscriptions;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    public function __construct(
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * Activate or renew a subscription from a verified successful payment.
     */
    public function activateFromPayment(Payment $payment): Subscription
    {
        return DB::transaction(function () use ($payment) {
            $user = $payment->user;
            $planId = $payment->payload['subscription_plan_id'] ?? null;
            $plan = $planId ? SubscriptionPlan::find($planId) : null;

            if (! $plan && $payment->subscription_id) {
                $plan = $payment->subscription?->plan;
            }

            if (! $plan) {
                throw new \RuntimeException('Subscription plan could not be determined for payment '.$payment->reference);
            }

            // Find existing user subscription (excluding free plan placeholder)
            $subscription = Subscription::query()
                ->where('user_id', $user->id)
                ->latest('id')
                ->first();

            $now = now();
            $startsAt = $now;

            // If user currently has an active subscription that hasn't expired yet, extend it
            if ($subscription && $subscription->status === Subscription::STATUS_ACTIVE && $subscription->ends_at && $subscription->ends_at->isFuture()) {
                $startsAt = $subscription->ends_at;
            }

            $durationDays = max(1, (int) $plan->duration_days);
            $endsAt = $startsAt->copy()->addDays($durationDays);
            $graceEndsAt = $endsAt->copy()->addDays(config('autosecure.subscription.grace_period_days', 7));

            if (! $subscription) {
                $subscription = new Subscription([
                    'user_id' => $user->id,
                ]);
            }

            $isRenewal = $subscription->exists && $subscription->status === Subscription::STATUS_ACTIVE;

            $subscription->fill([
                'subscription_plan_id' => $plan->id,
                'status' => Subscription::STATUS_ACTIVE,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'grace_ends_at' => $graceEndsAt,
                'renewed_at' => $now,
                'cancelled_at' => null,
                'cancellation_reason' => null,
                'auto_renew' => true,
                'failed_payment_attempts' => 0,
                'last_payment_id' => $payment->id,
                'source' => 'app',
                'metadata' => array_merge($subscription->metadata ?? [], [
                    'last_plan_slug' => $plan->slug,
                    'last_payment_ref' => $payment->reference,
                    'last_amount' => (float) $payment->amount,
                ]),
            ]);

            $subscription->save();

            // Link payment to subscription
            $payment->update(['subscription_id' => $subscription->id]);

            // Audit log
            $this->auditLogger->log(
                action: $isRenewal ? 'subscription.renewed' : 'subscription.activated',
                auditable: $subscription,
                actor: $user,
                context: [
                    'plan' => $plan->slug,
                    'amount' => (float) $payment->amount,
                    'reference' => $payment->reference,
                    'ends_at' => $endsAt->toIso8601String(),
                ]
            );

            return $subscription->fresh(['plan.features', 'lastPayment']);
        });
    }

    /**
     * Cancel auto-renew for a user subscription.
     */
    public function cancelAutoRenew(User $user, ?string $reason = null): Subscription
    {
        $subscription = Subscription::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_GRACE])
            ->latest('id')
            ->first();

        if (! $subscription) {
            throw ValidationException::withMessages([
                'subscription' => ['No active subscription found to cancel.'],
            ]);
        }

        $subscription->update([
            'auto_renew' => false,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason ?? 'Cancelled by user',
        ]);

        $this->auditLogger->log(
            action: 'subscription.cancelled',
            auditable: $subscription,
            actor: $user,
            context: [
                'reason' => $reason,
                'ends_at' => $subscription->ends_at?->toIso8601String(),
            ]
        );

        return $subscription->fresh(['plan.features', 'lastPayment']);
    }

    /**
     * Expire a subscription that has lapsed past its grace period.
     */
    public function expireSubscription(Subscription $subscription): Subscription
    {
        $subscription->update([
            'status' => Subscription::STATUS_EXPIRED,
        ]);

        $this->auditLogger->log(
            action: 'subscription.expired',
            auditable: $subscription,
            actor: null,
            context: [
                'ended_at' => $subscription->ends_at?->toIso8601String(),
            ]
        );

        return $subscription;
    }

    /**
     * Sweep through expired subscriptions. Core security & dashcam are untouched.
     */
    public function checkAndExpireLapsedSubscriptions(): int
    {
        $now = now();
        $expiredCount = 0;

        $dueSubscriptions = Subscription::query()
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_GRACE])
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', $now)
            ->get();

        foreach ($dueSubscriptions as $sub) {
            $graceCutoff = $sub->grace_ends_at ?? $sub->ends_at;

            if ($now->greaterThan($graceCutoff)) {
                $this->expireSubscription($sub);
                $expiredCount++;
            } elseif ($sub->status === Subscription::STATUS_ACTIVE) {
                // Move to grace period
                $sub->update(['status' => Subscription::STATUS_GRACE]);
            }
        }

        return $expiredCount;
    }

    /**
     * Get user's subscription history.
     */
    public function getUserHistory(User $user): array
    {
        $currentSubscription = Subscription::query()
            ->where('user_id', $user->id)
            ->with(['plan.features', 'lastPayment'])
            ->latest('id')
            ->first();

        $paymentHistory = Payment::query()
            ->where('user_id', $user->id)
            ->where('purpose', Payment::PURPOSE_SUBSCRIPTION)
            ->with('subscription.plan')
            ->latest()
            ->get()
            ->map(fn (Payment $p) => [
                'uuid' => $p->uuid,
                'reference' => $p->reference,
                'amount' => (float) $p->amount,
                'currency' => $p->currency,
                'status' => $p->status,
                'channel' => $p->channel,
                'paid_at' => $p->paid_at?->toIso8601String(),
                'plan_name' => $p->payload['plan_name'] ?? $p->subscription?->plan?->name ?? 'Premium Plan',
                'duration_days' => $p->payload['duration_days'] ?? $p->subscription?->plan?->duration_days ?? 30,
            ]);

        return [
            'current' => $currentSubscription ? [
                'uuid' => $currentSubscription->uuid,
                'status' => $currentSubscription->status,
                'plan_name' => $currentSubscription->plan?->name,
                'plan_slug' => $currentSubscription->plan?->slug,
                'price' => (float) ($currentSubscription->plan?->price ?? 0),
                'starts_at' => $currentSubscription->starts_at?->toIso8601String(),
                'ends_at' => $currentSubscription->ends_at?->toIso8601String(),
                'grace_ends_at' => $currentSubscription->grace_ends_at?->toIso8601String(),
                'auto_renew' => (bool) $currentSubscription->auto_renew,
                'has_access' => $currentSubscription->grantsPremiumAccess(),
            ] : null,
            'history' => $paymentHistory,
        ];
    }
}
