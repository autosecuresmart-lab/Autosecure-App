<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FuelRecord;
use App\Models\Vehicle;
use App\Services\Care\VehicleCareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FuelRecordController extends Controller
{
    public function __construct(
        protected VehicleCareService $careService,
    ) {}

    /**
     * List fuel fill-up logs for a vehicle.
     */
    public function index(Request $request, Vehicle $vehicle): JsonResponse
    {
        $query = $vehicle->fuelRecords();

        $records = $query->latest('filled_at')->paginate($request->input('per_page', 15));

        $totalLitres = (float) $vehicle->fuelRecords()->sum('litres');
        $totalCost = (float) $vehicle->fuelRecords()->sum('total_amount');

        return response()->json([
            'summary' => [
                'total_litres' => $totalLitres,
                'total_cost' => $totalCost,
                'average_price_per_litre' => $totalLitres > 0 ? round($totalCost / $totalLitres, 2) : 0,
            ],
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
     * Create a new fuel fill-up record.
     */
    public function store(Request $request, Vehicle $vehicle): JsonResponse
    {
        $validated = $request->validate([
            'filled_at' => 'required|date',
            'litres' => 'required|numeric|min:0.1|max:1000',
            'price_per_litre' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
            'odometer_km' => 'nullable|integer|min:0',
            'is_full_tank' => 'nullable|boolean',
            'station' => 'nullable|string|max:120',
            'notes' => 'nullable|string|max:500',
        ]);

        $litres = (float) $validated['litres'];
        $pricePerLitre = isset($validated['price_per_litre']) ? (float) $validated['price_per_litre'] : null;
        $totalAmount = isset($validated['total_amount']) ? (float) $validated['total_amount'] : null;

        if ($totalAmount === null && $pricePerLitre !== null) {
            $totalAmount = round($litres * $pricePerLitre, 2);
        } elseif ($pricePerLitre === null && $totalAmount !== null && $litres > 0) {
            $pricePerLitre = round($totalAmount / $litres, 2);
        }

        $user = $request->user();

        $record = FuelRecord::create([
            'vehicle_id' => $vehicle->id,
            'user_id' => $user->id,
            'filled_at' => $validated['filled_at'],
            'litres' => $litres,
            'price_per_litre' => $pricePerLitre ?? 0,
            'total_amount' => $totalAmount ?? 0,
            'odometer_km' => $validated['odometer_km'] ?? $vehicle->odometer_km,
            'is_full_tank' => $validated['is_full_tank'] ?? true,
            'station' => $validated['station'] ?? 'Fuel Station',
            'notes' => $validated['notes'] ?? null,
        ]);

        // If fuel fill-up odometer is higher, update vehicle odometer
        if (! empty($validated['odometer_km']) && $validated['odometer_km'] > (int) $vehicle->odometer_km) {
            $this->careService->updateOdometer($vehicle, (int) $validated['odometer_km'], 'manual', $user);
        }

        AuditLog::create([
            'actor_type' => 'user',
            'actor_id' => $user->id,
            'actor_label' => $user->name,
            'auditable_type' => 'fuel_record',
            'auditable_id' => $record->id,
            'action' => 'fuel.record_created',
            'group' => 'care',
            'description' => "Logged {$record->litres}L fuel fill-up at {$record->station} for {$vehicle->display_name}",
            'severity' => AuditLog::SEVERITY_INFO,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Fuel record created successfully.',
            'record' => $record,
        ], 201);
    }

    /**
     * Show a fuel record.
     */
    public function show(Request $request, Vehicle $vehicle, FuelRecord $record): JsonResponse
    {
        $this->ensureBelongsToVehicle($record, $vehicle);

        return response()->json([
            'record' => $record,
        ]);
    }

    /**
     * Update a fuel record.
     */
    public function update(Request $request, Vehicle $vehicle, FuelRecord $record): JsonResponse
    {
        $this->ensureBelongsToVehicle($record, $vehicle);

        $validated = $request->validate([
            'filled_at' => 'sometimes|required|date',
            'litres' => 'sometimes|required|numeric|min:0.1',
            'price_per_litre' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
            'odometer_km' => 'nullable|integer|min:0',
            'is_full_tank' => 'nullable|boolean',
            'station' => 'nullable|string|max:120',
            'notes' => 'nullable|string|max:500',
        ]);

        $record->update($validated);

        return response()->json([
            'message' => 'Fuel record updated successfully.',
            'record' => $record->fresh(),
        ]);
    }

    /**
     * Delete a fuel record.
     */
    public function destroy(Request $request, Vehicle $vehicle, FuelRecord $record): JsonResponse
    {
        $this->ensureBelongsToVehicle($record, $vehicle);

        $record->delete();

        return response()->json([
            'message' => 'Fuel record deleted successfully.',
        ]);
    }

    protected function ensureBelongsToVehicle(FuelRecord $record, Vehicle $vehicle): void
    {
        if ($record->vehicle_id !== $vehicle->id) {
            abort(404, 'Fuel record not found for this vehicle.');
        }
    }
}
