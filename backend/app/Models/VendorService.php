<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorService extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'vendor_category_id',
        'name',
        'slug',
        'description',
        'type',
        'price',
        'currency',
        'duration_minutes',
        'stock_quantity',
        'image_path',
        'is_active',
        'is_bookable',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'duration_minutes' => 'integer',
            'stock_quantity' => 'integer',
            'is_active' => 'boolean',
            'is_bookable' => 'boolean',
            'metadata' => 'array',
            'deleted_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(VendorCategory::class, 'vendor_category_id');
    }

    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }
}
