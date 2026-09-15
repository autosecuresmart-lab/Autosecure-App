<?php

use App\Exceptions\IntegrationPendingException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException as ThrottleRequestsException;

// Shared guard so each handler can decline non-API requests and let Laravel's
// default handling (redirects, Blade error pages) take over.
$wantsJson = static fn (Request $request): bool => $request->is('api/*') || $request->expectsJson();

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // AUTOSECURE management portal. Stays in the `web` middleware group
            // (session + CSRF) but runs on its own `admin` guard and route
            // name prefix.
            Route::middleware('web')
                ->prefix('manage')
                ->name('manage.')
                ->group(base_path('routes/manage.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.active' => App\Http\Middleware\EnsureAdminIsActive::class,
            'admin.permission' => App\Http\Middleware\EnsureAdminHasPermission::class,
            'entitlement' => App\Http\Middleware\EnsureEntitlement::class,
            'vehicle.access' => App\Http\Middleware\EnsureVehicleAccess::class,
            'device.access' => App\Http\Middleware\EnsureDeviceAccess::class,
        ]);

        // Never send an admin to the customer login screen (or vice versa).
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('manage', 'manage/*')
            ? route('manage.login')
            : route('login'));

        $middleware->redirectUsersTo(fn (Request $request) => $request->is('manage', 'manage/*')
            ? route('manage.dashboard')
            : '/');
    })
    ->withExceptions(function (Exceptions $exceptions) use ($wantsJson): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        /*
        |------------------------------------------------------------------
        | Consistent API error contract
        |------------------------------------------------------------------
        |
        | Every API failure carries a machine-readable `code` alongside the
        | human `message`, so the React Native client can branch on the code
        | instead of parsing prose or relying on the HTTP status alone.
        |
        | Returning null lets non-API requests fall through to Laravel's
        | default handling (redirects, Blade error pages).
        |
        */
        $exceptions->render(function (ValidationException $e, Request $request) use ($wantsJson) {
            if (! $wantsJson($request)) {
                return null;
            }

            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'validation_error',
                'errors' => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($wantsJson) {
            if (! $wantsJson($request)) {
                return null;
            }

            return response()->json([
                'message' => 'Unauthenticated.',
                'code' => 'unauthenticated',
            ], 401);
        });

        $exceptions->render(function (AccessDeniedHttpException|AuthorizationException $e, Request $request) use ($wantsJson) {
            if (! $wantsJson($request)) {
                return null;
            }

            return response()->json([
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'This action is unauthorized.',
                'code' => 'forbidden',
            ], 403);
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($wantsJson) {
            if (! $wantsJson($request)) {
                return null;
            }

            return response()->json([
                'message' => 'The requested record was not found.',
                'code' => 'not_found',
            ], 404);
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) use ($wantsJson) {
            if (! $wantsJson($request)) {
                return null;
            }

            return response()->json([
                'message' => 'Too many requests. Please slow down and try again shortly.',
                'code' => 'rate_limited',
                'retry_after' => (int) ($e->getHeaders()['Retry-After'] ?? 60),
            ], 429);
        });

        // A provider integration that does not exist yet is not a server fault;
        // it is an honest 501 with the outstanding requirements attached.
        $exceptions->render(function (IntegrationPendingException $e, Request $request) use ($wantsJson) {
            if (! $wantsJson($request)) {
                return null;
            }

            return response()->json($e->payload(), 501);
        });
    })->create();
