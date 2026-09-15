<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FuelRecord extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'vehicle_id',
        'user_id',
        'filled_at',
        'litres',
        'price_per_litre',
        'total_amount',
        'odometer_km',
        'is_full_tank',
        'station',
        'receipt_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'filled_at' => 'date',
            'litres' => 'decimal:2',
            'price_per_litre' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'odometer_km' => 'integer',
            'is_full_tank' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
