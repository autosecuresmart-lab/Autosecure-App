<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorCategory extends BaseModel
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'sort_order',
        'commission_percent',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'commission_percent' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(VendorService::class);
    }

    /**
     * Category override, otherwise the platform default commission.
     */
    public function effectiveCommissionPercent(): float
    {
        return (float) ($this->commission_percent
            ?? config('autosecure.finder.default_commission_percent'));
    }
}
