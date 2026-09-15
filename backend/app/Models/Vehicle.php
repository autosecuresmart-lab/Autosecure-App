<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'nickname',
        'plate_number',
        'make',
        'model',
        'year',
        'colour',
        'vin',
        'fuel_type',
        'transmission',
        'image_path',
        'odometer_km',
        'odometer_source',
        'is_primary',
        'status',
        'autodoc_vehicle_ref',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'odometer_km' => 'integer',
            'is_primary' => 'boolean',
            'odometer_updated_at' => 'datetime',
            'metadata' => 'array',
            'deleted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function tracker(): HasMany
    {
        return $this->devices()->where('type', 'tracker');
    }

    public function dashcams(): HasMany
    {
        return $this->devices()->where('type', 'dashcam');
    }

    public function accessGrants(): HasMany
    {
        return $this->hasMany(VehicleAccessGrant::class);
    }

    /**
     * Every user who can currently see this vehicle (owner included).
     */
    public function authorisedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'vehicle_access_grants')
            ->withPivot([
                'uuid', 'role', 'can_view_location', 'can_view_video',
                'can_send_commands', 'can_manage_care_records',
                'expires_at', 'revoked_at',
            ])
            ->withTimestamps();
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class);
    }

    public function fuelRecords(): HasMany
    {
        return $this->hasMany(FuelRecord::class);
    }

    public function theftEvents(): HasMany
    {
        return $this->hasMany(TheftEvent::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(DevicePosition::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function displayName(): string
    {
        return $this->nickname
            ?: trim(implode(' ', array_filter([$this->make, $this->model]))) ?: $this->plate_number;
    }
}
