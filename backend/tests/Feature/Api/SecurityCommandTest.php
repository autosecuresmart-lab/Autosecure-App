<?php

namespace Tests\Feature\Api;

use App\Models\AuditLog;
use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_remote_shutdown_requires_explicit_confirmation(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user, 'user')->create();
        Device::factory()->create([
            'vehicle_id' => $vehicle->id,
            'type' => Device::TYPE_TRACKER,
            'status' => Device::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/vehicles/{$vehicle->uuid}/shutdown", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('confirmation');
    }

    public function test_remote_shutdown_issues_command_and_records_audit_log(): void
    {
        $user = User::factory()->create(['password' => 'Password!2345']);
        $vehicle = Vehicle::factory()->for($user, 'user')->create();
        Device::factory()->create([
            'vehicle_id' => $vehicle->id,
            'type' => Device::TYPE_TRACKER,
            'status' => Device::STATUS_ACTIVE,
        ]);

        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response([
                'status' => 0,
                'msg_id' => 'cmd_12345',
                'message' => 'success',
            ], 200),
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/vehicles/{$vehicle->uuid}/shutdown", [
                'confirmation' => true,
                'password' => 'Password!2345',
                'reason' => 'Suspected unauthorized movement',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'command' => ['uuid', 'type', 'status', 'is_confirmed'],
            ]);

        $this->assertDatabaseHas('device_commands', [
            'vehicle_id' => $vehicle->id,
            'type' => DeviceCommand::TYPE_REMOTE_SHUTDOWN,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => 'vehicle',
            'auditable_id' => $vehicle->id,
            'action' => 'security.remote_shutdown_requested',
            'severity' => AuditLog::SEVERITY_CRITICAL,
        ]);
    }

    public function test_restore_engine_issues_restore_command(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user, 'user')->create();
        Device::factory()->create([
            'vehicle_id' => $vehicle->id,
            'type' => Device::TYPE_TRACKER,
            'status' => Device::STATUS_ACTIVE,
        ]);

        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response([
                'status' => 0,
                'msg_id' => 'restore_12345',
                'message' => 'success',
            ], 200),
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/vehicles/{$vehicle->uuid}/restore-engine", [
                'confirmation' => true,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'command' => ['uuid', 'status'],
            ]);
    }

    public function test_an_unauthorized_user_cannot_issue_shutdown_to_another_vehicle(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $vehicle = Vehicle::factory()->for($owner, 'user')->create();
        Device::factory()->create([
            'vehicle_id' => $vehicle->id,
            'type' => Device::TYPE_TRACKER,
        ]);

        $response = $this->actingAs($attacker)
            ->postJson("/api/v1/vehicles/{$vehicle->uuid}/shutdown", [
                'confirmation' => true,
            ]);

        $response->assertForbidden();
    }
}
