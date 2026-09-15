<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeviceCommand>
 */
class DeviceCommandFactory extends Factory
{
    protected $model = DeviceCommand::class;

    public function definition(): array
    {
        return [
            'device_id' => Device::factory(),
            'vehicle_id' => null,
            'user_id' => User::factory(),
            'type' => DeviceCommand::TYPE_LOCATE,
            'status' => DeviceCommand::STATUS_PENDING,
            'payload' => null,
            'requested_at' => now(),
            'confirmed_by_user' => false,
        ];
    }

    public function remoteShutdown(): static
    {
        return $this->state(fn () => [
            'type' => DeviceCommand::TYPE_REMOTE_SHUTDOWN,
            'confirmed_by_user' => true,
        ]);
    }

    public function acknowledged(): static
    {
        return $this->state(fn () => [
            'status' => DeviceCommand::STATUS_ACKNOWLEDGED,
            'sent_at' => now()->subSeconds(5),
            'acknowledged_at' => now(),
            'response' => ['result' => 'ok'],
        ]);
    }
}
