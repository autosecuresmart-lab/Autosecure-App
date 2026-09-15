<?php

namespace App\Support\Devices;

use App\Models\DevicePosition;
use DateTimeImmutable;

/**
 * A single normalised location fix from a provider.
 *
 * `source` uses the same vocabulary as the `device_positions.source` enum, which
 * already accounts for the GPS/LBS/WiFi fallback behaviour described in the
 * supplied GPRS protocol document.
 */
final readonly class DeviceLocation
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public float $latitude,
        public float $longitude,
        public DateTimeImmutable $recordedAt,
        public ?float $speedKph = null,
        public ?int $heading = null,
        public ?float $altitudeMetres = null,
        public ?float $accuracyMetres = null,
        public ?bool $ignition = null,
        public ?bool $moving = null,
        public string $source = DevicePosition::SOURCE_GPS,
        public array $raw = [],
    ) {}

    /**
     * Attributes ready for a `device_positions` insert.
     *
     * @return array<string, mixed>
     */
    public function toPositionAttributes(int $deviceId, ?int $vehicleId = null): array
    {
        return [
            'device_id' => $deviceId,
            'vehicle_id' => $vehicleId,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'speed_kph' => $this->speedKph,
            'heading' => $this->heading,
            'altitude_m' => $this->altitudeMetres,
            'accuracy_m' => $this->accuracyMetres,
            'ignition' => $this->ignition,
            'moving' => $this->moving,
            'source' => $this->source,
            'recorded_at' => $this->recordedAt,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'speed_kph' => $this->speedKph,
            'heading' => $this->heading,
            'altitude_m' => $this->altitudeMetres,
            'accuracy_m' => $this->accuracyMetres,
            'ignition' => $this->ignition,
            'moving' => $this->moving,
            'source' => $this->source,
            'recorded_at' => $this->recordedAt->format(DATE_ATOM),
        ];
    }
}
