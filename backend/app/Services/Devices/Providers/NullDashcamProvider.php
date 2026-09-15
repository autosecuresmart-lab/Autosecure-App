<?php

namespace App\Services\Devices\Providers;

use App\Contracts\Devices\DashcamProvider;
use App\Exceptions\IntegrationPendingException;
use App\Models\Device;
use App\Support\Devices\CommandResult;
use App\Support\Devices\Recording;
use App\Support\Devices\StreamSession;
use DateTimeInterface;
use Illuminate\Support\Collection;

/**
 * The dashcam driver used until an AUTOSECURE dashcam SDK/API is supplied.
 *
 * Fails loudly rather than returning a fake stream URL — a fabricated stream
 * would look like a working camera to the customer.
 */
class NullDashcamProvider implements DashcamProvider
{
    public const DRIVER = 'null';

    public function driverName(): string
    {
        return self::DRIVER;
    }

    public function isConfigured(): bool
    {
        return false;
    }

    public function createStreamSession(Device $device, string $camera = 'front'): StreamSession
    {
        throw $this->pending('createStreamSession');
    }

    /**
     * @return Collection<int, Recording>
     */
    public function recordings(Device $device, DateTimeInterface $date, string $camera = 'front'): Collection
    {
        throw $this->pending('recordings');
    }

    /**
     * @return Collection<int, Recording>
     */
    public function emergencies(Device $device): Collection
    {
        throw $this->pending('emergencies');
    }

    public function captureSnapshot(Device $device, string $camera = 'front'): Recording
    {
        throw $this->pending('captureSnapshot');
    }

    public function unbind(Device $device): CommandResult
    {
        throw $this->pending('unbind');
    }

    protected function pending(string $operation): IntegrationPendingException
    {
        return IntegrationPendingException::for('dashcam', $operation);
    }
}
