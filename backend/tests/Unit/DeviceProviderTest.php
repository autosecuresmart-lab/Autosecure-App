<?php

namespace Tests\Unit;

use App\Contracts\Devices\DashcamProvider;
use App\Contracts\Devices\TrackerProvider;
use App\Exceptions\IntegrationPendingException;
use App\Models\Device;
use App\Models\DeviceCommand;
use App\Services\Devices\DeviceProviderManager;
use App\Services\Devices\Providers\NullDashcamProvider;
use App\Services\Devices\Providers\NullTrackerProvider;
use App\Support\Devices\CommandResult;
use App\Support\Devices\DeviceLocation;
use App\Support\Devices\Recording;
use App\Support\Devices\StreamSession;
use App\Support\PendingIntegrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * The provider seam.
 *
 * Phase 3/4 must be able to plug in a real tracker/dashcam provider without
 * touching controllers, migrations or models. These tests lock that in.
 */
class DeviceProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('autosecure.devices.tracker.driver', 'null');
        config()->set('autosecure.devices.dashcam.driver', 'null');

        app(DeviceProviderManager::class)->forgetResolved();
    }

    /* --------------------------------------------------------------------- */
    /* Driver resolution                                                      */
    /* --------------------------------------------------------------------- */

    public function test_the_configured_tracker_driver_resolves_to_the_null_provider(): void
    {
        $provider = app(TrackerProvider::class);

        $this->assertInstanceOf(NullTrackerProvider::class, $provider);
        $this->assertSame('null', $provider->driverName());
        $this->assertFalse($provider->isConfigured());
    }

    public function test_the_configured_dashcam_driver_resolves_to_the_null_provider(): void
    {
        $provider = app(DashcamProvider::class);

        $this->assertInstanceOf(NullDashcamProvider::class, $provider);
        $this->assertSame('null', $provider->driverName());
        $this->assertFalse($provider->isConfigured());
    }

    public function test_a_device_resolves_the_driver_for_its_own_type(): void
    {
        $manager = app(DeviceProviderManager::class);

        $this->assertInstanceOf(
            TrackerProvider::class,
            $manager->for(Device::factory()->make(['type' => Device::TYPE_TRACKER])),
        );

        $this->assertInstanceOf(
            DashcamProvider::class,
            $manager->for(Device::factory()->dashcam()->make()),
        );
    }

    public function test_a_registered_driver_can_be_resolved_instead_of_the_null_one(): void
    {
        $manager = app(DeviceProviderManager::class);

        $manager->extend('tracker', 'acme', fn () => new class implements TrackerProvider
        {
            public function driverName(): string
            {
                return 'acme';
            }

            public function isConfigured(): bool
            {
                return true;
            }

            public function status(Device $device): \App\Support\Devices\DeviceStatus
            {
                throw new \LogicException('not used');
            }

            public function latestLocation(Device $device): DeviceLocation
            {
                throw new \LogicException('not used');
            }

            public function locationHistory(Device $device, \DateTimeInterface $from, \DateTimeInterface $to): \Illuminate\Support\Collection
            {
                throw new \LogicException('not used');
            }

            public function sendCommand(Device $device, DeviceCommand $command): CommandResult
            {
                throw new \LogicException('not used');
            }
        });

        config()->set('autosecure.devices.tracker.driver', 'acme');
        $manager->forgetResolved();

        $this->assertSame('acme', $manager->tracker()->driverName());
        $this->assertTrue($manager->isConfigured('tracker'));
    }

    public function test_an_unknown_driver_fails_loudly_instead_of_silently(): void
    {
        config()->set('autosecure.devices.tracker.driver', 'does-not-exist');

        $manager = app(DeviceProviderManager::class);
        $manager->forgetResolved();

        $this->expectException(InvalidArgumentException::class);

        $manager->tracker();
    }

    /* --------------------------------------------------------------------- */
    /* Null drivers must never invent data                                    */
    /* --------------------------------------------------------------------- */

    public function test_the_null_tracker_refuses_every_operation_with_the_pending_payload(): void
    {
        $provider = new NullTrackerProvider;
        $device = Device::factory()->make();

        try {
            $provider->latestLocation($device);
            $this->fail('Expected an IntegrationPendingException.');
        } catch (IntegrationPendingException $e) {
            $this->assertSame('security', $e->module);
            $this->assertSame('latestLocation', $e->operation);
            $this->assertSame('integration_pending', $e->payload()['code']);
            $this->assertNotEmpty($e->payload()['blocked_by']);
        }
    }

    public function test_the_null_tracker_cannot_confirm_a_shutdown(): void
    {
        $provider = new NullTrackerProvider;
        $device = Device::factory()->make();
        $command = DeviceCommand::factory()->remoteShutdown()->make();

        $this->expectException(IntegrationPendingException::class);

        $provider->sendCommand($device, $command);
    }

    public function test_the_null_dashcam_refuses_to_produce_a_stream(): void
    {
        $provider = new NullDashcamProvider;
        $device = Device::factory()->dashcam()->make();

        try {
            $provider->createStreamSession($device);
            $this->fail('Expected an IntegrationPendingException.');
        } catch (IntegrationPendingException $e) {
            $this->assertSame('dashcam', $e->module);
            $this->assertSame('createStreamSession', $e->operation);
        }
    }

    /* --------------------------------------------------------------------- */
    /* CommandResult: the "never claim success" rule                          */
    /* --------------------------------------------------------------------- */

    public function test_an_accepted_but_unacknowledged_command_is_not_confirmed(): void
    {
        $result = CommandResult::accepted('provider-ref-1');

        $this->assertTrue($result->accepted);
        $this->assertFalse($result->acknowledged);
        $this->assertFalse($result->isConfirmed());
        $this->assertTrue($result->isPending());
        $this->assertSame(DeviceCommand::STATUS_SENT, $result->deviceCommandStatus());
    }

    public function test_a_command_without_an_acknowledgement_timestamp_is_not_confirmed(): void
    {
        // A provider claiming "acknowledged" with no device evidence must not count.
        $result = new CommandResult(accepted: true, acknowledged: true, acknowledgedAt: null);

        $this->assertFalse($result->isConfirmed());
    }

    public function test_a_device_acknowledged_command_is_confirmed(): void
    {
        $result = CommandResult::acknowledged('provider-ref-2', new \DateTimeImmutable);

        $this->assertTrue($result->isConfirmed());
        $this->assertFalse($result->isPending());
        $this->assertSame(DeviceCommand::STATUS_ACKNOWLEDGED, $result->deviceCommandStatus());
        $this->assertTrue($result->toArray()['confirmed']);
    }

    public function test_a_failed_command_is_never_confirmed(): void
    {
        $result = CommandResult::failed('device refused');

        $this->assertFalse($result->isConfirmed());
        $this->assertFalse($result->isPending());
        $this->assertSame(DeviceCommand::STATUS_FAILED, $result->deviceCommandStatus());
    }

    /* --------------------------------------------------------------------- */
    /* DTOs                                                                   */
    /* --------------------------------------------------------------------- */

    public function test_a_location_normalises_into_position_attributes(): void
    {
        $location = new DeviceLocation(
            latitude: 6.5244,
            longitude: 3.3792,
            recordedAt: new \DateTimeImmutable('2026-09-14T10:00:00+00:00'),
            speedKph: 42.5,
        );

        $attributes = $location->toPositionAttributes(deviceId: 7, vehicleId: 3);

        $this->assertSame(7, $attributes['device_id']);
        $this->assertSame(3, $attributes['vehicle_id']);
        $this->assertSame('gps', $attributes['source']);
        $this->assertEqualsWithDelta(6.5244, $attributes['latitude'], 0.0000001);
    }

    public function test_a_stream_session_is_time_boxed_and_reports_its_remaining_life(): void
    {
        $session = new StreamSession(
            url: 'https://stream.example/live.m3u8',
            expiresAt: new \DateTimeImmutable('+5 minutes'),
            token: 'short-lived',
        );

        $this->assertFalse($session->isExpired());
        $this->assertGreaterThan(200, $session->expiresInSeconds());
        $this->assertSame('short-lived', $session->toArray()['token']);
    }

    public function test_an_expired_stream_session_is_detected(): void
    {
        $session = new StreamSession(
            url: 'https://stream.example/live.m3u8',
            expiresAt: new \DateTimeImmutable('-1 second'),
        );

        $this->assertTrue($session->isExpired());
        $this->assertSame(0, $session->expiresInSeconds());
    }

    public function test_a_protected_recording_is_treated_as_an_emergency(): void
    {
        $recording = new Recording(
            externalId: 'clip-1',
            startedAt: new \DateTimeImmutable,
            kind: Recording::KIND_LOOP,
            isProtected: true,
        );

        $this->assertTrue($recording->isEmergency());
    }

    /* --------------------------------------------------------------------- */
    /* Registry                                                               */
    /* --------------------------------------------------------------------- */

    public function test_every_pending_integration_exposes_a_blocker_list(): void
    {
        foreach (PendingIntegrations::keys() as $module) {
            $this->assertNotEmpty(
                PendingIntegrations::blockedBy($module),
                "The [{$module}] integration must state what it is waiting for.",
            );
        }
    }

    public function test_the_api_and_the_drivers_share_one_blocker_list(): void
    {
        $this->assertSame(
            PendingIntegrations::blockedBy('security'),
            (new IntegrationPendingException('security', 'latestLocation'))->payload()['blocked_by'],
        );
    }
}
