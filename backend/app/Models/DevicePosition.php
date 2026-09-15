<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single location fix. High volume, append-only.
 */
class DevicePosition extends BaseModel
{
    public const SOURCE_GPS = 'gps';

    public const SOURCE_LBS = 'lbs';

    public const SOURCE_WIFI = 'wifi';

    public const SOURCE_GPS_LBS = 'gps_lbs';

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_UNKNOWN = 'unknown';

    protected $fillable = [
        'device_id',
        'vehicle_id',
        'latitude',
        'longitude',
        'speed_kph',
        'heading',
        'altitude_m',
        'accuracy_m',
        'ignition',
        'moving',
        'source',
        'recorded_at',
        'raw',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'speed_kph' => 'decimal:2',
            'altitude_m' => 'decimal:2',
            'accuracy_m' => 'decimal:2',
            'heading' => 'integer',
            'ignition' => 'boolean',
            'moving' => 'boolean',
            'recorded_at' => 'datetime',
            'raw' => 'array',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
