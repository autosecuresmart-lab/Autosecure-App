<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Vehicle;
use App\Services\AutoDoc\AutoDocService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AutoDocController extends Controller
{
    public function __construct(
        protected AutoDocService $autoDocService,
    ) {}

    /**
     * Create an authorized Single Sign-On launch session and deep link for AutoDoc.
     */
    public function launch(Request $request, Vehicle $vehicle): JsonResponse
    {
        $user = $request->user();
        $session = $this->autoDocService->createLaunchSession($user, $vehicle);

        AuditLog::create([
            'actor_type' => 'user',
            'actor_id' => $user->id,
            'actor_label' => $user->name,
            'auditable_type' => 'vehicle',
            'auditable_id' => $vehicle->id,
            'action' => 'autodoc.launch_session_created',
            'group' => 'autodoc',
            'description' => "Launched AutoDoc session for {$vehicle->display_name}",
            'severity' => AuditLog::SEVERITY_INFO,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'AutoDoc launch session generated.',
            'session' => $session,
        ]);
    }

    /**
     * Get statutory vehicle documentation summary and expiry warnings from AutoDoc.
     */
    public function documents(Request $request, Vehicle $vehicle): JsonResponse
    {
        $summary = $this->autoDocService->fetchDocumentSummary($vehicle);

        return response()->json($summary);
    }

    /**
     * Associate a vehicle with an AutoDoc account or vehicle reference.
     */
    public function associate(Request $request, Vehicle $vehicle): JsonResponse
    {
        $validated = $request->validate([
            'autodoc_vehicle_ref' => 'required|string|max:120',
        ]);

        $updatedVehicle = $this->autoDocService->associateVehicle(
            vehicle: $vehicle,
            autodocRef: $validated['autodoc_vehicle_ref'],
        );

        return response()->json([
            'message' => 'Vehicle successfully mapped to AutoDoc record.',
            'vehicle' => [
                'uuid' => $updatedVehicle->uuid,
                'autodoc_vehicle_ref' => $updatedVehicle->autodoc_vehicle_ref,
            ],
        ]);
    }

    /**
     * Get live OBD-II diagnostic fault codes and sensor telemetry.
     */
    public function diagnostics(Request $request, Vehicle $vehicle): JsonResponse
    {
        $data = $this->autoDocService->diagnostics($vehicle);

        return response()->json($data);
    }

    /**
     * Clear stored ECU DTC diagnostic trouble codes.
     */
    public function clearCodes(Request $request, Vehicle $vehicle): JsonResponse
    {
        $result = $this->autoDocService->clearCodes($vehicle, $request->user());

        return response()->json($result);
    }
}
