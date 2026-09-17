<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DashcamTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_pair_a_dashcam_device(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user, 'user')->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/dashcams/pair', [
                'serial_number' => 'CAM-88992211',
                'model' => '4G Dual-Cam Dashcam 1080p',
                'vehicle_uuid' => $vehicle->uuid,
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'device' => ['uuid', 'type', 'serial_number', 'capabilities'],
            ]);

        $this->assertDatabaseHas('devices', [
            'serial_number' => 'CAM-88992211',
            'type' => Device::TYPE_DASHCAM,
            'vehicle_id' => $vehicle->id,
        ]);
    }

    public function test_an_authenticated_user_can_request_a_live_video_stream(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user, 'user')->create();
        $device = Device::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'type' => Device::TYPE_DASHCAM,
            'serial_number' => 'CAM-88992211',
            'status' => Device::STATUS_ACTIVE,
        ]);

        Http::fake([
            '*' => Http::response([
                'status' => 0,
                'stream_url' => 'rtsp://stream.autosecure.ng/live/CAM-88992211/ch1',
                'channel' => 1,
            ], 200),
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/dashcams/{$device->uuid}/stream", [
                'camera' => 'front',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'stream' => ['stream_url', 'protocol', 'camera', 'expires_at'],
                'device' => ['uuid', 'is_online'],
            ]);
    }

    public function test_an_authenticated_user_can_query_recordings_and_events(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user, 'user')->create();
        $device = Device::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'type' => Device::TYPE_DASHCAM,
            'serial_number' => 'CAM-88992211',
            'status' => Device::STATUS_ACTIVE,
        ]);

        Http::fake([
            '*' => Http::response([
                'status' => 0,
                'files' => [
                    [
                        'file_id' => 'rec_001',
                        'download_url' => 'https://storage.autosecure.ng/videos/rec_001.mp4',
                        'start_time' => now()->subMinutes(30)->format('Y-m-d H:i:s'),
                        'duration' => 120,
                        'file_size' => 10485760,
                        'is_alarm' => 1,
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/dashcams/{$device->uuid}/recordings?date=".now()->format('Y-m-d'));

        $response->assertOk()
            ->assertJsonStructure([
                'date',
                'camera',
                'count',
                'recordings',
            ]);
    }

    public function test_an_authenticated_user_can_capture_a_snapshot(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user, 'user')->create();
        $device = Device::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'type' => Device::TYPE_DASHCAM,
            'serial_number' => 'CAM-88992211',
            'status' => Device::STATUS_ACTIVE,
        ]);

        Http::fake([
            '*' => Http::response([
                'status' => 0,
                'photo_id' => 'snap_123',
                'photo_url' => 'https://storage.autosecure.ng/snapshots/snap_123.jpg',
                'file_size' => 524288,
            ], 200),
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/dashcams/{$device->uuid}/snapshot", [
                'camera' => 'front',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'snapshot' => ['id', 'photo_url', 'camera', 'captured_at'],
            ]);
    }

    public function test_an_unauthorized_user_cannot_access_dashcam_stream(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $vehicle = Vehicle::factory()->for($owner, 'user')->create();
        $device = Device::factory()->create([
            'user_id' => $owner->id,
            'vehicle_id' => $vehicle->id,
            'type' => Device::TYPE_DASHCAM,
        ]);

        $response = $this->actingAs($stranger)
            ->postJson("/api/v1/dashcams/{$device->uuid}/stream", [
                'camera' => 'front',
            ]);

        $response->assertForbidden();
    }
}
