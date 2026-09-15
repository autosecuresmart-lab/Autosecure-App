<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DeviceResource;
use App\Models\Device;
use App\Models\Vehicle;
use App\Services\Devices\DeviceBindingService;
use App\Services\Devices\DeviceProviderManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Device foundation.
 *
 * Device *lifecycle* is platform-owned and implemented here — which vehicle a
 * device belongs to, what it is called, and who may see it. Device *data*
 * (location, video, commands) comes from the provider and is not implemented:
 * see App\Contracts\Devices and docs/phase-1/DEVICE-INTEGRATION.md.
 */
class DeviceController extends Controller
{
    public function __construct(
        protected DeviceBindingService $binding,
        protected DeviceProviderManager $providers,
    ) {}

    /**
     * Every device the customer can reach, across all their vehicles.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'type' => ['sometimes', Rule::in([Device::TYPE_TRACKER, Device::TYPE_DASHCAM])],
            'vehicle' => ['sometimes', 'string', 'uuid'],
        ]);

        $devices = Device::query()
            ->visibleTo($user)
            ->with('vehicle')
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')->toString()))
            ->when($request->filled('vehicle'), function ($q) use ($request) {
                $vehicle = Vehicle::whereUuid($request->string('vehicle')->toString())->first();

                // An unknown vehicle uuid matches nothing rather than everything.
                $q->where('vehicle_id', $vehicle?->getKey() ?? 0);
            })
            ->orderBy('type')
            ->orderBy('label')
            ->get();

        return response()->json([
            'data' => DeviceResource::collection($devices)->resolve(),
            'meta' => [
                'total' => $devices->count(),
                'trackers' => $devices->where('type', Device::TYPE_TRACKER)->count(),
                'dashcams' => $devices->where('type', Device::TYPE_DASHCAM)->count(),
                'providers' => [
                    'tracker' => $this->providers->driverName(DeviceProviderManager::TYPE_TRACKER),
                    'tracker_configured' => $this->providers->isConfigured(DeviceProviderManager::TYPE_TRACKER),
                    'dashcam' => $this->providers->driverName(DeviceProviderManager::TYPE_DASHCAM),
                    'dashcam_configured' => $this->providers->isConfigured(DeviceProviderManager::TYPE_DASHCAM),
                ],
            ],
        ]);
    }

    public function show(Request $request, Device $device): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device') ?? $device;

        return response()->json([
            'device' => new DeviceResource($device->loadMissing('vehicle')),
        ]);
    }

    /**
     * Rename a device.
     *
     * Only the label is customer-editable: brand, model, serial, IMEI and SIM are
     * provisioning facts and changing them would decouple our record from the
     * physical hardware.
     */
    public function update(Request $request, Device $device): JsonResponse
    {
        $device = $request->attributes->get('device') ?? $device;

        $data = $request->validate([
            'label' => ['sometimes', 'nullable', 'string', 'max:120'],
        ]);

        $device->fill($data)->save();

        return response()->json([
            'message' => 'Device updated.',
            'device' => new DeviceResource($device->fresh()->loadMissing('vehicle')),
        ]);
    }

    /**
     * Bind a device that AUTOSECURE reserved for this customer to a vehicle.
     *
     * Manual QR/barcode pairing (which proves physical possession) needs the
     * provider's pairing contract and is therefore not implemented. Until then
     * only a device already reserved to the signed-in customer can be bound, so
     * a guessed identifier cannot claim hardware.
     */
    public function bind(Request $request, Device $device): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device') ?? $device;

        $data = $request->validate([
            'vehicle_uuid' => ['required', 'string', 'uuid'],
        ]);

        $user = $request->user();

        $vehicle = Vehicle::whereUuid($data['vehicle_uuid'])->first();

        if ($vehicle === null) {
            return response()->json([
                'message' => 'Vehicle not found.',
                'code' => 'not_found',
            ], 404);
        }

        // Only the vehicle owner may attach hardware to it.
        if ((int) $vehicle->user_id !== (int) $user->getKey()) {
            return response()->json([
                'message' => 'You can only add devices to your own vehicles.',
                'code' => 'vehicle_forbidden',
            ], 403);
        }

        /*
         * Reservation is checked before the already-bound case on purpose.
         * EnsureDeviceAccess already decides whether the device is reachable, but
         * the check is repeated here as defence in depth: binding hardware is a
         * possession-sensitive action, and a permissive change to the middleware
         * must not be enough to let somebody claim a device reserved for another
         * customer.
         */
        if (! $this->isReservedFor($device, $request)) {
            return response()->json([
                'message' => 'This device is not reserved for your account. '
                    .'Pairing by QR code or barcode will be available once the device provider is integrated.',
                'code' => 'device_not_reserved',
            ], 403);
        }

        if ($device->vehicle_id !== null) {
            return response()->json([
                'message' => 'This device is already linked to a vehicle. Unbind it first.',
                'code' => 'device_already_bound',
            ], 409);
        }

        $device = $this->binding->bind($device, $vehicle, $user);

        return response()->json([
            'message' => 'Device linked to '.$vehicle->displayName().'.',
            'device' => new DeviceResource($device->loadMissing('vehicle')),
        ]);
    }

    /**
     * Detach a device from its vehicle.
     */
    public function unbind(Request $request, Device $device): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device') ?? $device;

        $data = $request->validate([
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();

        // Unbinding removes access for everyone sharing the vehicle, so it is an
        // owner action, not a shared-driver one.
        $vehicle = $device->vehicle;

        if ($vehicle !== null && (int) $vehicle->user_id !== (int) $user->getKey()) {
            return response()->json([
                'message' => 'Only the vehicle owner can unlink a device.',
                'code' => 'device_forbidden',
            ], 403);
        }

        $device = $this->binding->unbind($device, $user, $data['reason'] ?? null);

        return response()->json([
            'message' => 'Device unlinked. It can now be added to another vehicle.',
            'device' => new DeviceResource($device),
            'note' => 'Release on the device provider platform is still outstanding and '
                .'will complete once the provider integration is in place.',
        ]);
    }

    /**
     * A device is claimable when the platform reserved it for this customer, or
     * it is already theirs.
     */
    protected function isReservedFor(Device $device, Request $request): bool
    {
        return $device->user_id !== null
            && (int) $device->user_id === (int) $request->user()->getKey();
    }
}
