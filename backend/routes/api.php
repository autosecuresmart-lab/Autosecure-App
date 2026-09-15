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

            // --- Subscriptions & Coins ---------------------------------------
            Route::get('subscriptions/me', [SubscriptionController::class, 'me']);
            Route::get('coins/wallet', [SubscriptionController::class, 'wallet']);

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
