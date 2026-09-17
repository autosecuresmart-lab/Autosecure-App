<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MaintenanceRecord;
use App\Models\Vehicle;
use App\Services\Care\VehicleCareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceRecordController extends Controller
{
    public function __construct(
        protected VehicleCareService $careService,
    ) {}

    /**
     * List maintenance records for a vehicle.
     */
    public function index(Request $request, Vehicle $vehicle): JsonResponse
    {
        $query = $vehicle->maintenanceRecords()->with('vendor:id,name');

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('search')) {
            $term = '%'.$request->input('search').'%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('workshop_name', 'like', $term);
            });
        }

        $records = $query->latest('performed_at')->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => $records->items(),
            'meta' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
            ],
        ]);
    }

    /**
     * Create a new maintenance record.
     */
    public function store(Request $request, Vehicle $vehicle): JsonResponse
    {
        $validated = $request->validate([
            'category' => 'required|string|in:'.implode(',', MaintenanceRecord::CATEGORIES),
            'title' => 'required|string|max:120',
            'description' => 'nullable|string|max:1000',
            'performed_at' => 'required|date',
            'odometer_km' => 'nullable|integer|min:0',
            'vendor_id' => 'nullable|exists:vendors,id',
            'workshop_name' => 'nullable|string|max:120',
            'cost' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'details' => 'nullable|array',
            'attachments' => 'nullable|array',
            'notes' => 'nullable|string|max:1000',
            'next_due_at' => 'nullable|date|after:performed_at',
            'next_due_odometer_km' => 'nullable|integer|min:0',
        ]);

        $user = $request->user();

        $record = MaintenanceRecord::create([
            'vehicle_id' => $vehicle->id,
            'user_id' => $user->id,
            'category' => $validated['category'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'performed_at' => $validated['performed_at'],
            'odometer_km' => $validated['odometer_km'] ?? $vehicle->odometer_km,
            'vendor_id' => $validated['vendor_id'] ?? null,
            'workshop_name' => $validated['workshop_name'] ?? null,
            'cost' => $validated['cost'] ?? 0,
            'currency' => $validated['currency'] ?? 'NGN',
            'details' => $validated['details'] ?? null,
            'attachments' => $validated['attachments'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'next_due_at' => $validated['next_due_at'] ?? null,
            'next_due_odometer_km' => $validated['next_due_odometer_km'] ?? null,
            'reminder_status' => ($validated['next_due_at'] ?? null || $validated['next_due_odometer_km'] ?? null)
                ? MaintenanceRecord::REMINDER_UPCOMING
                : null,
            'odometer_source' => 'manual',
        ]);

        // If odometer in service is higher than vehicle's current odometer, update vehicle odometer
        if (! empty($validated['odometer_km']) && $validated['odometer_km'] > (int) $vehicle->odometer_km) {
            $this->careService->updateOdometer($vehicle, (int) $validated['odometer_km'], 'manual', $user);
        }

        // Automatically create associated reminder if next due target provided
        $reminder = $this->careService->createReminderFromMaintenance($record, $user);

        AuditLog::create([
            'actor_type' => 'user',
            'actor_id' => $user->id,
            'actor_label' => $user->name,
            'auditable_type' => 'maintenance_record',
            'auditable_id' => $record->id,
            'action' => 'maintenance.record_created',
            'group' => 'care',
            'description' => "Logged {$record->category} service ({$record->title}) for {$vehicle->display_name}",
            'severity' => AuditLog::SEVERITY_INFO,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Maintenance record created successfully.',
            'record' => $record->load('vendor:id,name'),
            'reminder' => $reminder,
        ], 201);
    }

    /**
     * Show maintenance record details.
     */
    public function show(Request $request, Vehicle $vehicle, MaintenanceRecord $record): JsonResponse
    {
        $this->ensureBelongsToVehicle($record, $vehicle);

        return response()->json([
            'record' => $record->load(['vendor:id,name', 'reminders']),
        ]);
    }

    /**
     * Update maintenance record.
     */
    public function update(Request $request, Vehicle $vehicle, MaintenanceRecord $record): JsonResponse
    {
        $this->ensureBelongsToVehicle($record, $vehicle);

        $validated = $request->validate([
            'category' => 'sometimes|required|string|in:'.implode(',', MaintenanceRecord::CATEGORIES),
            'title' => 'sometimes|required|string|max:120',
            'description' => 'nullable|string|max:1000',
            'performed_at' => 'sometimes|required|date',
            'odometer_km' => 'nullable|integer|min:0',
            'vendor_id' => 'nullable|exists:vendors,id',
            'workshop_name' => 'nullable|string|max:120',
            'cost' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'details' => 'nullable|array',
            'attachments' => 'nullable|array',
            'notes' => 'nullable|string|max:1000',
            'next_due_at' => 'nullable|date',
            'next_due_odometer_km' => 'nullable|integer|min:0',
        ]);

        $record->update($validated);

        return response()->json([
            'message' => 'Maintenance record updated successfully.',
            'record' => $record->fresh()->load('vendor:id,name'),
        ]);
    }

    /**
     * Delete maintenance record.
     */
    public function destroy(Request $request, Vehicle $vehicle, MaintenanceRecord $record): JsonResponse
    {
        $this->ensureBelongsToVehicle($record, $vehicle);

        $record->delete();

        return response()->json([
            'message' => 'Maintenance record deleted successfully.',
        ]);
    }

    protected function ensureBelongsToVehicle(MaintenanceRecord $record, Vehicle $vehicle): void
    {
        if ($record->vehicle_id !== $vehicle->id) {
            abort(404, 'Maintenance record not found for this vehicle.');
        }
    }
}
