<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Coins wallet. `balance`/`pending_balance` are cached totals that must always
 * reconcile with the `coin_transactions` ledger.
 */
class CoinWallet extends BaseModel
{
    protected $fillable = [
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'integer',
            'pending_balance' => 'integer',
            'lifetime_earned' => 'integer',
            'lifetime_redeemed' => 'integer',
            'lifetime_expired' => 'integer',
            'is_locked' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CoinTransaction::class);
    }

    /**
     * Coins the customer can actually spend right now.
     */
    public function availableBalance(): int
    {
        return max(0, $this->balance);
    }
}
