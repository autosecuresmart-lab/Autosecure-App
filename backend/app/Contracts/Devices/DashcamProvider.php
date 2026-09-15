<?php

namespace App\Contracts\Devices;

use App\Models\Device;
use App\Support\Devices\CommandResult;
use App\Support\Devices\Recording;
use App\Support\Devices\StreamSession;
use DateTimeInterface;
use Illuminate\Support\Collection;

/**
 * Everything AUTOSECURE needs from a dashcam platform.
 *
 * The single blocking unknown is the live streaming method (vendor SDK player,
 * HLS, WebRTC or a P2P tunnel) — that answer determines how `createStreamSession`
 * is implemented and how the Camera tab plays video. Everything else here is
 * provider-neutral.
 *
 * Implementations MUST NOT return a permanent device token or streaming
 * credential. Only short-lived, time-boxed sessions (see StreamSession).
 */
interface DashcamProvider
{
    /**
     * The driver key used in `config('autosecure.devices.dashcam.driver')`.
     */
    public function driverName(): string;

    public function isConfigured(): bool;

    /**
     * Issue a short-lived playback session for one camera.
     */
    public function createStreamSession(Device $device, string $camera = 'front'): StreamSession;

    /**
     * Playback library: recordings for a given day and camera.
     *
     * @return Collection<int, Recording>
     */
    public function recordings(Device $device, DateTimeInterface $date, string $camera = 'front'): Collection;

    /**
     * Protected/event recordings, listed separately from the loop library.
     *
     * @return Collection<int, Recording>
     */
    public function emergencies(Device $device): Collection;

    /**
     * Take a snapshot from the active camera feed.
     */
    public function captureSnapshot(Device $device, string $camera = 'front'): Recording;

    /**
     * Release the device from this account, so it can be paired elsewhere.
     *
     * Returns a CommandResult because the provider may need to talk to the device.
     */
    public function unbind(Device $device): CommandResult;
}
