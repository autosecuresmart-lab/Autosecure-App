<?php

namespace App\Exceptions;

use App\Support\PendingIntegrations;
use RuntimeException;

/**
 * Raised when code reaches a provider integration that has not been supplied yet.
 *
 * This is deliberately a real exception rather than a silently returning null:
 * a tracker command or dashcam stream that cannot be performed must fail loudly,
 * and the HTTP layer renders it as the standard `501 integration_pending` body.
 */
class IntegrationPendingException extends RuntimeException
{
    public function __construct(
        public readonly string $module,
        public readonly string $operation = '',
        ?string $message = null,
    ) {
        parent::__construct(
            $message ?? static::describe($module, $operation),
        );
    }

    public static function for(string $module, string $operation): self
    {
        return new self($module, $operation);
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return PendingIntegrations::payload($this->module) + [
            'operation' => $this->operation,
        ];
    }

    protected static function describe(string $module, string $operation): string
    {
        $title = PendingIntegrations::title($module);

        return $operation === ''
            ? "The {$title} integration is not available yet: the required provider documentation has not been supplied."
            : "Cannot perform '{$operation}': the {$title} integration is not available yet. The required provider documentation has not been supplied.";
    }
}
