<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PendingController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\VehicleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AUTOSECURE 2.0 — Mobile API
|--------------------------------------------------------------------------
|
| Versioned under /api/v1. Records are addressed by uuid, never by the numeric
| id. Everything that touches a vehicle passes through the `vehicle.access`
| middleware so ownership and sharing are enforced server side.
|
*/

Route::prefix(config('autosecure.api.version', 'v1'))
    ->name('api.v1.')
    ->group(function () {

        /*
        |----------------------------------------------------------------------
        | Public
        |----------------------------------------------------------------------
        */
        Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:auth');
        Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:auth');

        // Password recovery. Throttled harder: these are the endpoints an
        // attacker would use to spam a customer or guess reset codes.
        Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])
            ->middleware('throttle:password-reset');
        Route::post('auth/reset-password', [AuthController::class, 'resetPassword'])
            ->middleware('throttle:password-reset');

        Route::get('subscriptions/plans', [SubscriptionController::class, 'plans']);
        Route::post('payments/webhook', [\App\Http\Controllers\Api\V1\PaymentController::class, 'webhook']);
        Route::post('gprs/packet', [\App\Http\Controllers\Api\V1\GprsPacketController::class, 'handle']);
        Route::match(['get', 'post'], 'cron/gprs-stream', [\App\Http\Controllers\Api\V1\CronController::class, 'gprsStream']);
        Route::match(['get', 'post'], 'cron/telemetry-sync', [\App\Http\Controllers\Api\V1\CronController::class, 'gprsStream']);

        /*
        |----------------------------------------------------------------------
        | Authenticated (Sanctum bearer token)
        |----------------------------------------------------------------------
        */
        Route::middleware('auth:sanctum')->group(function () {

            // --- Session / account -------------------------------------------
            Route::get('auth/me', [AuthController::class, 'me']);
            Route::post('auth/logout', [AuthController::class, 'logout']);
            Route::post('auth/logout-all', [AuthController::class, 'logoutAll']);

            // Signed-in devices, so a customer can see and revoke them.
            // Sessions are addressed by token uuid, never by the numeric id.
            Route::get('auth/sessions', [AuthController::class, 'sessions']);
            Route::delete('auth/sessions/{session}', [AuthController::class, 'revokeSession']);

            // --- Profile ------------------------------------------------------
            Route::get('users/me', [UserController::class, 'show']);
            Route::patch('users/me', [UserController::class, 'update']);
            Route::put('users/me/password', [UserController::class, 'updatePassword']);

            // --- My Vehicle --------------------------------------------------
            Route::get('vehicles', [VehicleController::class, 'index']);
            Route::post('vehicles', [VehicleController::class, 'store']);
            Route::get('vehicles/{vehicle}', [VehicleController::class, 'show'])
                ->middleware('vehicle.access');
            Route::patch('vehicles/{vehicle}', [VehicleController::class, 'update'])
                ->middleware('vehicle.access');
            Route::delete('vehicles/{vehicle}', [VehicleController::class, 'destroy'])
                ->middleware('vehicle.access');

            // --- Devices ------------------------------------------------------
            // Device lifecycle is platform-owned. Location, video and commands
            // come from the provider and are still pending (see the 501 group
            // at the bottom of this file).
            Route::get('devices', [DeviceController::class, 'index']);
            Route::get('devices/{device}', [DeviceController::class, 'show'])
                ->middleware('device.access');
            Route::patch('devices/{device}', [DeviceController::class, 'update'])
                ->middleware('device.access');
            Route::post('devices/{device}/bind', [DeviceController::class, 'bind'])
                ->middleware('device.access');
            Route::delete('devices/{device}/unbind', [DeviceController::class, 'unbind'])
                ->middleware('device.access');

            // Devices bound to one vehicle (kept for the vehicle screen).
            Route::get('vehicles/{vehicle}/devices', [VehicleController::class, 'devices'])
                ->middleware('vehicle.access');

            // --- Dashcam Module (Phase 4) ------------------------------------
            Route::post('dashcams/pair', [\App\Http\Controllers\Api\V1\DashcamController::class, 'pair']);
            Route::post('dashcam/pair', [\App\Http\Controllers\Api\V1\DashcamController::class, 'pair']);

            Route::post('dashcams/{device}/stream', [\App\Http\Controllers\Api\V1\DashcamController::class, 'stream'])
                ->middleware('device.access');
            Route::get('dashcam/{device}/stream', [\App\Http\Controllers\Api\V1\DashcamController::class, 'stream'])
                ->middleware('device.access');
            Route::post('dashcam/{device}/stream', [\App\Http\Controllers\Api\V1\DashcamController::class, 'stream'])
                ->middleware('device.access');

            Route::get('dashcams/{device}/recordings', [\App\Http\Controllers\Api\V1\DashcamController::class, 'recordings'])
                ->middleware('device.access');
            Route::get('dashcam/{device}/recordings', [\App\Http\Controllers\Api\V1\DashcamController::class, 'recordings'])
                ->middleware('device.access');

            Route::get('dashcams/{device}/emergencies', [\App\Http\Controllers\Api\V1\DashcamController::class, 'emergencies'])
                ->middleware('device.access');
            Route::get('dashcam/{device}/emergencies', [\App\Http\Controllers\Api\V1\DashcamController::class, 'emergencies'])
                ->middleware('device.access');

            Route::post('dashcams/{device}/snapshot', [\App\Http\Controllers\Api\V1\DashcamController::class, 'snapshot'])
                ->middleware('device.access');
            Route::post('dashcam/{device}/snapshot', [\App\Http\Controllers\Api\V1\DashcamController::class, 'snapshot'])
                ->middleware('device.access');

            Route::get('dashcams/{device}/status', [\App\Http\Controllers\Api\V1\DashcamController::class, 'status'])
                ->middleware('device.access');
            Route::get('dashcam/{device}/status', [\App\Http\Controllers\Api\V1\DashcamController::class, 'status'])
                ->middleware('device.access');

            Route::post('dashcams/{device}/restart', [\App\Http\Controllers\Api\V1\DashcamController::class, 'restart'])
                ->middleware('device.access');
            Route::post('dashcam/{device}/restart', [\App\Http\Controllers\Api\V1\DashcamController::class, 'restart'])
                ->middleware('device.access');

            Route::post('dashcams/{device}/format-sd', [\App\Http\Controllers\Api\V1\DashcamController::class, 'formatSdCard'])
                ->middleware('device.access');
            Route::post('dashcam/{device}/format-sd', [\App\Http\Controllers\Api\V1\DashcamController::class, 'formatSdCard'])
                ->middleware('device.access');
            Route::post('dashcam/{device}/format-sd-card', [\App\Http\Controllers\Api\V1\DashcamController::class, 'formatSdCard'])
                ->middleware('device.access');

            Route::get('vehicles/{vehicle}/dashcam/live', [\App\Http\Controllers\Api\V1\DashcamController::class, 'liveForVehicle'])
                ->middleware('vehicle.access');


            // --- Tracking & Telemetry (Phase 3) ------------------------------
            Route::get('vehicles/{vehicle}/location', [\App\Http\Controllers\Api\V1\TrackingController::class, 'location'])
                ->middleware('vehicle.access');
            Route::get('vehicles/{vehicle}/status', [\App\Http\Controllers\Api\V1\TrackingController::class, 'status'])
                ->middleware('vehicle.access');
            Route::get('vehicles/{vehicle}/playback', [\App\Http\Controllers\Api\V1\TrackingController::class, 'playback'])
                ->middleware('vehicle.access');
            Route::get('vehicles/{vehicle}/trips', [\App\Http\Controllers\Api\V1\TrackingController::class, 'trips'])
                ->middleware('vehicle.access');

            // --- Security & Engine Control (Phase 3) -------------------------
            Route::post('vehicles/{vehicle}/shutdown', [\App\Http\Controllers\Api\V1\SecurityController::class, 'shutdown'])
                ->middleware(['vehicle.access', 'throttle:device-commands']);
            Route::post('vehicles/{vehicle}/restore-engine', [\App\Http\Controllers\Api\V1\SecurityController::class, 'restoreEngine'])
                ->middleware(['vehicle.access', 'throttle:device-commands']);
            Route::post('vehicles/{vehicle}/call-vehicle', [\App\Http\Controllers\Api\V1\SecurityController::class, 'callVehicle'])
                ->middleware('vehicle.access');
            Route::post('vehicles/{vehicle}/voice-monitor', [\App\Http\Controllers\Api\V1\SecurityController::class, 'voiceMonitor'])
                ->middleware('vehicle.access');
            Route::post('vehicles/{vehicle}/gprs-command', [\App\Http\Controllers\Api\V1\SecurityController::class, 'sendGprsCommand'])
                ->middleware(['vehicle.access', 'throttle:device-commands']);
            Route::get('vehicles/{vehicle}/security-events', [\App\Http\Controllers\Api\V1\SecurityController::class, 'securityEvents'])
                ->middleware('vehicle.access');
            Route::get('commands/{command}', [\App\Http\Controllers\Api\V1\SecurityController::class, 'commandStatus']);

            // --- Car Theft Trigger (Phase 3) ---------------------------------
            Route::post('vehicles/{vehicle}/theft-trigger', [\App\Http\Controllers\Api\V1\TheftTriggerController::class, 'trigger'])
                ->middleware(['vehicle.access', 'throttle:device-commands']);
            Route::get('vehicles/{vehicle}/theft-events', [\App\Http\Controllers\Api\V1\TheftTriggerController::class, 'index'])
                ->middleware('vehicle.access');
            Route::get('theft-events/{theft_event}', [\App\Http\Controllers\Api\V1\TheftTriggerController::class, 'show']);
            Route::post('theft-events/{theft_event}/resolve', [\App\Http\Controllers\Api\V1\TheftTriggerController::class, 'resolve']);

            // --- Subscriptions & Coins (Phase 8) ----------------------------
            Route::get('subscriptions/me', [SubscriptionController::class, 'me']);
            Route::get('subscriptions/history', [SubscriptionController::class, 'history']);
            Route::post('subscriptions/renew', [SubscriptionController::class, 'renew']);
            Route::post('subscriptions/cancel', [SubscriptionController::class, 'cancel']);
            Route::get('coins/wallet', [SubscriptionController::class, 'wallet']);

            // --- Payments Module (Phase 8) -----------------------------------
            Route::get('payments', [\App\Http\Controllers\Api\V1\PaymentController::class, 'index']);
            Route::post('payments/initialize', [\App\Http\Controllers\Api\V1\PaymentController::class, 'initialize']);
            Route::post('payments/verify', [\App\Http\Controllers\Api\V1\PaymentController::class, 'verify']);
            Route::get('payments/{payment}', [\App\Http\Controllers\Api\V1\PaymentController::class, 'show']);
            Route::post('payments/{payment}/refund', [\App\Http\Controllers\Api\V1\PaymentController::class, 'refund']);

            // --- Vehicle Care Module (Phase 5) -------------------------------
            Route::get('vehicles/{vehicle}/care/dashboard', [\App\Http\Controllers\Api\V1\VehicleCareController::class, 'dashboard'])
                ->middleware('vehicle.access');
            Route::patch('vehicles/{vehicle}/odometer', [\App\Http\Controllers\Api\V1\VehicleCareController::class, 'updateOdometer'])
                ->middleware('vehicle.access');
            Route::get('vehicles/{vehicle}/care/timeline', [\App\Http\Controllers\Api\V1\VehicleCareController::class, 'timeline'])
                ->middleware('vehicle.access');

            // Maintenance Records
            Route::get('vehicles/{vehicle}/maintenance-records', [\App\Http\Controllers\Api\V1\MaintenanceRecordController::class, 'index'])
                ->middleware('vehicle.access');
            Route::post('vehicles/{vehicle}/maintenance-records', [\App\Http\Controllers\Api\V1\MaintenanceRecordController::class, 'store'])
                ->middleware('vehicle.access');
            Route::get('vehicles/{vehicle}/maintenance-records/{record}', [\App\Http\Controllers\Api\V1\MaintenanceRecordController::class, 'show'])
                ->middleware('vehicle.access');
            Route::match(['put', 'patch'], 'vehicles/{vehicle}/maintenance-records/{record}', [\App\Http\Controllers\Api\V1\MaintenanceRecordController::class, 'update'])
                ->middleware('vehicle.access');
            Route::delete('vehicles/{vehicle}/maintenance-records/{record}', [\App\Http\Controllers\Api\V1\MaintenanceRecordController::class, 'destroy'])
                ->middleware('vehicle.access');

            // Maintenance Reminders
            Route::get('vehicles/{vehicle}/reminders', [\App\Http\Controllers\Api\V1\MaintenanceReminderController::class, 'index'])
                ->middleware('vehicle.access');
            Route::post('vehicles/{vehicle}/reminders', [\App\Http\Controllers\Api\V1\MaintenanceReminderController::class, 'store'])
                ->middleware('vehicle.access');
            Route::patch('vehicles/{vehicle}/reminders/{reminder}/complete', [\App\Http\Controllers\Api\V1\MaintenanceReminderController::class, 'complete'])
                ->middleware('vehicle.access');
            Route::patch('vehicles/{vehicle}/reminders/{reminder}/dismiss', [\App\Http\Controllers\Api\V1\MaintenanceReminderController::class, 'dismiss'])
                ->middleware('vehicle.access');
            Route::delete('vehicles/{vehicle}/reminders/{reminder}', [\App\Http\Controllers\Api\V1\MaintenanceReminderController::class, 'destroy'])
                ->middleware('vehicle.access');

            // Fuel Records
            Route::get('vehicles/{vehicle}/fuel-records', [\App\Http\Controllers\Api\V1\FuelRecordController::class, 'index'])
                ->middleware('vehicle.access');
            Route::post('vehicles/{vehicle}/fuel-records', [\App\Http\Controllers\Api\V1\FuelRecordController::class, 'store'])
                ->middleware('vehicle.access');
            Route::get('vehicles/{vehicle}/fuel-records/{record}', [\App\Http\Controllers\Api\V1\FuelRecordController::class, 'show'])
                ->middleware('vehicle.access');
            Route::match(['put', 'patch'], 'vehicles/{vehicle}/fuel-records/{record}', [\App\Http\Controllers\Api\V1\FuelRecordController::class, 'update'])
                ->middleware('vehicle.access');
            Route::delete('vehicles/{vehicle}/fuel-records/{record}', [\App\Http\Controllers\Api\V1\FuelRecordController::class, 'destroy'])
                ->middleware('vehicle.access');

            // --- AutoDoc Integration (Phase 6) -------------------------------
            Route::post('vehicles/{vehicle}/autodoc/launch', [\App\Http\Controllers\Api\V1\AutoDocController::class, 'launch'])
                ->middleware('vehicle.access');
            Route::get('vehicles/{vehicle}/autodoc/documents', [\App\Http\Controllers\Api\V1\AutoDocController::class, 'documents'])
                ->middleware('vehicle.access');
            Route::post('vehicles/{vehicle}/autodoc/associate', [\App\Http\Controllers\Api\V1\AutoDocController::class, 'associate'])
                ->middleware('vehicle.access');
            Route::get('vehicles/{vehicle}/autodoc/diagnostics', [\App\Http\Controllers\Api\V1\AutoDocController::class, 'diagnostics'])
                ->middleware('vehicle.access');
            Route::post('vehicles/{vehicle}/autodoc/clear-codes', [\App\Http\Controllers\Api\V1\AutoDocController::class, 'clearCodes'])
                ->middleware('vehicle.access');

            // --- Finder Marketplace (Phase 7) ---------------------------------
            Route::get('finder/categories', [\App\Http\Controllers\Api\V1\FinderController::class, 'categories']);
            Route::get('finder/vendors', [\App\Http\Controllers\Api\V1\FinderController::class, 'vendors']);
            Route::get('finder/vendors/{vendor}', [\App\Http\Controllers\Api\V1\FinderController::class, 'show']);
            Route::get('finder/vendors/{vendor}/reviews', [\App\Http\Controllers\Api\V1\FinderController::class, 'reviews']);
            Route::post('finder/vendors/{vendor}/reviews', [\App\Http\Controllers\Api\V1\FinderController::class, 'submitReview']);

            // --- Bookings (Phase 7) -------------------------------------------
            Route::get('bookings', [\App\Http\Controllers\Api\V1\BookingController::class, 'index']);
            Route::post('bookings', [\App\Http\Controllers\Api\V1\BookingController::class, 'store']);
            Route::get('bookings/{booking}', [\App\Http\Controllers\Api\V1\BookingController::class, 'show']);
            Route::post('bookings/{booking}/cancel', [\App\Http\Controllers\Api\V1\BookingController::class, 'cancel']);

            // --- Vendor Hub (Phase 7) -----------------------------------------
            Route::post('vendor-hub/register', [\App\Http\Controllers\Api\V1\VendorHubController::class, 'register']);
            Route::get('vendor-hub/profile', [\App\Http\Controllers\Api\V1\VendorHubController::class, 'profile']);
            Route::put('vendor-hub/profile', [\App\Http\Controllers\Api\V1\VendorHubController::class, 'updateProfile']);
            Route::get('vendor-hub/services', [\App\Http\Controllers\Api\V1\VendorHubController::class, 'services']);
            Route::post('vendor-hub/services', [\App\Http\Controllers\Api\V1\VendorHubController::class, 'storeService']);
            Route::put('vendor-hub/services/{service}', [\App\Http\Controllers\Api\V1\VendorHubController::class, 'updateService']);
            Route::delete('vendor-hub/services/{service}', [\App\Http\Controllers\Api\V1\VendorHubController::class, 'destroyService']);
            Route::get('vendor-hub/bookings', [\App\Http\Controllers\Api\V1\VendorHubController::class, 'bookings']);
            Route::patch('vendor-hub/bookings/{booking}/status', [\App\Http\Controllers\Api\V1\VendorHubController::class, 'updateBookingStatus']);
            Route::post('vendor-hub/reviews/{review}/reply', [\App\Http\Controllers\Api\V1\VendorHubController::class, 'replyToReview']);



            // --- Admin Management (/manage Portal) ---------------------------
            Route::prefix('admin')->group(function () {
                // User management
                Route::get('users', [\App\Http\Controllers\Api\V1\Admin\AdminUserController::class, 'index']);
                Route::post('users', [\App\Http\Controllers\Api\V1\Admin\AdminUserController::class, 'store']);
                Route::get('users/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminUserController::class, 'show']);
                Route::patch('users/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminUserController::class, 'update']);

                // Vehicle management & assignment
                Route::get('vehicles', [\App\Http\Controllers\Api\V1\Admin\AdminVehicleController::class, 'index']);
                Route::post('vehicles', [\App\Http\Controllers\Api\V1\Admin\AdminVehicleController::class, 'store']);
                Route::get('vehicles/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminVehicleController::class, 'show']);
                Route::patch('vehicles/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminVehicleController::class, 'update']);
                Route::delete('vehicles/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminVehicleController::class, 'destroy']);

                // Device inventory & vehicle binding
                Route::get('devices', [\App\Http\Controllers\Api\V1\Admin\AdminDeviceController::class, 'index']);
                Route::post('devices', [\App\Http\Controllers\Api\V1\Admin\AdminDeviceController::class, 'store']);
                Route::get('devices/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminDeviceController::class, 'show']);
                Route::post('devices/{id}/bind', [\App\Http\Controllers\Api\V1\Admin\AdminDeviceController::class, 'bind']);
                Route::post('devices/{id}/unbind', [\App\Http\Controllers\Api\V1\Admin\AdminDeviceController::class, 'unbind']);
            });

            // --- Notifications ------------------------------------------------
            Route::get('notifications', [NotificationController::class, 'index']);
            Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
            Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);

            // Push registration for this handset.
            Route::post('notifications/push-tokens', [NotificationController::class, 'registerPushToken']);
            Route::delete('notifications/push-tokens', [NotificationController::class, 'unregisterPushToken']);

            /*
            |------------------------------------------------------------------
            | Integration-pending areas
            |------------------------------------------------------------------
            |
            | Registered LAST so real routes always win. These answer HTTP 501
            | with the exact documentation still outstanding rather than
            | pretending an integration exists.
            |
            | security -> tracker API (location, playback, commands, shutdown)
            | dashcam  -> dashcam SDK/API (live, playback, pairing, controls)
            | autodoc  -> separate app; integration level not yet agreed
            | care     -> vehicle care (mileage/reminder provider dependencies)
            | finder / bookings / payments -> awaiting commercial sign-off
            |
            */
            Route::prefix('{module}')
                ->whereIn('module', App\Support\PendingIntegrations::keys())
                ->group(function () {
                    Route::any('{path?}', PendingController::class)->where('path', '.*');
                });
        });
    });
