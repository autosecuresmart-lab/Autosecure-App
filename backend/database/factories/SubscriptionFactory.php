<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subscription_plan_id' => SubscriptionPlan::factory(),
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->addDays(25),
            'auto_renew' => false,
            'source' => 'purchase',
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => Subscription::STATUS_EXPIRED,
            'starts_at' => now()->subDays(60),
            'ends_at' => now()->subDays(30),
        ]);
    }

    public function inGrace(): static
    {
        return $this->state(fn () => [
            'status' => Subscription::STATUS_GRACE,
            'starts_at' => now()->subDays(35),
            'ends_at' => now()->subDays(5),
            'grace_ends_at' => now()->addDays(2),
        ]);
    }
}
