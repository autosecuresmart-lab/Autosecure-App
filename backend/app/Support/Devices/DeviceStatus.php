<?php

namespace App\Support\Devices;

use DateTimeImmutable;

/**
 * Provider-reported state of a physical device.
 *
 * Deliberately provider-neutral: whatever the tracker or dashcam platform calls
 * these fields, they are normalised into this shape before touching our schema.
 */
final readonly class DeviceStatus
{
    /**
     * @param  array<string, mixed>  $raw  The provider's original payload, kept for debugging.
     */
    public function __construct(
        public bool $online,
        public ?DateTimeImmutable $lastSeenAt = null,
        public ?string $firmwareVersion = null,
        public ?int $batteryPercent = null,
        public ?int $signalPercent = null,
        public ?bool $ignition = null,
        public ?string $simNumber = null,
        public array $raw = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'online' => $this->online,
            'last_seen_at' => $this->lastSeenAt?->format(DATE_ATOM),
            'firmware_version' => $this->firmwareVersion,
            'battery_percent' => $this->batteryPercent,
            'signal_percent' => $this->signalPercent,
            'ignition' => $this->ignition,
            'sim_number' => $this->simNumber,
        ];
    }
}
