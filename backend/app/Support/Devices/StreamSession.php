<?php

namespace App\Support\Devices;

use DateTimeImmutable;

/**
 * A short-lived permission to view a camera stream.
 *
 * SECURITY-CRITICAL. AUTOSECURE must never expose streaming credentials or a
 * permanent device token to the mobile client, so a session carries only:
 *
 *   - a URL the app can play,
 *   - a short-lived token (seconds to minutes), and
 *   - the expiry it must respect.
 *
 * The provider's permanent credentials stay server side.
 */
final readonly class StreamSession
{
    /**
     * @param  array<string, mixed>  $extra  Transport hints (e.g. ICE servers, quality ladder).
     */
    public function __construct(
        public string $url,
        public DateTimeImmutable $expiresAt,
        public ?string $token = null,
        public string $protocol = 'hls',
        public string $camera = 'front',
        public array $extra = [],
    ) {}

    public function isExpired(?DateTimeImmutable $now = null): bool
    {
        return $this->expiresAt <= ($now ?? new DateTimeImmutable());
    }

    public function expiresInSeconds(?DateTimeImmutable $now = null): int
    {
        return max(0, $this->expiresAt->getTimestamp() - ($now ?? new DateTimeImmutable())->getTimestamp());
    }

    /**
     * Payload for the mobile client. Only the time-boxed token is included.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'token' => $this->token,
            'protocol' => $this->protocol,
            'camera' => $this->camera,
            'expires_at' => $this->expiresAt->format(DATE_ATOM),
            'expires_in' => $this->expiresInSeconds(),
            'extra' => $this->extra,
        ];
    }
}
