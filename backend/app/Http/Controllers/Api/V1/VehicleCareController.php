<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Vehicle;
use App\Services\Care\VehicleCareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * High-level Vehicle Care operations: summary dashboard, odometer tracking, and timeline.
 */
class VehicleCareController extends Controller
{
    public function __construct(
        protected VehicleCareService $careService,
    ) {}

    /**
     * Get the consolidated vehicle care dashboard metrics, health summaries, and reminders.
     */
    public function dashboard(Request $request, Vehicle $vehicle): JsonResponse
    {
        $data = $this->careService->dashboard($vehicle);

        return response()->json($data);
    }

    /**
     * Update vehicle odometer reading.
     */
    public function updateOdometer(Request $request, Vehicle $vehicle): JsonResponse
    {
        $validated = $request->validate([
            'odometer_km' => 'required|integer|min:0|max:2000000',
            'source' => 'nullable|string|in:manual,tracker',
        ]);

        $previousKm = $vehicle->odometer_km;
        $newKm = (int) $validated['odometer_km'];
        $source = $validated['source'] ?? 'manual';
        $user = $request->user();

        $updatedVehicle = $this->careService->updateOdometer(
            vehicle: $vehicle,
            odometerKm: $newKm,
            source: $source,
            user: $user,
        );

        AuditLog::create([
            'actor_type' => 'user',
            'actor_id' => $user->id,
            'actor_label' => $user->name,
            'auditable_type' => 'vehicle',
            'auditable_id' => $vehicle->id,
            'action' => 'vehicle.odometer_updated',
            'group' => 'care',
            'description' => "Odometer updated from {$previousKm} km to {$newKm} km ({$source})",
            'changes' => ['previous_km' => $previousKm, 'new_km' => $newKm, 'source' => $source],
            'severity' => AuditLog::SEVERITY_INFO,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Vehicle odometer updated successfully.',
            'vehicle' => [
                'uuid' => $updatedVehicle->uuid,
                'odometer_km' => $updatedVehicle->odometer_km,
                'odometer_source' => $updatedVehicle->odometer_source,
                'odometer_updated_at' => $updatedVehicle->odometer_updated_at?->format(DATE_ATOM),
            ],
        ]);
    }

    /**
     * Get the unified chronological care timeline (maintenance, fuel fill-ups, reminders).
     */
    public function timeline(Request $request, Vehicle $vehicle): JsonResponse
    {
        $limit = min(50, max(5, (int) $request->input('limit', 25)));
        $events = $this->careService->timeline($vehicle, $limit);

        return response()->json([
            'vehicle_uuid' => $vehicle->uuid,
            'count' => count($events),
            'timeline' => $events,
        ]);
    }
}
