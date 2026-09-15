<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Theft Trigger event record (proposal section 04, steps 1-6).
 */
class TheftEvent extends BaseModel
{
    public const STATUS_OPEN = 'open';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_FALSE_ALARM = 'false_alarm';

    protected $fillable = [
        'vehicle_id',
        'user_id',
        'device_id',
        'status',
        'severity',
        'pin_verified',
        'biometric_verified',
        'triggered_at',
        'acknowledged_at',
        'resolved_at',
        'resolved_by_user_id',
        'resolved_by_admin_id',
        'resolution_note',
        'last_known_latitude',
        'last_known_longitude',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'pin_verified' => 'boolean',
            'biometric_verified' => 'boolean',
            'triggered_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
            'last_known_latitude' => 'decimal:7',
            'last_known_longitude' => 'decimal:7',
            'metadata' => 'array',
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

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function commands(): HasMany
    {
        return $this->hasMany(DeviceCommand::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_ACKNOWLEDGED], true);
    }
}
