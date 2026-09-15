<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Explicit, revocable permission for a non-owner user to reach a vehicle.
 */
class VehicleAccessGrant extends BaseModel
{
    protected $fillable = [
        'vehicle_id',
        'user_id',
        'granted_by_user_id',
        'role',
        'can_view_location',
        'can_view_video',
        'can_send_commands',
        'can_manage_care_records',
        'starts_at',
        'expires_at',
        'revoked_at',
        'revoked_by_user_id',
        'revoke_reason',
    ];

    protected function casts(): array
    {
        return [
            'can_view_location' => 'boolean',
            'can_view_video' => 'boolean',
            'can_send_commands' => 'boolean',
            'can_manage_care_records' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
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

    public function isActive(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return $this->starts_at === null || $this->starts_at->isPast();
    }
}
