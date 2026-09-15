<?php

namespace App\Services\Devices;

use App\Contracts\Devices\DashcamProvider;
use App\Contracts\Devices\TrackerProvider;
use App\Models\Device;
use App\Services\Devices\Providers\NullDashcamProvider;
use App\Services\Devices\Providers\NullTrackerProvider;
use Closure;
use InvalidArgumentException;

/**
 * Resolves the tracker/dashcam driver configured for the environment.
 *
 * This is the only place that knows which provider is wired, so controllers and
 * services never branch on a provider name. Registering a real provider is one
 * `extend()` call plus a config value — see AppServiceProvider.
 *
 *   TRACKER_DRIVER=null   -> NullTrackerProvider  (everything answers 501)
 *   TRACKER_DRIVER=acme   -> whatever was registered via extend('tracker', 'acme', ...)
 *   TRACKER_DRIVER=\App\Services\Devices\Providers\AcmeTrackerProvider
 *
 * A driver that is registered but not configured (missing credentials) is still
 * resolved, so the app can report "integration pending" rather than crash.
 */
class DeviceProviderManager
{
    public const TYPE_TRACKER = 'tracker';

    public const TYPE_DASHCAM = 'dashcam';

    /**
     * Custom driver factories, keyed by type then driver name.
     *
     * @var array<string, array<string, Closure>>
     */
    protected array $creators = [];

    /**
     * @var array<string, TrackerProvider|DashcamProvider>
     */
    protected array $resolved = [];

    /**
     * The tracker driver.
     */
    public function tracker(): TrackerProvider
    {
        /** @var TrackerProvider $provider */
        $provider = $this->resolve(self::TYPE_TRACKER);

        return $provider;
    }

    /**
     * The dashcam driver.
     */
    public function dashcam(): DashcamProvider
    {
        /** @var DashcamProvider $provider */
        $provider = $this->resolve(self::TYPE_DASHCAM);

        return $provider;
    }

    /**
     * The driver responsible for a specific device.
     */
    public function for(Device $device): TrackerProvider|DashcamProvider
    {
        return $this->forType($device->type);
    }

    public function forType(string $type): TrackerProvider|DashcamProvider
    {
        return match ($type) {
            self::TYPE_DASHCAM => $this->dashcam(),
            default => $this->tracker(),
        };
    }

    /**
     * The configured driver name for a type.
     *
     * An unset or empty value means "no provider wired", which is the null
     * driver — not a configuration error.
     */
    public function driverName(string $type): string
    {
        $driver = trim((string) config("autosecure.devices.{$type}.driver", ''));

        return $driver === '' ? NullTrackerProvider::DRIVER : $driver;
    }

    /**
     * Whether the configured driver has its credentials and endpoint.
     *
     * Used to decide between "not integrated yet" and "integrated but broken".
     */
    public function isConfigured(string $type): bool
    {
        return $this->forType($type)->isConfigured();
    }

    /**
     * Register a provider implementation.
     *
     * Called from AppServiceProvider once a vendor SDK/API is available.
     */
    public function extend(string $type, string $driver, Closure $creator): static
    {
        $this->creators[$type][$driver] = $creator;

        unset($this->resolved[$type]);

        return $this;
    }

    /**
     * Drop resolved instances (used by tests and config reloads).
     */
    public function forgetResolved(): void
    {
        $this->resolved = [];
    }

    protected function resolve(string $type): TrackerProvider|DashcamProvider
    {
        if (isset($this->resolved[$type])) {
            return $this->resolved[$type];
        }

        $driver = $this->driverName($type);

        $provider = $this->creators[$type][$driver] ?? null;

        if ($provider instanceof Closure) {
            return $this->resolved[$type] = $provider();
        }

        // A class-string driver is resolved from the container.
        if (class_exists($driver)) {
            $instance = app($driver);

            $this->assertImplements($type, $instance);

            return $this->resolved[$type] = $instance;
        }

        return $this->resolved[$type] = match (true) {
            $driver === NullTrackerProvider::DRIVER && $type === self::TYPE_TRACKER => new NullTrackerProvider,
            $driver === NullDashcamProvider::DRIVER && $type === self::TYPE_DASHCAM => new NullDashcamProvider,
            default => throw new InvalidArgumentException(
                "Unknown {$type} driver [{$driver}]. Register it with "
                ."DeviceProviderManager::extend() or set a valid class name."
            ),
        };
    }

    protected function assertImplements(string $type, object $instance): void
    {
        $expected = $type === self::TYPE_DASHCAM ? DashcamProvider::class : TrackerProvider::class;

        if (! $instance instanceof $expected) {
            throw new InvalidArgumentException(
                sprintf('The [%s] driver must implement %s.', $type, $expected)
            );
        }
    }
}
