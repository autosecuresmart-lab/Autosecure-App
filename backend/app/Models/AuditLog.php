<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Append-only audit trail row.
 */
class AuditLog extends BaseModel
{
    public const SEVERITY_INFO = 'info';

    public const SEVERITY_NOTICE = 'notice';

    public const SEVERITY_WARNING = 'warning';

    public const SEVERITY_CRITICAL = 'critical';

    protected $fillable = [
        'actor_type',
        'actor_id',
        'actor_label',
        'auditable_type',
        'auditable_id',
        'action',
        'group',
        'description',
        'changes',
        'context',
        'ip_address',
        'user_agent',
        'severity',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'context' => 'array',
        ];
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
