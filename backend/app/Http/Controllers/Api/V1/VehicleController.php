<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\VehicleResource;
use App\Models\Vehicle;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * My Vehicle module.
 *
 * All access is resolved server side: a customer only ever sees vehicles they
 * own or hold an active grant for, and route binding uses the vehicle uuid.
 */
class VehicleController extends Controller
{
    public function __construct(protected AuditLogger $audit) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $vehicles = Vehicle::query()
            ->with('devices')
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->getKey())
                    ->orWhereIn(
                        'id',
                        $user->vehicleGrants()
                            ->whereNull('revoked_at')
                            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                            ->select('vehicle_id'),
                    );
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('is_primary')
            ->orderBy('plate_number')
            ->get();

        return VehicleResource::collection($vehicles);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'nickname' => ['nullable', 'string', 'max:120'],
            'plate_number' => ['required', 'string', 'max:32'],
            'make' => ['nullable', 'string', 'max:64'],
            'model' => ['nullable', 'string', 'max:64'],
            'year' => ['nullable', 'integer', 'between:1950,'.(now()->year + 1)],
            'colour' => ['nullable', 'string', 'max:32'],
            'vin' => ['nullable', 'string', 'max:64', 'unique:vehicles,vin'],
            'fuel_type' => ['nullable', Rule::in(['petrol', 'diesel', 'hybrid', 'electric', 'cng', 'other'])],
            'transmission' => ['nullable', Rule::in(['manual', 'automatic'])],
            'odometer_km' => ['nullable', 'integer', 'min:0'],
        ]);

        $vehicle = DB::transaction(function () use ($user, $data) {
            $isFirst = ! $user->vehicles()->exists();

            $vehicle = $user->vehicles()->create($data + [
                'is_primary' => $isFirst,
                'odometer_source' => 'manual',
                'odometer_updated_at' => isset($data['odometer_km']) ? now() : null,
            ]);

            // The owner is recorded as an explicit grant holder so downstream
            // permission checks have a single shape to reason about.
            $vehicle->accessGrants()->create([
                'user_id' => $user->getKey(),
                'granted_by_user_id' => $user->getKey(),
                'role' => 'owner',
                'can_view_location' => true,
                'can_view_video' => true,
                'can_send_commands' => true,
                'can_manage_care_records' => true,
            ]);

            return $vehicle;
        });

        $this->audit->log('vehicles.created', $vehicle, $user);

        return response()->json([
            'message' => 'Vehicle added.',
            'vehicle' => new VehicleResource($vehicle->load('devices')),
        ], 201);
    }

    public function show(Request $request, Vehicle $vehicle): JsonResponse
    {
        /** @var Vehicle $vehicle */
        $vehicle = $request->attributes->get('vehicle') ?? $vehicle;

        return response()->json([
            'vehicle' => new VehicleResource($vehicle->load('devices')),
        ]);
    }

    public function update(Request $request, Vehicle $vehicle): JsonResponse
    {
        $vehicle = $request->attributes->get('vehicle') ?? $vehicle;

        abort_unless(
            (int) $vehicle->user_id === (int) $request->user()->getKey(),
            403,
            'Only the vehicle owner can change these details.',
        );

        $data = $request->validate([
            'nickname' => ['nullable', 'string', 'max:120'],
            'plate_number' => ['sometimes', 'string', 'max:32'],
            'make' => ['nullable', 'string', 'max:64'],
            'model' => ['nullable', 'string', 'max:64'],
            'year' => ['nullable', 'integer', 'between:1950,'.(now()->year + 1)],
            'colour' => ['nullable', 'string', 'max:32'],
            'fuel_type' => ['nullable', Rule::in(['petrol', 'diesel', 'hybrid', 'electric', 'cng', 'other'])],
            'transmission' => ['nullable', Rule::in(['manual', 'automatic'])],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'archived'])],
            'odometer_km' => ['nullable', 'integer', 'min:0'],
        ]);

        $original = $vehicle->only(array_keys($data));
        $vehicle->fill($data);

        // A manual odometer entry is always recorded as such, and the change is
        // audited so a tracker-reported value can be distinguished later.
        if (array_key_exists('odometer_km', $data)) {
            $vehicle->odometer_source = 'manual';
            $vehicle->odometer_updated_at = now();
        }

        $vehicle->save();

        $this->audit->log('vehicles.updated', $vehicle, $request->user(), [
            'before' => $original,
            'after' => $vehicle->only(array_keys($data)),
        ]);

        return response()->json([
            'message' => 'Vehicle updated.',
            'vehicle' => new VehicleResource($vehicle->load('devices')),
        ]);
    }

    public function destroy(Request $request, Vehicle $vehicle): JsonResponse
    {
        $vehicle = $request->attributes->get('vehicle') ?? $vehicle;

        abort_unless(
            (int) $vehicle->user_id === (int) $request->user()->getKey(),
            403,
            'Only the vehicle owner can remove this vehicle.',
        );

        $vehicle->delete();

        $this->audit->log('vehicles.deleted', $vehicle, $request->user(), severity: 'notice');

        return response()->json(['message' => 'Vehicle removed.']);
    }

    /**
     * Devices bound to a vehicle (tracker + dashcam).
     */
    public function devices(Request $request, Vehicle $vehicle): JsonResponse
    {
        $vehicle = $request->attributes->get('vehicle') ?? $vehicle;

        return response()->json([
            'devices' => \App\Http\Resources\Api\V1\DeviceResource::collection(
                $vehicle->devices()->get(),
            ),
        ]);
    }
}
