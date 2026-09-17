<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DeviceResource;
use App\Models\Device;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Admin Device & Hardware Inventory Management.
 *
 * Allows administrators to add GPS trackers and dashcams to inventory, bind
 * devices to specific vehicles and users, and unbind or decommission units.
 */
class AdminDeviceController extends Controller
{
    public function __construct(protected AuditLogger $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = Device::query()
            ->with(['vehicle', 'user'])
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(function ($sub) use ($search) {
                    $sub->where('serial_number', 'like', "%{$search}%")
                        ->orWhere('imei', 'like', "%{$search}%")
                        ->orWhere('label', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at');

        $devices = $query->paginate($request->integer('per_page', 20));

        return response()->json([
            'data' => DeviceResource::collection($devices->items()),
            'meta' => [
                'current_page' => $devices->currentPage(),
                'last_page' => $devices->lastPage(),
                'per_page' => $devices->perPage(),
                'total' => $devices->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['tracker', 'dashcam'])],
            'serial_number' => ['required', 'string', 'max:64', 'unique:devices,serial_number'],
            'imei' => ['nullable', 'string', 'max:64', 'unique:devices,imei'],
            'label' => ['nullable', 'string', 'max:120'],
            'brand' => ['nullable', 'string', 'max:64'],
            'model' => ['nullable', 'string', 'max:64'],
            'sim_number' => ['nullable', 'string', 'max:32'],
            'phone_number' => ['nullable', 'string', 'max:32'],
            'provider' => ['nullable', 'string', 'max:64'],
            'status' => ['nullable', 'string'],
            'vehicle_id' => ['nullable'], // optional UUID or ID to bind immediately
            'user_id' => ['nullable'],
        ]);

        $vehicle = null;
        $user = null;

        if (! empty($data['vehicle_id'])) {
            $vehicle = Vehicle::query()
                ->where('uuid', $data['vehicle_id'])
                ->orWhere('id', $data['vehicle_id'])
                ->firstOrFail();
            $data['vehicle_id'] = $vehicle->id;
            $data['user_id'] = $vehicle->user_id;
            $data['bound_at'] = now();
        } elseif (! empty($data['user_id'])) {
            $user = User::query()
                ->where('uuid', $data['user_id'])
                ->orWhere('id', $data['user_id'])
                ->firstOrFail();
            $data['user_id'] = $user->id;
        }

        $device = Device::create($data + [
            'status' => $data['status'] ?? (isset($data['vehicle_id']) ? 'active' : 'unbound'),
            'is_online' => true,
        ]);

        $this->audit->log('admin.devices.created', $device, $request->user());

        return response()->json([
            'message' => 'Device created successfully.',
            'device' => new DeviceResource($device->load(['vehicle', 'user'])),
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $device = Device::query()
            ->with(['vehicle.user', 'user'])
            ->where('uuid', $id)
            ->orWhere('id', $id)
            ->firstOrFail();

        return response()->json([
            'device' => new DeviceResource($device),
        ]);
    }

    public function bind(Request $request, string $id): JsonResponse
    {
        $device = Device::query()
            ->where('uuid', $id)
            ->orWhere('id', $id)
            ->firstOrFail();

        $data = $request->validate([
            'vehicle_id' => ['required'], // UUID or numeric ID
        ]);

        $vehicle = Vehicle::query()
            ->where('uuid', $data['vehicle_id'])
            ->orWhere('id', $data['vehicle_id'])
            ->firstOrFail();

        $device->update([
            'vehicle_id' => $vehicle->id,
            'user_id' => $vehicle->user_id,
            'bound_at' => now(),
            'unbound_at' => null,
            'status' => 'active',
        ]);

        $this->audit->log('admin.devices.bound', $device, $request->user());

        return response()->json([
            'message' => "Device bound to {$vehicle->displayName()} successfully.",
            'device' => new DeviceResource($device->fresh()->load(['vehicle', 'user'])),
        ]);
    }

    public function unbind(Request $request, string $id): JsonResponse
    {
        $device = Device::query()
            ->where('uuid', $id)
            ->orWhere('id', $id)
            ->firstOrFail();

        $device->update([
            'vehicle_id' => null,
            'unbound_at' => now(),
            'status' => 'unbound',
        ]);

        $this->audit->log('admin.devices.unbound', $device, $request->user());

        return response()->json([
            'message' => 'Device unbound from vehicle.',
            'device' => new DeviceResource($device->fresh()),
        ]);
    }
}
