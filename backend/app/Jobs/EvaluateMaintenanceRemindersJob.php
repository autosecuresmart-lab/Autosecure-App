<?php

namespace App\Jobs;

use App\Models\MaintenanceReminder;
use App\Models\Notification;
use App\Models\Vehicle;
use App\Services\Care\VehicleCareService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EvaluateMaintenanceRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(VehicleCareService $careService): void
    {
        $vehicles = Vehicle::whereHas('reminders', fn ($q) => $q->outstanding())->get();

        foreach ($vehicles as $vehicle) {
            $reminders = $careService->evaluateRemindersForVehicle($vehicle);

            foreach ($reminders as $reminder) {
                if (
                    in_array($reminder->status, [
                        MaintenanceReminder::STATUS_DUE_SOON,
                        MaintenanceReminder::STATUS_DUE,
                        MaintenanceReminder::STATUS_OVERDUE,
                    ], true) &&
                    (! $reminder->notified_at || $reminder->notified_at->isBefore(now()->subHours(24)))
                ) {
                    Notification::create([
                        'user_id' => $reminder->user_id,
                        'subject_type' => 'vehicle',
                        'subject_id' => $vehicle->id,
                        'type' => 'maintenance.reminder',
                        'category' => 'care',
                        'title' => "Maintenance Alert: {$reminder->title}",
                        'body' => "Service for {$vehicle->display_name} is {$reminder->status}".($reminder->due_odometer_km ? " (Target: {$reminder->due_odometer_km} km)" : ''),
                        'channel' => Notification::CHANNEL_IN_APP,
                        'status' => Notification::STATUS_DELIVERED,
                        'sent_at' => now(),
                        'delivered_at' => now(),
                        'data' => [
                            'reminder_uuid' => $reminder->uuid,
                            'vehicle_uuid' => $vehicle->uuid,
                            'status' => $reminder->status,
                            'category' => $reminder->category,
                        ],
                    ]);

                    $reminder->update(['notified_at' => now()]);
                }
            }
        }
    }
}
