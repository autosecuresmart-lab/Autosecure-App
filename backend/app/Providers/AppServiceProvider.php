<?php

namespace App\Providers;

use App\Contracts\Devices\DashcamProvider;
use App\Contracts\Devices\TrackerProvider;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\CoinWallet;
use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\FuelRecord;
use App\Models\MaintenanceRecord;
use App\Models\Payment;
use App\Models\PersonalAccessToken;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Vendor;
use App\Models\VendorCategory;
use App\Models\VendorService;
use App\Services\Devices\DeviceProviderManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Morph aliases keep polymorphic columns (audit logs, notification subjects,
     * coin sources) stable and readable instead of storing class names.
     *
     * @var array<string, class-string>
     */
    private const MORPH_MAP = [
        'user' => User::class,
        'admin' => Admin::class,
        'vehicle' => Vehicle::class,
        'device' => Device::class,
        'device_command' => DeviceCommand::class,
        'vendor' => Vendor::class,
        'vendor_category' => VendorCategory::class,
        'vendor_service' => VendorService::class,
        'booking' => Booking::class,
        'payment' => Payment::class,
        'subscription' => Subscription::class,
        'subscription_plan' => SubscriptionPlan::class,
        'coin_wallet' => CoinWallet::class,
        'maintenance_record' => MaintenanceRecord::class,
        'fuel_record' => FuelRecord::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One manager instance owns driver resolution for both device types.
        $this->app->singleton(DeviceProviderManager::class);

        // Controllers and services type-hint the contract, never a provider name.
        $this->app->bind(
            TrackerProvider::class,
            fn ($app) => $app->make(DeviceProviderManager::class)->tracker(),
        );

        $this->app->bind(
            DashcamProvider::class,
            fn ($app) => $app->make(DeviceProviderManager::class)->dashcam(),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap(self::MORPH_MAP);

        // Sanctum tokens carry a uuid like every other AUTOSECURE table, so a
        // customer can revoke a single signed-in device by uuid.
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        $this->registerDeviceProviders();
        $this->configureRateLimiting();
    }

    /**
     * Provider registration point.
     *
     * PHASE 3/4: when AUTOSECURE supplies the tracker API or the dashcam SDK,
     * register the implementation here, for example:
     *
     *   $manager->extend('tracker', 'acme', fn () => new AcmeTrackerProvider(
     *       config('autosecure.devices.tracker.base_url'),
     *       config('autosecure.devices.tracker.api_key'),
     *   ));
     *
     * Then set TRACKER_DRIVER=acme and drop the module from
     * App\Support\PendingIntegrations. No other code changes are required.
     */
    protected function registerDeviceProviders(): void
    {
        /** @var DeviceProviderManager $manager */
        $manager = $this->app->make(DeviceProviderManager::class);

        $manager->extend('tracker', 'gprs', fn () => new \App\Services\Devices\Providers\GprsDeviceProvider());
        $manager->extend('tracker', 'openapi', fn () => new \App\Services\Devices\Providers\OpenApiDeviceProvider());
        $manager->extend('dashcam', 'openapi', fn () => new \App\Services\Devices\Providers\OpenApiDeviceProvider());
    }

    /**
     * Rate limiters for the mobile API and the staff portal.
     *
     * Auth endpoints are limited by IP; authenticated traffic is limited per
     * customer so one handset cannot exhaust the platform for everyone.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        // Password recovery is the endpoint an attacker would use to spam a
        // customer or to brute-force reset codes, so it is limited per IP AND
        // per target address.
        RateLimiter::for('password-reset', fn (Request $request) => [
            Limit::perMinute(5)->by('ip:'.$request->ip()),
            Limit::perMinute(3)->by('email:'.(string) ($request->input('email') ?: $request->ip())),
        ]);

        RateLimiter::for('api', fn (Request $request) => $request->user()
            ? Limit::perMinute(120)->by($request->user()->getAuthIdentifier())
            : Limit::perMinute(30)->by($request->ip()));

        // Device commands are physically consequential and expensive, so they
        // get their own tighter budget.
        RateLimiter::for('device-commands', fn (Request $request) => $request->user()
            ? Limit::perMinute(10)->by($request->user()->getAuthIdentifier())
            : Limit::perMinute(5)->by($request->ip()));
    }
}
