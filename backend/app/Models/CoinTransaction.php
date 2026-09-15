<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Append-only Coins ledger entry.
 *
 * Never update or delete a row: corrections are made with a compensating
 * `reverse` entry so the audit trail stays intact.
 */
class CoinTransaction extends BaseModel
{
    public const TYPE_EARN = 'earn';

    public const TYPE_PENDING = 'pending';

    public const TYPE_REDEEM = 'redeem';

    public const TYPE_REVERSE = 'reverse';

    public const TYPE_EXPIRE = 'expire';

    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'coin_wallet_id',
        'user_id',
        'type',
        'coins',
        'balance_after',
        'description',
        'source_type',
        'source_id',
        'booking_id',
        'payment_id',
        'admin_id',
        'idempotency_key',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'coins' => 'integer',
            'balance_after' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(CoinWallet::class, 'coin_wallet_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
