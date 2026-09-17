<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\VehicleResource;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Admin Vehicle Management.
 *
 * Allows administrators to list all vehicles, assign a new vehicle to any user,
 * update vehicle parameters, or remove vehicles.
 */
class AdminVehicleController extends Controller
{
    public function __construct(protected AuditLogger $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = Vehicle::query()
            ->with(['user', 'devices'])
            ->when($request->filled('user_id'), function ($q) use ($request) {
                $userParam = $request->string('user_id');
                $q->whereHas('user', fn ($sub) => $sub->where('uuid', $userParam)->orWhere('id', $userParam));
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(function ($sub) use ($search) {
                    $sub->where('plate_number', 'like', "%{$search}%")
                        ->orWhere('nickname', 'like', "%{$search}%")
                        ->orWhere('make', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhere('vin', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('created_at');

        $vehicles = $query->paginate($request->integer('per_page', 20));

        return response()->json([
            'data' => VehicleResource::collection($vehicles->items()),
            'meta' => [
                'current_page' => $vehicles->currentPage(),
                'last_page' => $vehicles->lastPage(),
                'per_page' => $vehicles->perPage(),
                'total' => $vehicles->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required'], // user UUID or numeric ID
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
            'status' => ['nullable', 'string'],
        ]);

        $user = User::query()
            ->where('uuid', $data['user_id'])
            ->orWhere('id', $data['user_id'])
            ->firstOrFail();

        $vehicle = DB::transaction(function () use ($user, $data) {
            $isFirst = ! $user->vehicles()->exists();

            $vehicleData = $data;
            unset($vehicleData['user_id']);

            $vehicle = $user->vehicles()->create($vehicleData + [
                'is_primary' => $isFirst,
                'odometer_source' => 'manual',
                'odometer_updated_at' => isset($data['odometer_km']) ? now() : null,
                'status' => $data['status'] ?? 'active',
            ]);

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

        $this->audit->log('admin.vehicles.created', $vehicle, $request->user());

        return response()->json([
            'message' => 'Vehicle assigned to user successfully.',
            'vehicle' => new VehicleResource($vehicle->load(['user', 'devices'])),
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $vehicle = Vehicle::query()
            ->with(['user', 'devices'])
            ->where('uuid', $id)
            ->orWhere('id', $id)
            ->firstOrFail();

        return response()->json([
            'vehicle' => new VehicleResource($vehicle),
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $vehicle = Vehicle::query()
            ->where('uuid', $id)
            ->orWhere('id', $id)
            ->firstOrFail();

        $data = $request->validate([
            'nickname' => ['nullable', 'string', 'max:120'],
            'plate_number' => ['nullable', 'string', 'max:32'],
            'make' => ['nullable', 'string', 'max:64'],
            'model' => ['nullable', 'string', 'max:64'],
            'year' => ['nullable', 'integer', 'between:1950,'.(now()->year + 1)],
            'colour' => ['nullable', 'string', 'max:32'],
            'vin' => ['nullable', 'string', 'max:64', Rule::unique('vehicles', 'vin')->ignore($vehicle->id)],
            'fuel_type' => ['nullable', Rule::in(['petrol', 'diesel', 'hybrid', 'electric', 'cng', 'other'])],
            'transmission' => ['nullable', Rule::in(['manual', 'automatic'])],
            'odometer_km' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string'],
        ]);

        $vehicle->update(array_filter($data));

        $this->audit->log('admin.vehicles.updated', $vehicle, $request->user());

        return response()->json([
            'message' => 'Vehicle updated successfully.',
            'vehicle' => new VehicleResource($vehicle->fresh()->load(['user', 'devices'])),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $vehicle = Vehicle::query()
            ->where('uuid', $id)
            ->orWhere('id', $id)
            ->firstOrFail();

        $vehicle->delete();

        $this->audit->log('admin.vehicles.deleted', $vehicle, $request->user());

        return response()->json([
            'message' => 'Vehicle removed.',
        ]);
    }
}
