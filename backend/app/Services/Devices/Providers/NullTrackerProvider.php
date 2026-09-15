<?php

namespace App\Services\Devices\Providers;

use App\Contracts\Devices\TrackerProvider;
use App\Exceptions\IntegrationPendingException;
use App\Models\Device;
use App\Models\DeviceCommand;
use App\Support\Devices\CommandResult;
use App\Support\Devices\DeviceLocation;
use App\Support\Devices\DeviceStatus;
use DateTimeInterface;
use Illuminate\Support\Collection;

/**
 * The tracker driver used until an AUTOSECURE tracker API is supplied.
 *
 * Every operation fails loudly with an IntegrationPendingException, which the
 * HTTP layer renders as the standard `501 integration_pending` body. It never
 * fabricates a location, a device status or — critically — a command
 * acknowledgement.
 */
class NullTrackerProvider implements TrackerProvider
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

    public function status(Device $device): DeviceStatus
    {
        throw $this->pending('status');
    }

    public function latestLocation(Device $device): DeviceLocation
    {
        throw $this->pending('latestLocation');
    }

    /**
     * @return Collection<int, DeviceLocation>
     */
    public function locationHistory(Device $device, DateTimeInterface $from, DateTimeInterface $to): Collection
    {
        throw $this->pending('locationHistory');
    }

    public function sendCommand(Device $device, DeviceCommand $command): CommandResult
    {
        throw $this->pending('sendCommand:'.$command->type);
    }

    protected function pending(string $operation): IntegrationPendingException
    {
        return IntegrationPendingException::for('security', $operation);
    }
}
