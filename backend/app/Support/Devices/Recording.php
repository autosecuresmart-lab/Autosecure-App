<?php

namespace App\Support\Devices;

use DateTimeImmutable;

/**
 * A dashcam recording, snapshot or emergency clip.
 *
 * Mirrors the album categories the dashcam experience distinguishes: loop
 * recordings, event recordings, snapshots and recorded clips.
 */
final readonly class Recording
{
    public const KIND_LOOP = 'loop';

    public const KIND_EVENT = 'event';

    public const KIND_EMERGENCY = 'emergency';

    public const KIND_SNAPSHOT = 'snapshot';

    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $externalId,
        public DateTimeImmutable $startedAt,
        public string $kind = self::KIND_LOOP,
        public string $camera = 'front',
        public ?int $durationSeconds = null,
        public ?int $sizeBytes = null,
        public ?string $downloadUrl = null,
        public ?string $thumbnailUrl = null,
        public ?bool $isProtected = null,
        public array $raw = [],
    ) {}

    public function isEmergency(): bool
    {
        return $this->kind === self::KIND_EMERGENCY || $this->isProtected === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'external_id' => $this->externalId,
            'camera' => $this->camera,
            'kind' => $this->kind,
            'started_at' => $this->startedAt->format(DATE_ATOM),
            'duration_seconds' => $this->durationSeconds,
            'size_bytes' => $this->sizeBytes,
            'download_url' => $this->downloadUrl,
            'thumbnail_url' => $this->thumbnailUrl,
            'protected' => $this->isProtected,
        ];
    }
}
