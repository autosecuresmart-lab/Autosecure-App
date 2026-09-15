<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authorises a staff request against a permission slug, e.g.
 * ->middleware('admin.permission:vendors.verify').
 *
 * Multiple slugs may be supplied; any one of them is enough.
 */
class EnsureAdminHasPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $admin = Auth::guard('admin')->user();

        if ($admin === null) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->route('manage.login');
        }

        if (! $admin->isActive()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'This staff account is not active.'], 403)
                : redirect()->route('manage.login');
        }

        if ($permissions !== [] && ! $admin->hasAnyPermission($permissions)) {
            return $request->expectsJson()
                ? response()->json([
                    'message' => 'You do not have permission to perform this action.',
                    'required' => $permissions,
                ], 403)
                : response()->view('manage.errors.forbidden', [
                    'required' => $permissions,
                ], 403);
        }

        return $next($request);
    }
}
