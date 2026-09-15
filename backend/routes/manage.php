<?php

use App\Http\Controllers\Manage\AuthController;
use App\Http\Controllers\Manage\DashboardController;
use App\Http\Controllers\Manage\ModuleController;
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

Route::middleware('guest:admin')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:auth')
        ->name('login.store');
});

Route::middleware(['auth:admin', 'admin.active'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    /*
    |----------------------------------------------------------------------
    | Modules
    |----------------------------------------------------------------------
    |
    | Phase 1 registers the navigation and the authorisation boundary for every
    | planned module. Anything not built yet renders its scope and required
    | permission instead of a fake screen.
    |
    */
    foreach (array_keys(ModuleController::MODULES) as $module) {
        Route::get($module, ModuleController::class)
            ->defaults('module', $module)
            ->name($module.'.index')
            ->middleware('admin.permission:'.ModuleController::permissionFor($module));
    }
});
