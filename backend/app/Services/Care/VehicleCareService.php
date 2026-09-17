<?php

namespace App\Services\Care;

use App\Models\FuelRecord;
use App\Models\MaintenanceRecord;
use App\Models\MaintenanceReminder;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Service orchestrating Vehicle Care memory, odometer tracking, service logs,
 * fuel statistics, timeline events, and automated reminder state transitions.
 */
class VehicleCareService
{
    /**
     * Compute comprehensive vehicle care summary dashboard metrics.
     *
     * @return array<string, mixed>
     */
    public function dashboard(Vehicle $vehicle): array
    {
        $this->evaluateRemindersForVehicle($vehicle);

        $maintenanceQuery = $vehicle->maintenanceRecords();
        $fuelQuery = $vehicle->fuelRecords();
        $remindersQuery = $vehicle->reminders()->outstanding();

        $totalMaintenanceCost = (float) $maintenanceQuery->sum('cost');
        $totalServicesCount = $maintenanceQuery->count();

        $totalFuelLitres = (float) $fuelQuery->sum('litres');
        $totalFuelCost = (float) $fuelQuery->sum('total_amount');

        $reminders = $remindersQuery->orderBy('due_at')->get();
        $overdueCount = $reminders->where('status', MaintenanceReminder::STATUS_OVERDUE)->count();
        $dueCount = $reminders->where('status', MaintenanceReminder::STATUS_DUE)->count();
        $dueSoonCount = $reminders->where('status', MaintenanceReminder::STATUS_DUE_SOON)->count();
        $pendingCount = $reminders->where('status', MaintenanceReminder::STATUS_PENDING)->count();

        $recentMaintenance = $vehicle->maintenanceRecords()
            ->with(['vendor:id,name'])
            ->latest('performed_at')
            ->limit(5)
            ->get();

        $recentFuel = $vehicle->fuelRecords()
            ->latest('filled_at')
            ->limit(5)
            ->get();

        return [
            'vehicle' => [
                'uuid' => $vehicle->uuid,
                'display_name' => $vehicle->display_name,
                'plate_number' => $vehicle->plate_number,
                'odometer_km' => $vehicle->odometer_km,
                'odometer_source' => $vehicle->odometer_source,
                'odometer_updated_at' => $vehicle->odometer_updated_at?->format(DATE_ATOM),
            ],
            'metrics' => [
                'total_maintenance_cost' => $totalMaintenanceCost,
                'total_services_count' => $totalServicesCount,
                'total_fuel_litres' => $totalFuelLitres,
                'total_fuel_cost' => $totalFuelCost,
                'average_cost_per_litre' => $totalFuelLitres > 0 ? round($totalFuelCost / $totalFuelLitres, 2) : 0,
            ],
            'reminders_summary' => [
                'total_outstanding' => $reminders->count(),
                'overdue_count' => $overdueCount,
                'due_count' => $dueCount,
                'due_soon_count' => $dueSoonCount,
                'pending_count' => $pendingCount,
            ],
            'active_reminders' => $reminders->take(5)->values()->map(fn (MaintenanceReminder $r) => [
                'uuid' => $r->uuid,
                'category' => $r->category,
                'title' => $r->title,
                'due_at' => $r->due_at?->format('Y-m-d'),
                'due_odometer_km' => $r->due_odometer_km,
                'status' => $r->status,
            ]),
            'recent_maintenance' => $recentMaintenance->map(fn (MaintenanceRecord $m) => [
                'uuid' => $m->uuid,
                'category' => $m->category,
                'title' => $m->title,
                'performed_at' => $m->performed_at?->format('Y-m-d'),
                'odometer_km' => $m->odometer_km,
                'cost' => (float) $m->cost,
                'currency' => $m->currency,
                'workshop_name' => $m->workshop_name ?: $m->vendor?->name,
            ]),
            'recent_fuel' => $recentFuel->map(fn (FuelRecord $f) => [
                'uuid' => $f->uuid,
                'filled_at' => $f->filled_at?->format('Y-m-d'),
                'litres' => (float) $f->litres,
                'total_amount' => (float) $f->total_amount,
                'price_per_litre' => (float) $f->price_per_litre,
                'station' => $f->station,
                'odometer_km' => $f->odometer_km,
            ]),
        ];
    }

    /**
     * Update vehicle odometer reading and trigger reminder evaluation.
     */
    public function updateOdometer(Vehicle $vehicle, int $odometerKm, string $source = 'manual', ?User $user = null): Vehicle
    {
        $vehicle->update([
            'odometer_km' => $odometerKm,
            'odometer_source' => $source,
            'odometer_updated_at' => now(),
        ]);

        $this->evaluateRemindersForVehicle($vehicle);

        return $vehicle->fresh();
    }

    /**
     * Automatically generate or sync a maintenance reminder from a service record.
     */
    public function createReminderFromMaintenance(MaintenanceRecord $record, User $user): ?MaintenanceReminder
    {
        if (! $record->next_due_at && ! $record->next_due_odometer_km) {
            return null;
        }

        $reminder = MaintenanceReminder::create([
            'vehicle_id' => $record->vehicle_id,
            'user_id' => $user->id,
            'maintenance_record_id' => $record->id,
            'category' => $record->category,
            'title' => "Next {$record->title}",
            'due_at' => $record->next_due_at,
            'due_odometer_km' => $record->next_due_odometer_km,
            'status' => MaintenanceReminder::STATUS_PENDING,
        ]);

        $this->evaluateReminder($reminder, $record->vehicle);

        return $reminder->fresh();
    }

