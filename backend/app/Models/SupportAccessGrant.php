<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Time-boxed, justified grant that lets a support agent reach a customer's
 * sensitive data (location, video, voice, documents, commands).
 *
 * Product rule: sensitive access is never implicit. Every use increments
 * `access_count` and writes an audit log entry.
 */
class SupportAccessGrant extends BaseModel
{
    public const SCOPE_LOCATION = 'location';

    public const SCOPE_VIDEO = 'video';

    public const SCOPE_VOICE = 'voice';

    public const SCOPE_DOCUMENTS = 'documents';

    public const SCOPE_COMMANDS = 'commands';

    protected $fillable = [
        'admin_id',
        'user_id',
        'vehicle_id',
        'approved_by_admin_id',
        'reason',
        'reference',
        'scopes',
        'starts_at',
        'expires_at',
        'revoked_at',
        'revoked_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'access_count' => 'integer',
            'last_accessed_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by_admin_id');
    }

    public function isActive(): bool
    {
        if ($this->revoked_at !== null || $this->expires_at->isPast()) {
            return false;
        }

        return $this->starts_at === null || $this->starts_at->isPast();
    }

    public function covers(string $scope): bool
    {
        return $this->isActive() && in_array($scope, $this->scopes ?? [], true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()));
    }
}
