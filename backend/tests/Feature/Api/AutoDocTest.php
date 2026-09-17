<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AutoDocTest extends TestCase
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
            'plate_number' => 'LAG-892-KJ',
        ]);
    }

    public function test_user_can_create_autodoc_launch_session(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/vehicles/{$this->vehicle->uuid}/autodoc/launch");

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'session' => [
                    'launch_token',
                    'deep_link',
                    'web_url',
                    'vehicle_ref',
                    'expires_at',
                    'expires_in_seconds',
                    'app_store_urls' => ['ios', 'android'],
                ],
            ]);

        $deepLink = $response->json('session.deep_link');
        $this->assertStringContainsString('autodoc://vehicle/', $deepLink);
        $this->assertStringContainsString('token=', $deepLink);
    }

    public function test_user_can_fetch_vehicle_document_summary(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/vehicles/{$this->vehicle->uuid}/autodoc/documents");

        $response->assertOk()
            ->assertJsonStructure([
                'vehicle' => ['uuid', 'display_name', 'plate_number', 'autodoc_vehicle_ref'],
                'summary' => ['total_documents', 'valid_count', 'expiring_soon_count', 'expired_count', 'overall_status'],
                'documents' => [
                    '*' => ['id', 'type', 'title', 'issuer', 'document_number', 'status'],
                ],
            ]);

        $this->assertGreaterThanOrEqual(4, $response->json('summary.total_documents'));
    }

    public function test_user_can_associate_vehicle_with_autodoc_reference(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/vehicles/{$this->vehicle->uuid}/autodoc/associate", [
            'autodoc_vehicle_ref' => 'AD-VEH-99281',
        ]);

        $response->assertOk()
            ->assertJsonPath('vehicle.autodoc_vehicle_ref', 'AD-VEH-99281');

        $this->vehicle->refresh();
        $this->assertEquals('AD-VEH-99281', $this->vehicle->autodoc_vehicle_ref);
    }

    public function test_user_can_fetch_ecu_diagnostics(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/vehicles/{$this->vehicle->uuid}/autodoc/diagnostics");

        $response->assertOk()
            ->assertJsonStructure([
                'vehicle_uuid',
                'health_score',
                'protocol',
                'ecu_status',
                'telemetry' => ['battery_voltage', 'coolant_temp', 'oil_life_percent', 'fuel_trim_st'],
                'fault_codes_count',
                'fault_codes',
            ]);

        $this->assertEquals(94, $response->json('health_score'));
    }

    public function test_user_can_clear_diagnostic_trouble_codes(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/vehicles/{$this->vehicle->uuid}/autodoc/clear-codes");

        $response->assertOk()
            ->assertJsonPath('fault_codes_count', 0)
            ->assertJsonPath('health_score', 100);
    }

    public function test_unauthorized_user_cannot_access_autodoc_endpoints(): void
    {
        $stranger = User::factory()->create();
        Sanctum::actingAs($stranger);

        $response = $this->getJson("/api/v1/vehicles/{$this->vehicle->uuid}/autodoc/documents");
        $response->assertForbidden();

        $launchResponse = $this->postJson("/api/v1/vehicles/{$this->vehicle->uuid}/autodoc/launch");
        $launchResponse->assertForbidden();
    }
}
