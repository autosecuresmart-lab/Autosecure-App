<?php

use App\Http\Controllers\Manage\AuthController;
use App\Http\Controllers\Manage\DashboardController;
use App\Http\Controllers\Manage\DeviceController;
use App\Http\Controllers\Manage\ModuleController;
use App\Http\Controllers\Manage\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AUTOSECURE management portal (/manage)
|--------------------------------------------------------------------------
|
| Runs on the dedicated `admin` guard. Customers and staff never share a table,
| a guard or a session. Every module route is guarded by an explicit permission
| slug, and each registered module is listed once in ModuleController MODULES.
|
*/

// Root /manage redirect: redirects to /manage/auth/login if guest, or /manage/dashboard if authenticated
Route::get('/', function () {
    if (auth('admin')->check()) {
        return redirect()->route('manage.dashboard');
    }

    return redirect()->route('manage.login');
});

Route::middleware('guest:admin')->group(function () {
    // Primary auth login routes: /manage/auth/login
    Route::get('auth/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:auth')
        ->name('login.store');

    // Backward-compatible aliases for /manage/login
    Route::get('login', fn () => redirect()->route('manage.login'));
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:auth');
});

Route::middleware(['auth:admin', 'admin.active'])->group(function () {
    // Dashboard route: /manage/dashboard
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    /*
    |----------------------------------------------------------------------
    | Customer Users Management
    |----------------------------------------------------------------------
    */
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('users/{user}/status', [UserController::class, 'toggleStatus'])->name('users.status');
    Route::post('users/{user}/vehicles', [UserController::class, 'storeVehicle'])->name('users.vehicles.store');
    Route::post('users/{user}/devices', [UserController::class, 'storeDevice'])->name('users.devices.store');
    Route::post('users/{user}/security-command', [UserController::class, 'sendCommand'])->name('users.security-command');

    // Backward-compatible route for customers module
    Route::get('customers', [UserController::class, 'index'])->name('customers.index');

    /*
    |----------------------------------------------------------------------
    | Hardware & GPS Trackers Management
    |----------------------------------------------------------------------
    */
    Route::get('devices', [DeviceController::class, 'index'])->name('devices.index');
    Route::post('devices', [DeviceController::class, 'store'])->name('devices.store');
    Route::get('devices/{device}', [DeviceController::class, 'show'])->name('devices.show');
    Route::put('devices/{device}', [DeviceController::class, 'update'])->name('devices.update');
    Route::post('devices/{device}/command', [DeviceController::class, 'sendCommand'])->name('devices.command');
    Route::post('devices/{device}/unbind', [DeviceController::class, 'unbind'])->name('devices.unbind');

    /*
    |----------------------------------------------------------------------
    | Modules
    |----------------------------------------------------------------------
    |
    | Registers the navigation and the authorisation boundary for every
    | planned module.
    |
    */
    foreach (array_keys(ModuleController::MODULES) as $module) {
        if ($module === 'customers' || $module === 'devices') {
            continue;
        }

        Route::get($module, ModuleController::class)
            ->defaults('module', $module)
            ->name($module.'.index')
            ->middleware('admin.permission:'.ModuleController::permissionFor($module));
    }
});
