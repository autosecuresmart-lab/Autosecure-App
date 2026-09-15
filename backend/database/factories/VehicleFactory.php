<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nickname' => null,
            'plate_number' => strtoupper(fake()->bothify('??-###-???')),
            'make' => fake()->randomElement(['Toyota', 'Honda', 'Lexus', 'Kia', 'Hyundai']),
            'model' => fake()->word(),
            'year' => fake()->numberBetween(2005, (int) date('Y')),
            'colour' => fake()->safeColorName(),
            'vin' => strtoupper(fake()->bothify('?????????????????')),
            'fuel_type' => 'petrol',
            'transmission' => 'automatic',
            'odometer_km' => fake()->numberBetween(1000, 250000),
            'odometer_source' => 'manual',
            'is_primary' => false,
            'status' => 'active',
        ];
    }
}
