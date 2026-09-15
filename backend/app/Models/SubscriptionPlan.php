<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends BaseModel
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'base_price',
        'currency',
        'interval',
        'duration_days',
        'discount_percent',
        'is_free',
        'is_active',
        'is_default',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'base_price' => 'decimal:2',
            'duration_days' => 'integer',
            'discount_percent' => 'decimal:2',
            'is_free' => 'boolean',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function features(): HasMany
    {
        return $this->hasMany(SubscriptionPlanFeature::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function featureKeys(): array
    {
        return $this->features()->pluck('feature_key')->all();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
