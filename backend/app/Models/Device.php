<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Device extends BaseModel
{
    use SoftDeletes;

    public const TYPE_TRACKER = 'tracker';

    public const TYPE_DASHCAM = 'dashcam';

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_OFFLINE = 'offline';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_FAULTY = 'faulty';

    public const STATUS_UNBOUND = 'unbound';

    protected $fillable = [
        'type',
        'vehicle_id',
        'user_id',
        'provider',
        'external_id',
        'serial_number',
        'label',
        'imei',
        'sim_number',
        'phone_number',
        'brand',
        'model',
        'firmware_version',
        'status',
        'capabilities',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_online' => 'boolean',
            'bound_at' => 'datetime',
            'unbound_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'last_known_latitude' => 'decimal:7',
            'last_known_longitude' => 'decimal:7',
            'capabilities' => 'array',
            'metadata' => 'array',
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

    public function commands(): HasMany
    {
        return $this->hasMany(DeviceCommand::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(DevicePosition::class);
    }

    public function isTracker(): bool
    {
        return $this->type === self::TYPE_TRACKER;
    }

    public function isDashcam(): bool
    {
        return $this->type === self::TYPE_DASHCAM;
    }

    /**
     * A human label for the device, falling back to brand/model then serial.
     */
    public function displayName(): string
    {
        return $this->label
            ?: trim(implode(' ', array_filter([$this->brand, $this->model]))) ?: $this->serial_number;
    }

    /**
     * A device is the customer's own, or bound to a vehicle they own or hold an
     * active grant for. Used by the API to scope listings server side.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $userId = $user->getKey();

        return $query->where(function (Builder $q) use ($userId) {
            $q->where('user_id', $userId)
                ->orWhereIn('vehicle_id', function ($sub) use ($userId) {
                    $sub->select('id')->from('vehicles')->where('user_id', $userId);
                })
                ->orWhereIn('vehicle_id', function ($sub) use ($userId) {
                    $sub->select('vehicle_id')
                        ->from('vehicle_access_grants')
                        ->where('user_id', $userId)
                        ->whereNull('revoked_at')
                        ->where(fn ($inner) => $inner->whereNull('expires_at')->orWhere('expires_at', '>', now()));
                });
        });
    }
}
