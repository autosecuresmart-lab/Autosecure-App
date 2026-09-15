<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

/**
 * Customer subscription plans.
 *
 * Prices are the figures in the AUTOSECURE 2.0 proposal (Monthly ₦4,200,
 * Half-Year ₦23,940 after a recommended 5%, Yearly ₦45,360 after a recommended
 * 10%) and remain subject to management approval.
 *
 * The Free plan is stored as a real row so entitlement resolution has one code
 * path, and it carries every core security and dashcam feature.
 */
class SubscriptionPlanSeeder extends Seeder
{
    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $plans = [
        [
            'slug' => 'free',
            'name' => 'Free',
            'description' => 'Essential tracker and dashcam security, always available.',
            'price' => 0,
            'base_price' => null,
            'interval' => 'custom',
            'duration_days' => 36500,
            'discount_percent' => 0,
            'is_free' => true,
            'is_default' => true,
            'sort_order' => 0,
            'features' => [
                'security.live_location',
                'security.playback',
                'security.remote_shutdown',
                'security.call_vehicle',
                'security.theft_trigger',
                'dashcam.live',
                'dashcam.playback',
                'finder.browse',
                'finder.book',
                'finder.pay',
                'coins.earn',
            ],
        ],
        [
            'slug' => 'monthly',
            'name' => 'Monthly',
            'description' => 'Full vehicle care and ownership features, billed monthly.',
            'price' => 4200,
            'base_price' => 4200,
            'interval' => 'monthly',
            'duration_days' => 30,
            'discount_percent' => 0,
            'is_free' => false,
            'is_default' => false,
            'sort_order' => 1,
            'features' => [
                'care.oil_change',
                'care.brake_service',
                'care.tyre_replacement',
                'care.battery',
                'care.service_history',
                'care.mileage_insights',
                'care.fuel_usage',
                'autodoc.connect',
                'autodoc.renewal_summary',
            ],
        ],
        [
            'slug' => 'half-yearly',
            'name' => 'Half-Year',
            'description' => 'Six months of Premium with the recommended 5% discount.',
            'price' => 23940,
            'base_price' => 25200,
            'interval' => 'half_yearly',
            'duration_days' => 182,
            'discount_percent' => 5,
            'is_free' => false,
            'is_default' => false,
            'sort_order' => 2,
            'features' => [
                'care.oil_change',
                'care.brake_service',
                'care.tyre_replacement',
                'care.battery',
                'care.service_history',
                'care.mileage_insights',
                'care.fuel_usage',
                'autodoc.connect',
                'autodoc.renewal_summary',
            ],
        ],
        [
            'slug' => 'yearly',
            'name' => 'Yearly',
            'description' => 'Twelve months of Premium with the recommended 10% discount.',
            'price' => 45360,
            'base_price' => 50400,
            'interval' => 'yearly',
            'duration_days' => 365,
            'discount_percent' => 10,
            'is_free' => false,
            'is_default' => false,
            'sort_order' => 3,
            'features' => [
                'care.oil_change',
                'care.brake_service',
                'care.tyre_replacement',
                'care.battery',
                'care.service_history',
                'care.mileage_insights',
                'care.fuel_usage',
                'autodoc.connect',
                'autodoc.renewal_summary',
            ],
        ],
    ];

    public function run(): void
    {
        foreach ($this->plans as $definition) {
            $features = $definition['features'];
            $slug = $definition['slug'];

            $plan = SubscriptionPlan::updateOrCreate(
                ['slug' => $slug],
                collect($definition)->except(['slug', 'features'])->all(),
            );

            $plan->features()->delete();

            foreach (array_unique($features) as $featureKey) {
                $plan->features()->create(['feature_key' => $featureKey]);
            }
        }

        $this->command?->info('Subscription plans: '.count($this->plans).' seeded.');
    }
}
