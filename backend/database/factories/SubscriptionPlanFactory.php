<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionPlan>
 */
class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    public function definition(): array
    {
        return [
            'name' => 'Monthly',
            'slug' => 'monthly-'.fake()->unique()->numerify('####'),
            'description' => null,
            'price' => 4200,
            'base_price' => 4200,
            'currency' => 'NGN',
            'interval' => 'monthly',
            'duration_days' => 30,
            'discount_percent' => 0,
            'is_free' => false,
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 1,
        ];
    }

    public function free(): static
    {
        return $this->state(fn () => [
            'name' => 'Free',
            'slug' => 'free-'.fake()->unique()->numerify('####'),
            'price' => 0,
            'interval' => 'custom',
            'duration_days' => 36500,
            'is_free' => true,
            'is_default' => true,
            'sort_order' => 0,
        ]);
    }
}
