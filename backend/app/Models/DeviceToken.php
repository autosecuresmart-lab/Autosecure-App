<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Push token for a signed-in customer handset.
 */
class DeviceToken extends BaseModel
{
    protected $fillable = [
        'user_id',
        'token',
        'platform',
        'device_name',
        'device_id',
        'app_version',
        'push_provider',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
