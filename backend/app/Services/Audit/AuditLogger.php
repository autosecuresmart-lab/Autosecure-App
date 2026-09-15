<?php

namespace App\Services\Audit;

use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Single entry point for writing the audit trail.
 *
 * Used for customer actions, staff actions, device commands and every access to
 * sensitive data (location, video, voice, documents).
 */
class AuditLogger
{
    public function __construct(protected ?Request $request = null) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function log(
        string $action,
        ?Model $auditable = null,
        ?Model $actor = null,
        array $changes = [],
        array $context = [],
        string $severity = AuditLog::SEVERITY_INFO,
        ?string $group = null,
    ): AuditLog {
        $actor ??= $this->currentActor();
        $request = $this->request ?? request();

        return AuditLog::create([
            'actor_type' => $actor ? $actor->getMorphClass() : null,
            'actor_id' => $actor?->getKey(),
            'actor_label' => $this->actorLabel($actor),
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'action' => $action,
            'group' => $group ?? $this->groupFor($action),
            'description' => $context['description'] ?? null,
            'changes' => $changes ?: null,
            'context' => $context ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => substr((string) $request?->userAgent(), 0, 512) ?: null,
            'severity' => $severity,
        ]);
    }

    /**
     * Records access to sensitive data. Always at `notice` severity.
     *
     * @param  array<string, mixed>  $context
     */
    public function sensitive(string $action, ?Model $auditable = null, array $context = []): AuditLog
    {
        return $this->log(
            action: $action,
            auditable: $auditable,
            context: $context,
            severity: AuditLog::SEVERITY_NOTICE,
            group: 'sensitive_access',
        );
    }

    protected function currentActor(): ?Model
    {
        foreach (['admin', 'web', 'sanctum'] as $guard) {
            $user = Auth::guard($guard)->user();

            if ($user instanceof Model) {
                return $user;
            }
        }

        return null;
    }

    protected function actorLabel(?Model $actor): ?string
    {
        return match (true) {
            $actor instanceof Admin => 'admin:'.$actor->email,
            $actor instanceof User => 'user:'.$actor->email,
            $actor === null => 'system',
            default => $actor->getMorphClass().':'.$actor->getKey(),
        };
    }

    protected function groupFor(string $action): string
    {
        return str_contains($action, '.') ? explode('.', $action)[0] : 'general';
    }
}
