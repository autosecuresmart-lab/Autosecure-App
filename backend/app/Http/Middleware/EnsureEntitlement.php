<?php

namespace App\Http\Middleware;

use App\Services\Subscriptions\EntitlementService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-side subscription gate, e.g. ->middleware('entitlement:care.oil_change').
 *
 * Core (free) features pass through untouched — a lapsed subscription must never
 * remove the tracker or dashcam security functions.
 */
class EnsureEntitlement
{
    public function __construct(protected EntitlementService $entitlements) {}

    public function handle(Request $request, Closure $next, string ...$features): Response
    {
        $user = Auth::guard('sanctum')->user() ?? $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $entitlements = $this->entitlements;

        $missing = collect($features)
            ->reject(fn (string $feature) => $entitlements->can($user, $feature))
            ->values();

        if ($missing->isNotEmpty()) {
            return response()->json([
                'message' => 'This feature requires an active AUTOSECURE Premium subscription.',
                'code' => 'premium_required',
                'required_features' => $missing->all(),
            ], 402);
        }

        return $next($request);
    }
}
