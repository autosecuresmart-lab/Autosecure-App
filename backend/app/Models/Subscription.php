<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A customer's subscription state.
 *
 * Entitlements are always resolved from here on the server. Lapsing to `expired`
 * removes premium modules only — the free security/dashcam entitlements remain.
 */
class Subscription extends BaseModel
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_GRACE = 'grace';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_FAILED = 'failed';

    /** Statuses that still grant premium entitlements. */
    public const STATUSES_WITH_ACCESS = [self::STATUS_ACTIVE, self::STATUS_GRACE];

    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'status',
        'starts_at',
        'ends_at',
        'grace_ends_at',
        'cancelled_at',
        'renewed_at',
        'auto_renew',
        'failed_payment_attempts',
        'last_payment_id',
        'source',
        'cancellation_reason',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'grace_ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'renewed_at' => 'datetime',
            'auto_renew' => 'boolean',
            'failed_payment_attempts' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function lastPayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'last_payment_id');
    }

    public function payments(): Builder
    {
        return Payment::query()->where('subscription_id', $this->id);
    }

    public function grantsPremiumAccess(): bool
    {
        if (! in_array($this->status, self::STATUSES_WITH_ACCESS, true)) {
            return false;
        }

        $until = $this->status === self::STATUS_GRACE
            ? ($this->grace_ends_at ?? $this->ends_at)
            : $this->ends_at;

        return $until === null || $until->isFuture();
    }

    public function isOnFreeTier(): bool
    {
        return ! $this->grantsPremiumAccess();
    }

    public function scopeWithAccess(Builder $query): Builder
    {
        return $query->whereIn('status', self::STATUSES_WITH_ACCESS);
    }
}
