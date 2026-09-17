<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MaintenanceRecord;
use App\Models\MaintenanceReminder;
use App\Models\Vehicle;
use App\Services\Care\VehicleCareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceReminderController extends Controller
{
    public function __construct(
        protected VehicleCareService $careService,
    ) {}

    /**
     * List maintenance reminders for a vehicle.
     */
    public function index(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->careService->evaluateRemindersForVehicle($vehicle);

        $query = $vehicle->reminders()->with('maintenanceRecord:id,title,category');

        if ($request->input('status') === 'outstanding' || ! $request->has('status')) {
            $query->outstanding();
        } elseif ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        $reminders = $query->orderBy('due_at')->get();

        return response()->json([
            'vehicle_uuid' => $vehicle->uuid,
            'count' => $reminders->count(),
            'reminders' => $reminders,
        ]);
    }

    /**
     * Create a custom maintenance reminder.
     */
    public function store(Request $request, Vehicle $vehicle): JsonResponse
    {
        $validated = $request->validate([
            'category' => 'required|string|in:'.implode(',', MaintenanceRecord::CATEGORIES),
            'title' => 'required|string|max:120',
            'due_at' => 'nullable|date',
            'due_odometer_km' => 'nullable|integer|min:0',
        ]);

        if (empty($validated['due_at']) && empty($validated['due_odometer_km'])) {
            return response()->json([
                'message' => 'Please provide either a due date or a target odometer reading.',
                'errors' => ['due_at' => ['Either due_at or due_odometer_km is required.']],
            ], 422);
        }

        $user = $request->user();

        $reminder = MaintenanceReminder::create([
            'vehicle_id' => $vehicle->id,
            'user_id' => $user->id,
            'category' => $validated['category'],
            'title' => $validated['title'],
            'due_at' => $validated['due_at'] ?? null,
            'due_odometer_km' => $validated['due_odometer_km'] ?? null,
            'status' => MaintenanceReminder::STATUS_PENDING,
        ]);

        $this->careService->evaluateReminder($reminder, $vehicle);

        AuditLog::create([
            'actor_type' => 'user',
            'actor_id' => $user->id,
            'actor_label' => $user->name,
            'auditable_type' => 'maintenance_reminder',
            'auditable_id' => $reminder->id,
            'action' => 'maintenance.reminder_created',
            'group' => 'care',
            'description' => "Created reminder: {$reminder->title} for {$vehicle->display_name}",
            'severity' => AuditLog::SEVERITY_INFO,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Maintenance reminder created successfully.',
            'reminder' => $reminder->fresh(),
        ], 201);
    }

    /**
     * Mark a maintenance reminder as completed.
     */
    public function complete(Request $request, Vehicle $vehicle, MaintenanceReminder $reminder): JsonResponse
    {
        $this->ensureBelongsToVehicle($reminder, $vehicle);

        $reminder->update([
            'status' => MaintenanceReminder::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Reminder marked as completed.',
            'reminder' => $reminder->fresh(),
        ]);
    }

    /**
     * Dismiss a maintenance reminder.
     */
    public function dismiss(Request $request, Vehicle $vehicle, MaintenanceReminder $reminder): JsonResponse
    {
        $this->ensureBelongsToVehicle($reminder, $vehicle);

        $reminder->update([
            'status' => MaintenanceReminder::STATUS_DISMISSED,
            'dismissed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Reminder dismissed.',
            'reminder' => $reminder->fresh(),
        ]);
    }

    /**
     * Delete a maintenance reminder.
     */
    public function destroy(Request $request, Vehicle $vehicle, MaintenanceReminder $reminder): JsonResponse
    {
        $this->ensureBelongsToVehicle($reminder, $vehicle);

        $reminder->delete();

        return response()->json([
            'message' => 'Reminder deleted successfully.',
        ]);
    }

    protected function ensureBelongsToVehicle(MaintenanceReminder $reminder, Vehicle $vehicle): void
    {
        if ($reminder->vehicle_id !== $vehicle->id) {
            abort(404, 'Maintenance reminder not found for this vehicle.');
        }
    }
}
