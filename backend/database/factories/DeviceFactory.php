<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        return [
            'type' => Device::TYPE_TRACKER,
            // Devices arrive unbound: provisioning reserves a device for a
            // customer (see `reservedFor`) and the customer links it to a
            // vehicle afterwards.
            'vehicle_id' => null,
            'user_id' => null,
            'provider' => null,
            'external_id' => null,
            'serial_number' => strtoupper(Str::random(12)),
            'label' => null,
            'imei' => fake()->unique()->numerify('###############'),
            'status' => Device::STATUS_PENDING,
            'is_online' => false,
        ];
    }

    /**
     * The device is already installed on a vehicle.
     */
    public function boundTo(Vehicle $vehicle): static
    {
        return $this->state(fn () => [
            'vehicle_id' => $vehicle->getKey(),
            'user_id' => $vehicle->user_id,
            'status' => Device::STATUS_ACTIVE,
            'bound_at' => now(),
            'is_online' => true,
        ]);
    }

    /**
     * AUTOSECURE has reserved the device for this customer, but it is not yet
     * linked to a vehicle.
     */
    public function reservedFor(User $user): static
    {
        return $this->state(fn () => [
            'user_id' => $user->getKey(),
            'status' => Device::STATUS_PENDING,
        ]);
    }

    public function dashcam(): static
    {
        return $this->state(fn () => ['type' => Device::TYPE_DASHCAM]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => Device::STATUS_PENDING, 'is_online' => false]);
    }
}
