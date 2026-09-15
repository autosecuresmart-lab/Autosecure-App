<?php

namespace App\Contracts\Devices;

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Support\Devices\CommandResult;
use App\Support\Devices\DeviceLocation;
use App\Support\Devices\DeviceStatus;
use DateTimeInterface;
use Illuminate\Support\Collection;

/**
 * Everything AUTOSECURE needs from a tracker platform.
 *
 * This contract is the seam for Phase 3. When the tracker API documentation
 * arrives, the work is:
 *
 *   1. implement this interface in one class (e.g. `VendorTrackerProvider`),
 *   2. register it: `DeviceProviderManager::extend('tracker', 'vendor', fn () => ...)`,
 *   3. set `TRACKER_DRIVER=vendor` in `.env`,
 *   4. remove the `security` entry from App\Support\PendingIntegrations.
 *
 * No controller, migration or model changes are needed.
 *
 * Implementations MUST NOT:
 *   - report a command as successful without device evidence (see CommandResult),
 *   - return provider credentials to the caller,
 *   - throw anything other than a provider exception for a transport failure.
 */
interface TrackerProvider
{
    /**
     * The driver key used in `config('autosecure.devices.tracker.driver')`.
     */
    public function driverName(): string;

    /**
     * Whether credentials and a base URL are present.
     *
     * A driver that is not configured must still satisfy this interface; the
     * platform uses this to answer "integration pending" instead of guessing.
     */
    public function isConfigured(): bool;

    /**
     * Current device state: online/offline, firmware, battery, signal.
     */
    public function status(Device $device): DeviceStatus;

    /**
     * The most recent position fix.
     */
    public function latestLocation(Device $device): DeviceLocation;

    /**
     * Position history for trip playback.
     *
     * @return Collection<int, DeviceLocation>
     */
    public function locationHistory(Device $device, DateTimeInterface $from, DateTimeInterface $to): Collection;

    /**
     * Dispatch a command and report exactly what the device did.
     *
     * The command row already exists (status `pending`) with an idempotency key,
     * so a retry must not immobilise a vehicle twice.
     */
    public function sendCommand(Device $device, DeviceCommand $command): CommandResult;
}
