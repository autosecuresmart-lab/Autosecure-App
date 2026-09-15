<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleAccessGrant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-side vehicle ownership / sharing enforcement.
 *
 * Usage: ->middleware('vehicle.access') or with a required capability, e.g.
 * ->middleware('vehicle.access:location') / 'video' / 'commands' / 'care'.
 *
 * The request must carry the vehicle as a route parameter (`{vehicle}`, resolved
 * by uuid). The resolved grant is attached to the request as `vehicle_grant`
 * (null for the owner) so downstream code can reuse it.
 */
class EnsureVehicleAccess
{
    /** Capability map: middleware argument => grant column. */
    protected const CAPABILITIES = [
        'location' => 'can_view_location',
        'video' => 'can_view_video',
        'commands' => 'can_send_commands',
        'care' => 'can_manage_care_records',
    ];

    public function handle(Request $request, Closure $next, ?string $capability = null): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $vehicle = $this->resolveVehicle($request);

        if (! $vehicle instanceof Vehicle) {
            return response()->json(['message' => 'Vehicle not found.'], 404);
        }

        // The owner always has full access.
        if ((int) $vehicle->user_id === (int) $user->getKey()) {
            $request->attributes->set('vehicle', $vehicle);
            $request->attributes->set('vehicle_grant', null);

            return $next($request);
        }

        $grant = VehicleAccessGrant::query()
            ->where('vehicle_id', $vehicle->getKey())
            ->where('user_id', $user->getKey())
            ->first();

        if (! $grant instanceof VehicleAccessGrant || ! $grant->isActive()) {
            return response()->json([
                'message' => 'You do not have access to this vehicle.',
                'code' => 'vehicle_forbidden',
            ], 403);
        }

        if ($capability !== null) {
            $column = self::CAPABILITIES[$capability] ?? null;

            if ($column === null || ! $grant->{$column}) {
                return response()->json([
                    'message' => 'Your access to this vehicle does not include '.$capability.'.',
                    'code' => 'vehicle_capability_denied',
                ], 403);
            }
        }

        $request->attributes->set('vehicle', $vehicle);
        $request->attributes->set('vehicle_grant', $grant);

        return $next($request);
    }

    protected function resolveVehicle(Request $request): ?Vehicle
    {
        $parameter = $request->route('vehicle');

        if ($parameter instanceof Vehicle) {
            return $parameter;
        }

        if (is_string($parameter) && $parameter !== '') {
            return Vehicle::whereUuid($parameter)->first();
        }

        // Fall back to an explicit vehicle uuid in the request body for routes
        // that cannot carry a route parameter (e.g. bulk endpoints).
        $uuid = $request->input('vehicle_uuid') ?? $request->header('X-Vehicle-Uuid');

        return is_string($uuid) && $uuid !== '' ? Vehicle::whereUuid($uuid)->first() : null;
    }
}
