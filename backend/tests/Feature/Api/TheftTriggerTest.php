<?php

namespace Tests\Feature\Api;

use App\Models\AuditLog;
use App\Models\Device;
use App\Models\TheftEvent;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TheftTriggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_activate_theft_trigger(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user, 'user')->create();
        Device::factory()->create([
            'vehicle_id' => $vehicle->id,
            'type' => Device::TYPE_TRACKER,
            'status' => Device::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/vehicles/{$vehicle->uuid}/theft-trigger", [
                'biometric_verified' => true,
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'theft_event' => [
                    'uuid',
                    'status',
                    'severity',
                    'triggered_at',
                    'last_known_location',
                    'available_actions',
                ],
            ]);

        $this->assertDatabaseHas('theft_events', [
            'vehicle_id' => $vehicle->id,
            'user_id' => $user->id,
            'status' => TheftEvent::STATUS_OPEN,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'security.theft_alert',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => 'vehicle',
            'auditable_id' => $vehicle->id,
            'action' => 'security.theft_triggered',
            'severity' => AuditLog::SEVERITY_CRITICAL,
        ]);
    }

    public function test_an_authenticated_user_can_view_theft_events_for_vehicle(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user, 'user')->create();

        TheftEvent::create([
            'vehicle_id' => $vehicle->id,
            'user_id' => $user->id,
            'status' => TheftEvent::STATUS_OPEN,
            'severity' => 'critical',
            'triggered_at' => now(),
            'last_known_latitude' => 6.4382,
            'last_known_longitude' => 3.4721,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/vehicles/{$vehicle->uuid}/theft-events");

        $response->assertOk()
            ->assertJsonStructure([
                'vehicle_uuid',
                'theft_events' => [
                    '*' => ['uuid', 'status', 'severity', 'triggered_at', 'is_open'],
                ],
            ]);
    }

    public function test_an_authenticated_user_can_resolve_a_theft_event(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user, 'user')->create();

        $theftEvent = TheftEvent::create([
            'vehicle_id' => $vehicle->id,
            'user_id' => $user->id,
            'status' => TheftEvent::STATUS_OPEN,
            'severity' => 'critical',
            'triggered_at' => now(),
            'last_known_latitude' => 6.4382,
            'last_known_longitude' => 3.4721,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/theft-events/{$theftEvent->uuid}/resolve", [
                'resolution_note' => 'Vehicle recovered safely by police team at Lekki toll gate.',
                'status' => 'resolved',
            ]);

        $response->assertOk()
            ->assertJsonPath('theft_event.status', 'resolved');

        $this->assertDatabaseHas('theft_events', [
            'id' => $theftEvent->id,
            'status' => 'resolved',
            'resolution_note' => 'Vehicle recovered safely by police team at Lekki toll gate.',
        ]);
    }
}
