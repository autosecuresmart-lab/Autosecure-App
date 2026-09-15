<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Landing page (public web)
|--------------------------------------------------------------------------
|
| The AUTOSECURE marketing landing page. The customer web application is future
| scope; today the web tier is the landing page plus the /manage portal
| (see routes/manage.php).
|
*/

Route::get('/', function () {
    return view('welcome');
})->name('landing');

// Placeholder while the customer web application is out of scope.
// Laravel's guest redirection needs this named route to exist.
Route::get('/login', function () {
    return redirect()->route('landing');
})->name('login');

// The password reset flow is delivered by the mobile app over the API
// (POST /api/v1/auth/reset-password) and deep-links to `autosecure://reset-password`.
// This named route exists so a framework-generated reset URL can never 500.
Route::get('/password/reset/{token}', function () {
    return redirect()->route('landing');
})->name('password.reset');
