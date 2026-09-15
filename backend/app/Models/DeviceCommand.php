<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A command issued to a device and its acknowledgement lifecycle.
 *
 * SECURITY: `status` may only become `acknowledged` from a device/provider
 * response. Nothing in the platform may mark a command successful optimistically
 * — Remote Shutdown in particular must never be shown as done until confirmed.
 */
class DeviceCommand extends BaseModel
{
    public const TYPE_LOCATE = 'locate';

    public const TYPE_REMOTE_SHUTDOWN = 'remote_shutdown';

    public const TYPE_RESTORE = 'restore';

    public const TYPE_CALL_VEHICLE = 'call_vehicle';

    public const TYPE_THEFT_ALERT = 'theft_alert';

    public const TYPE_RESTART = 'restart';

    public const TYPE_SNAPSHOT = 'snapshot';

    public const TYPE_SYNC_SETTINGS = 'sync_settings';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const STATUS_FAILED = 'failed';

    public const STATUS_TIMEOUT = 'timeout';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Commands that change the physical state of the vehicle and therefore
     * require an explicit user confirmation plus a device acknowledgement.
     */
    public const DESTRUCTIVE_TYPES = [
        self::TYPE_REMOTE_SHUTDOWN,
        self::TYPE_RESTORE,
        self::TYPE_THEFT_ALERT,
    ];

    protected $fillable = [
        'device_id',
        'vehicle_id',
        'user_id',
        'admin_id',
        'theft_event_id',
        'type',
        'status',
        'payload',
        'response',
        'failure_reason',
        'provider_reference',
        'idempotency_key',
        'attempts',
        'confirmed_by_user',
        'requested_at',
        'sent_at',
        'acknowledged_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'response' => 'array',
            'attempts' => 'integer',
            'confirmed_by_user' => 'boolean',
            'requested_at' => 'datetime',
            'sent_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'expires_at' => 'datetime',
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

    public function theftEvent(): BelongsTo
    {
        return $this->belongsTo(TheftEvent::class);
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function requestedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function isDestructive(): bool
    {
        return in_array($this->type, self::DESTRUCTIVE_TYPES, true);
    }

    /**
     * Only a confirmed device response may move a command to `acknowledged`.
     */
    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_ACKNOWLEDGED && $this->acknowledged_at !== null;
    }

    public function isAwaitingDevice(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_SENT], true);
    }
}
