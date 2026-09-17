<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends BaseModel
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'owner_user_id',
        'vendor_category_id',
        'business_name',
        'trading_name',
        'slug',
        'email',
        'phone',
        'whatsapp',
        'description',
        'logo_path',
        'address_line',
        'city',
        'state',
        'country',
        'latitude',
        'longitude',
        'service_radius_km',
        'opening_hours',
        'cancellation_policy',
        'status',
        'is_publicly_visible',
        'verified_at',
        'verified_by_admin_id',
        'commission_percent',
        'subscription_fee',
        'subscription_starts_at',
        'subscription_expires_at',
        'rating_average',
        'rating_count',
        'settlement_details',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'service_radius_km' => 'integer',
            'opening_hours' => 'array',
            'commission_percent' => 'decimal:2',
            'subscription_fee' => 'decimal:2',
            'settlement_details' => 'array',
            'metadata' => 'array',
            'is_publicly_visible' => 'boolean',
            'verified_at' => 'datetime',
            'subscription_starts_at' => 'datetime',
            'subscription_expires_at' => 'datetime',
            'rating_average' => 'decimal:2',
            'rating_count' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(VendorCategory::class, 'vendor_category_id');
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(VendorVerification::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(VendorService::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(VendorSettlement::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'verified_by_admin_id');
    }

    /**
     * Only verified vendors with a live subscription may be listed or booked.
     */
    public function isBookable(): bool
    {
        if ($this->status !== self::STATUS_VERIFIED || ! $this->is_publicly_visible) {
            return false;
        }

        return $this->subscription_expires_at === null || $this->subscription_expires_at->isFuture();
    }

    public function recalculateRating(): void
    {
        $stats = $this->reviews()
            ->where('is_published', true)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total_reviews')
            ->first();

        $this->updateQuietly([
            'rating_average' => round((float) ($stats->avg_rating ?? 0), 2),
            'rating_count' => (int) ($stats->total_reviews ?? 0),
        ]);
    }

    public function effectiveCommissionPercent(): float
    {
        return (float) ($this->commission_percent
            ?? $this->category?->effectiveCommissionPercent()
            ?? config('autosecure.finder.default_commission_percent'));
    }

    /**
     * Scope to vendors the customer may actually see in Finder.
     */
    public function scopePubliclyListed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_VERIFIED)
            ->where('is_publicly_visible', true)
            ->where(function (Builder $q) {
                $q->whereNull('subscription_expires_at')
                    ->orWhere('subscription_expires_at', '>', now());
            });
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('business_name', 'like', "%{$term}%")
                ->orWhere('trading_name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('city', 'like', "%{$term}%")
                ->orWhere('state', 'like', "%{$term}%")
                ->orWhereHas('services', function (Builder $sq) use ($term) {
                    $sq->where('name', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                });
        });
    }
}
