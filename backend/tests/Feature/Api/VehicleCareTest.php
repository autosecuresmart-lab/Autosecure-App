<?php

namespace Tests\Feature\Api;

use App\Models\FuelRecord;
use App\Models\MaintenanceRecord;
use App\Models\MaintenanceReminder;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VehicleCareTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->vehicle = Vehicle::factory()->create([
            'user_id' => $this->user->id,
            'odometer_km' => 45000,
            'odometer_source' => 'tracker',
        ]);
    }

    public function test_user_can_view_vehicle_care_dashboard(): void
    {
        Sanctum::actingAs($this->user);

        MaintenanceRecord::create([
            'vehicle_id' => $this->vehicle->id,
            'user_id' => $this->user->id,
            'category' => MaintenanceRecord::CATEGORY_OIL_CHANGE,
            'title' => 'Engine Oil & Filter Service',
            'performed_at' => now()->subDays(10),
            'odometer_km' => 44500,
            'cost' => 35000,
        ]);

        FuelRecord::create([
            'vehicle_id' => $this->vehicle->id,
            'user_id' => $this->user->id,
            'filled_at' => now()->subDays(2),
            'litres' => 50.0,
            'price_per_litre' => 850.0,
            'total_amount' => 42500.0,
            'station' => 'TotalEnergies Victoria Island',
        ]);

        $response = $this->getJson("/api/v1/vehicles/{$this->vehicle->uuid}/care/dashboard");

        $response->assertOk()
            ->assertJsonStructure([
                'vehicle' => ['uuid', 'display_name', 'odometer_km', 'odometer_source'],
                'metrics' => ['total_maintenance_cost', 'total_services_count', 'total_fuel_litres', 'total_fuel_cost'],
                'reminders_summary' => ['total_outstanding', 'overdue_count', 'due_count', 'due_soon_count'],
                'active_reminders',
                'recent_maintenance',
                'recent_fuel',
            ]);

        $this->assertEquals(35000, $response->json('metrics.total_maintenance_cost'));
        $this->assertEquals(50.0, $response->json('metrics.total_fuel_litres'));
    }

    public function test_user_can_update_vehicle_odometer(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->patchJson("/api/v1/vehicles/{$this->vehicle->uuid}/odometer", [
            'odometer_km' => 48200,
            'source' => 'manual',
        ]);

        $response->assertOk()
            ->assertJsonPath('vehicle.odometer_km', 48200)
            ->assertJsonPath('vehicle.odometer_source', 'manual');

        $this->vehicle->refresh();
        $this->assertEquals(48200, $this->vehicle->odometer_km);
    }

    public function test_user_can_create_maintenance_record_and_auto_generate_reminder(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/vehicles/{$this->vehicle->uuid}/maintenance-records", [
            'category' => MaintenanceRecord::CATEGORY_BRAKE_SERVICE,
            'title' => 'Front Ceramic Brake Pads Replacement',
            'performed_at' => now()->format('Y-m-d'),
            'odometer_km' => 45500,
            'workshop_name' => 'AutoSecure Premium Service Centre',
            'cost' => 65000,
            'next_due_at' => now()->addMonths(6)->format('Y-m-d'),
            'next_due_odometer_km' => 60000,
        ]);

        $response->assertCreated()
            ->assertJsonPath('record.title', 'Front Ceramic Brake Pads Replacement')
            ->assertJsonPath('record.category', 'brake_service');

        $this->assertDatabaseHas('maintenance_records', [
            'vehicle_id' => $this->vehicle->id,
            'category' => 'brake_service',
            'cost' => 65000,
        ]);

        $this->assertDatabaseHas('maintenance_reminders', [
            'vehicle_id' => $this->vehicle->id,
            'category' => 'brake_service',
            'due_odometer_km' => 60000,
        ]);
    }

    public function test_user_can_manage_maintenance_reminders(): void
    {
        Sanctum::actingAs($this->user);

        $reminder = MaintenanceReminder::create([
            'vehicle_id' => $this->vehicle->id,
            'user_id' => $this->user->id,
            'category' => MaintenanceRecord::CATEGORY_OIL_CHANGE,
            'title' => 'Synthetic Oil Change',
            'due_at' => now()->addDays(5),
            'status' => MaintenanceReminder::STATUS_DUE_SOON,
        ]);

        // Complete
        $response = $this->patchJson("/api/v1/vehicles/{$this->vehicle->uuid}/reminders/{$reminder->uuid}/complete");
        $response->assertOk()
            ->assertJsonPath('reminder.status', MaintenanceReminder::STATUS_COMPLETED);

        // Dismiss
        $reminder2 = MaintenanceReminder::create([
            'vehicle_id' => $this->vehicle->id,
            'user_id' => $this->user->id,
            'category' => MaintenanceRecord::CATEGORY_TYRE_REPLACEMENT,
            'title' => 'Tyre Rotation',
            'due_at' => now()->addDays(15),
            'status' => MaintenanceReminder::STATUS_DUE_SOON,
        ]);

        $dismissResponse = $this->patchJson("/api/v1/vehicles/{$this->vehicle->uuid}/reminders/{$reminder2->uuid}/dismiss");
        $dismissResponse->assertOk()
            ->assertJsonPath('reminder.status', MaintenanceReminder::STATUS_DISMISSED);
    }

    public function test_user_can_create_and_list_fuel_records(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/vehicles/{$this->vehicle->uuid}/fuel-records", [
            'filled_at' => now()->format('Y-m-d'),
            'litres' => 45.5,
            'price_per_litre' => 870,
            'odometer_km' => 46200,
            'station' => 'Mobil Station Ikoyi',
        ]);

        $response->assertCreated()
            ->assertJsonPath('record.litres', '45.50')
            ->assertJsonPath('record.station', 'Mobil Station Ikoyi');

        $listResponse = $this->getJson("/api/v1/vehicles/{$this->vehicle->uuid}/fuel-records");
        $listResponse->assertOk()
            ->assertJsonStructure(['summary' => ['total_litres', 'total_cost'], 'data']);
    }

    public function test_user_can_view_care_timeline(): void
    {
        Sanctum::actingAs($this->user);

        MaintenanceRecord::create([
            'vehicle_id' => $this->vehicle->id,
            'user_id' => $this->user->id,
            'category' => MaintenanceRecord::CATEGORY_BATTERY,
            'title' => 'AGM Battery Check',
            'performed_at' => now()->subDays(5),
            'cost' => 15000,
        ]);

        $response = $this->getJson("/api/v1/vehicles/{$this->vehicle->uuid}/care/timeline");
        $response->assertOk()
            ->assertJsonStructure(['vehicle_uuid', 'count', 'timeline']);

        $this->assertNotEmpty($response->json('timeline'));
    }

    public function test_unauthorized_user_cannot_access_other_users_care_data(): void
    {
        $stranger = User::factory()->create();
        Sanctum::actingAs($stranger);

        $response = $this->getJson("/api/v1/vehicles/{$this->vehicle->uuid}/care/dashboard");
        $response->assertForbidden();
    }

    public function test_check_maintenance_reminders_command_runs_successfully(): void
    {
        MaintenanceReminder::create([
            'vehicle_id' => $this->vehicle->id,
            'user_id' => $this->user->id,
            'category' => MaintenanceRecord::CATEGORY_OIL_CHANGE,
            'title' => 'Overdue Transmission Fluid Flush',
            'due_at' => now()->subDays(2),
            'status' => MaintenanceReminder::STATUS_PENDING,
        ]);

        $this->artisan('autosecure:check-reminders')
            ->assertSuccessful();

        $this->assertDatabaseHas('maintenance_reminders', [
            'vehicle_id' => $this->vehicle->id,
            'status' => MaintenanceReminder::STATUS_OVERDUE,
        ]);
    }
}
