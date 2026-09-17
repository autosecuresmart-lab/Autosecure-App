<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Booking extends BaseModel
{
    use SoftDeletes;

    public const TYPE_BOOKING = 'booking';

    public const TYPE_ORDER = 'order';

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_DISPUTED = 'disputed';

    public const STATUS_REFUNDED = 'refunded';

    public const PAYMENT_UNPAID = 'unpaid';

    public const PAYMENT_PENDING = 'pending';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_PARTIALLY_REFUNDED = 'partially_refunded';

    public const PAYMENT_REFUNDED = 'refunded';

    protected $fillable = [
        'reference',
        'user_id',
        'vehicle_id',
        'vendor_id',
        'type',
        'status',
        'payment_status',
        'fulfilment',
        'scheduled_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
        'subtotal',
        'discount',
        'coins_redeemed',
        'commission_percent',
        'commission_amount',
        'total',
        'currency',
        'customer_note',
        'vendor_note',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'coins_redeemed' => 'decimal:2',
            'commission_percent' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'metadata' => 'array',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Human-readable booking reference, e.g. AS-2K4J7QX9.
     */
    public static function generateReference(): string
    {
        do {
            $reference = 'AS-'.Str::upper(Str::random(8));
        } while (static::withTrashed()->where('reference', $reference)->exists());

        return $reference;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    public function settlement()
    {
        return $this->hasOne(VendorSettlement::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(VendorSettlement::class);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED]);
    }

    /**
     * Coins may only be awarded once a booking is completed and paid.
     */
    public function qualifiesForCoins(): bool
    {
        return $this->status === self::STATUS_COMPLETED
            && $this->payment_status === self::PAYMENT_PAID;
    }
}
