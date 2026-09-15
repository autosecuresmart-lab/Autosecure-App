<?php

namespace App\Support\Devices;

use DateTimeImmutable;

/**
 * The result of sending a command to a device.
 *
 * SECURITY-CRITICAL. The AUTOSECURE 2.0 proposal requires that an action is
 * "not presented as successful until the platform receives a confirmed response",
 * so `acknowledged` is a separate fact from `accepted`:
 *
 *   accepted      — the provider/platform took the request
 *   acknowledged  — the DEVICE reported back
 *
 * `isConfirmed()` requires both, plus a real acknowledgement timestamp. A provider
 * integration must never construct a "success" without device evidence.
 */
final readonly class CommandResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public bool $accepted,
        public bool $acknowledged = false,
        public ?DateTimeImmutable $acknowledgedAt = null,
        public ?string $providerReference = null,
        public ?string $message = null,
        public ?string $failureReason = null,
        public array $raw = [],
    ) {}

    /**
     * The request was taken, but the device has not confirmed yet.
     */
    public static function accepted(string $providerReference, ?string $message = null): self
    {
        return new self(
            accepted: true,
            providerReference: $providerReference,
            message: $message,
        );
    }

    /**
     * The device confirmed the command.
     */
    public static function acknowledged(
        string $providerReference,
        DateTimeImmutable $acknowledgedAt,
        ?string $message = null,
    ): self {
        return new self(
            accepted: true,
            acknowledged: true,
            acknowledgedAt: $acknowledgedAt,
            providerReference: $providerReference,
            message: $message,
        );
    }

    public static function failed(string $reason, ?string $providerReference = null): self
    {
        return new self(
            accepted: false,
            providerReference: $providerReference,
            failureReason: $reason,
        );
    }

    /**
     * Only true when the device itself has reported back.
     */
    public function isConfirmed(): bool
    {
        return $this->accepted && $this->acknowledged && $this->acknowledgedAt !== null;
    }

    /**
     * The command is still in flight: accepted but not yet confirmed.
     */
    public function isPending(): bool
    {
        return $this->accepted && ! $this->isConfirmed();
    }

    /**
     * The `device_commands.status` this result maps to.
     */
    public function deviceCommandStatus(): string
    {
        return match (true) {
            ! $this->accepted => \App\Models\DeviceCommand::STATUS_FAILED,
            $this->isConfirmed() => \App\Models\DeviceCommand::STATUS_ACKNOWLEDGED,
            default => \App\Models\DeviceCommand::STATUS_SENT,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'accepted' => $this->accepted,
            'acknowledged' => $this->acknowledged,
            'confirmed' => $this->isConfirmed(),
            'acknowledged_at' => $this->acknowledgedAt?->format(DATE_ATOM),
            'provider_reference' => $this->providerReference,
            'message' => $this->message,
            'failure_reason' => $this->failureReason,
        ];
    }
}
