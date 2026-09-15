<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A money movement. Created once per intent and then updated by gateway
 * webhooks; `idempotency_key` is unique so retries cannot double-charge.
 */
class Payment extends BaseModel
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SUCCESSFUL = 'successful';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REVERSED = 'reversed';

    public const STATUS_REFUNDED = 'refunded';

    public const PURPOSE_SUBSCRIPTION = 'subscription';

    public const PURPOSE_BOOKING = 'booking';

    public const PURPOSE_ORDER = 'order';

    public const PURPOSE_VENDOR_SUBSCRIPTION = 'vendor_subscription';

    protected $fillable = [
        'reference',
        'user_id',
        'purpose',
        'booking_id',
        'subscription_id',
        'amount',
        'currency',
        'status',
        'channel',
        'gateway',
        'gateway_reference',
        'idempotency_key',
        'paid_at',
        'failed_at',
        'failure_reason',
        'payload',
        'initiated_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'PAY-'.Str::upper(Str::random(12));
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESSFUL;
    }
}
