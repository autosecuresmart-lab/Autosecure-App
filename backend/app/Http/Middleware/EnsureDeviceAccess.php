<?php

namespace App\Http\Middleware;

use App\Models\Device;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleAccessGrant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-side device authorisation.
 *
 * A device is reachable when the acting user can reach the vehicle it is bound
 * to, and — where a capability is required (camera video, device commands) — the
 * user's grant actually covers it. An unbound device is visible only to the user
 * it is reserved for.
 *
 * Usage: ->middleware('device.access') or 'device.access:video' / 'commands'.
 * The resolved device is attached to the request as `device`.
 */
class EnsureDeviceAccess
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
            return response()->json(['message' => 'Unauthenticated.', 'code' => 'unauthenticated'], 401);
        }

        $device = $this->resolveDevice($request);

        if (! $device instanceof Device) {
            return response()->json(['message' => 'Device not found.', 'code' => 'not_found'], 404);
        }

        $vehicle = $device->vehicle;

        // A device not yet bound to a vehicle is visible only to its reserved user.
        if (! $vehicle instanceof Vehicle) {
            if ($device->user_id !== null && (int) $device->user_id === (int) $user->getKey()) {
                $request->attributes->set('device', $device);

                return $next($request);
            }

            return response()->json([
                'message' => 'This device is not linked to a vehicle you can access.',
                'code' => 'device_forbidden',
            ], 403);
        }

        // Owner of the vehicle: full access.
        if ((int) $vehicle->user_id === (int) $user->getKey()) {
            $request->attributes->set('device', $device);
            $request->attributes->set('device_vehicle', $vehicle);

            return $next($request);
        }

        $grant = VehicleAccessGrant::query()
            ->where('vehicle_id', $vehicle->getKey())
            ->where('user_id', $user->getKey())
            ->first();

        if (! $grant instanceof VehicleAccessGrant || ! $grant->isActive()) {
            return response()->json([
                'message' => 'You do not have access to this device.',
                'code' => 'device_forbidden',
            ], 403);
        }

        if ($capability !== null) {
            $column = self::CAPABILITIES[$capability] ?? null;

            if ($column === null || ! $grant->{$column}) {
                return response()->json([
                    'message' => 'Your access to this device does not include '.$capability.'.',
                    'code' => 'device_capability_denied',
                ], 403);
            }
        }

        $request->attributes->set('device', $device);
        $request->attributes->set('device_vehicle', $vehicle);
        $request->attributes->set('device_grant', $grant);

        return $next($request);
    }

    protected function resolveDevice(Request $request): ?Device
    {
        $parameter = $request->route('device');

        if ($parameter instanceof Device) {
            return $parameter;
        }

        if (is_string($parameter) && $parameter !== '') {
            return Device::whereUuid($parameter)->first();
        }

        return null;
    }
}
