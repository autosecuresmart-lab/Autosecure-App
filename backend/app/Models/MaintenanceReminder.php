<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceReminder extends BaseModel
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_DUE_SOON = 'due_soon';

    public const STATUS_DUE = 'due';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_DISMISSED = 'dismissed';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'vehicle_id',
        'user_id',
        'maintenance_record_id',
        'category',
        'title',
        'due_at',
        'due_odometer_km',
        'status',
        'notified_at',
        'dismissed_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'date',
            'due_odometer_km' => 'integer',
            'notified_at' => 'datetime',
            'dismissed_at' => 'datetime',
            'completed_at' => 'datetime',
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

    public function maintenanceRecord(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRecord::class);
    }

    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_DUE_SOON,
            self::STATUS_DUE,
            self::STATUS_OVERDUE,
        ]);
    }
}
