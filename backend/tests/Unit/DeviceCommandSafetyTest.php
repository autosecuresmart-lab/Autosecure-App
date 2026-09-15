<?php

namespace Tests\Unit;

use App\Models\DeviceCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The single most safety-critical rule in the product:
 *
 *   "The action should not be presented as successful until the platform
 *    receives a confirmed response."
 *
 * Remote shutdown must never be reported as done on the strength of a request
 * having been sent.
 */
class DeviceCommandSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_newly_queued_command_is_never_confirmed(): void
    {
        $command = DeviceCommand::factory()->remoteShutdown()->create();

        $this->assertFalse($command->isConfirmed());
        $this->assertTrue($command->isAwaitingDevice());
    }

    public function test_a_sent_but_unacknowledged_command_is_not_confirmed(): void
    {
        $command = DeviceCommand::factory()->remoteShutdown()->create([
            'status' => DeviceCommand::STATUS_SENT,
            'sent_at' => now(),
        ]);

        $this->assertFalse($command->isConfirmed());
        $this->assertTrue($command->isAwaitingDevice());
    }

    public function test_a_timed_out_command_is_not_confirmed(): void
    {
        $command = DeviceCommand::factory()->remoteShutdown()->create([
            'status' => DeviceCommand::STATUS_TIMEOUT,
            'sent_at' => now()->subMinutes(2),
        ]);

        $this->assertFalse($command->isConfirmed());
        $this->assertFalse($command->isAwaitingDevice());
    }

    public function test_a_failed_command_is_not_confirmed(): void
    {
        $command = DeviceCommand::factory()->remoteShutdown()->create([
            'status' => DeviceCommand::STATUS_FAILED,
            'failure_reason' => 'device refused',
        ]);

        $this->assertFalse($command->isConfirmed());
    }

    public function test_an_acknowledged_command_requires_a_device_response_timestamp(): void
    {
        $command = DeviceCommand::factory()->remoteShutdown()->create([
            'status' => DeviceCommand::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => null,
        ]);

        $this->assertFalse(
            $command->isConfirmed(),
            'A command marked acknowledged without a device response timestamp must not count as confirmed.',
        );
    }

    public function test_a_genuinely_acknowledged_command_is_confirmed(): void
    {
        $command = DeviceCommand::factory()->remoteShutdown()->acknowledged()->create();

        $this->assertTrue($command->isConfirmed());
    }

    public function test_remote_shutdown_is_treated_as_a_destructive_command(): void
    {
        $this->assertTrue(
            DeviceCommand::factory()->remoteShutdown()->make()->isDestructive(),
        );

        $this->assertFalse(
            DeviceCommand::factory()->make(['type' => DeviceCommand::TYPE_LOCATE])->isDestructive(),
        );
    }
}