    /**
     * Evaluate reminder status against vehicle odometer and current calendar date.
     */
    public function evaluateReminder(MaintenanceReminder $reminder, ?Vehicle $vehicle = null): MaintenanceReminder
    {
        if (in_array($reminder->status, [MaintenanceReminder::STATUS_COMPLETED, MaintenanceReminder::STATUS_DISMISSED], true)) {
            return $reminder;
        }

        $vehicle = $vehicle ?: $reminder->vehicle;
        $currentKm = $vehicle?->odometer_km;
        $today = CarbonImmutable::now()->startOfDay();

        $dueSoonDays = (int) config('autosecure.care.due_soon_days', 30);
        $dueSoonKm = (int) config('autosecure.care.due_soon_km', 500);

        $newStatus = MaintenanceReminder::STATUS_PENDING;

        $isDateOverdue = $reminder->due_at && $reminder->due_at->startOfDay()->isBefore($today);
        $isDateDueToday = $reminder->due_at && $reminder->due_at->startOfDay()->equalTo($today);
        $isDateDueSoon = $reminder->due_at && $reminder->due_at->startOfDay()->isBefore($today->addDays($dueSoonDays));

        $isKmOverdue = $reminder->due_odometer_km && $currentKm !== null && $currentKm >= $reminder->due_odometer_km;
        $isKmDueSoon = $reminder->due_odometer_km && $currentKm !== null && $currentKm >= ($reminder->due_odometer_km - $dueSoonKm);

        if ($isDateOverdue || $isKmOverdue) {
            $newStatus = $isDateOverdue ? MaintenanceReminder::STATUS_OVERDUE : MaintenanceReminder::STATUS_DUE;
        } elseif ($isDateDueToday) {
            $newStatus = MaintenanceReminder::STATUS_DUE;
        } elseif ($isDateDueSoon || $isKmDueSoon) {
            $newStatus = MaintenanceReminder::STATUS_DUE_SOON;
        }

        if ($reminder->status !== $newStatus) {
            $reminder->update(['status' => $newStatus]);
        }

        return $reminder;
    }

    /**
     * Evaluate all outstanding reminders for a given vehicle.
     *
     * @return Collection<int, MaintenanceReminder>
     */
    public function evaluateRemindersForVehicle(Vehicle $vehicle): Collection
    {
        return $vehicle->reminders()
            ->outstanding()
            ->get()
            ->map(fn (MaintenanceReminder $reminder) => $this->evaluateReminder($reminder, $vehicle));
    }

    /**
     * Generate chronological vehicle care timeline of maintenance, fuel fill-ups, and reminders.
     *
     * @return array<int, array<string, mixed>>
     */
    public function timeline(Vehicle $vehicle, int $limit = 25): array
    {
        $events = collect();

        // 1. Maintenance Records
        $maintenance = $vehicle->maintenanceRecords()
            ->with('vendor:id,name')
            ->latest('performed_at')
            ->limit($limit)
            ->get();

        foreach ($maintenance as $m) {
            $events->push([
                'id' => "maint-{$m->uuid}",
                'type' => 'maintenance',
                'category' => $m->category,
                'title' => $m->title,
                'description' => $m->description ?: "Service logged: {$m->title}",
                'cost' => (float) $m->cost,
                'currency' => $m->currency,
                'odometer_km' => $m->odometer_km,
                'workshop' => $m->workshop_name ?: $m->vendor?->name,
                'timestamp' => $m->performed_at?->format(DATE_ATOM) ?? $m->created_at?->format(DATE_ATOM),
                'date' => $m->performed_at?->format('Y-m-d'),
            ]);
        }

        // 2. Fuel Records
        $fuel = $vehicle->fuelRecords()
            ->latest('filled_at')
            ->limit($limit)
            ->get();

        foreach ($fuel as $f) {
            $events->push([
                'id' => "fuel-{$f->uuid}",
                'type' => 'fuel',
                'category' => 'fuel',
                'title' => 'Fuel Fill-up',
                'description' => "{$f->litres}L at {$f->station} (".number_format((float) $f->total_amount, 2).' NGN)',
                'cost' => (float) $f->total_amount,
                'currency' => 'NGN',
                'litres' => (float) $f->litres,
                'odometer_km' => $f->odometer_km,
                'workshop' => $f->station,
                'timestamp' => $f->filled_at?->format(DATE_ATOM) ?? $f->created_at?->format(DATE_ATOM),
                'date' => $f->filled_at?->format('Y-m-d'),
            ]);
        }

        // 3. Reminders
        $reminders = $vehicle->reminders()
            ->latest('updated_at')
            ->limit($limit)
            ->get();

        foreach ($reminders as $r) {
            $events->push([
                'id' => "reminder-{$r->uuid}",
                'type' => 'reminder',
                'category' => $r->category,
                'title' => $r->title,
                'description' => "Reminder status: {$r->status}".($r->due_odometer_km ? " (Due at {$r->due_odometer_km} km)" : ''),
                'cost' => 0.0,
                'currency' => 'NGN',
                'status' => $r->status,
                'timestamp' => $r->due_at?->format(DATE_ATOM) ?? $r->created_at?->format(DATE_ATOM),
                'date' => $r->due_at?->format('Y-m-d'),
            ]);
        }

        return $events
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values()
            ->all();
    }
}
