<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;

/**
 * Runtime-editable platform settings.
 *
 * Values mirror config/autosecure.php defaults so the admin portal has something
 * concrete to edit. Anything marked as awaiting approval is called out in the
 * description so nobody assumes it is final.
 */
class AppSettingSeeder extends Seeder
{
    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $settings = [
        // --- Finder commercials (PENDING approval) ---------------------------
        [
            'group' => 'finder',
            'key' => 'finder.default_commission_percent',
            'value' => '7.5',
            'type' => 'decimal',
            'label' => 'Default booking commission (%)',
            'description' => 'Applied when neither the vendor nor its category sets a commission. Awaiting management approval.',
        ],
        [
            'group' => 'finder',
            'key' => 'finder.vendor_annual_fee',
            'value' => '15000',
            'type' => 'decimal',
            'label' => 'Vendor annual subscription (NGN)',
            'description' => 'Proposal range is ₦12,000-₦15,000. Awaiting the final figure and tier structure.',
        ],

        // --- Coins (PENDING approval) ----------------------------------------
        [
            'group' => 'coins',
            'key' => 'coins.earn_rate_percent',
            'value' => '1',
            'type' => 'decimal',
            'label' => 'Coins earned per qualifying payment (%)',
            'description' => 'Awaiting approval of the earning rate.',
        ],
        [
            'group' => 'coins',
            'key' => 'coins.naira_per_coin',
            'value' => '1',
            'type' => 'decimal',
            'label' => 'Naira value of one Coin',
            'description' => 'Awaiting approval.',
        ],
        [
            'group' => 'coins',
            'key' => 'coins.min_redemption',
            'value' => '500',
            'type' => 'integer',
            'label' => 'Minimum Coins per redemption',
            'description' => 'Awaiting approval.',
        ],
        [
            'group' => 'coins',
            'key' => 'coins.max_percent_payable',
            'value' => '20',
            'type' => 'decimal',
            'label' => 'Maximum % of an order payable with Coins',
            'description' => 'Awaiting approval.',
        ],
        [
            'group' => 'coins',
            'key' => 'coins.expiry_days',
            'value' => '365',
            'type' => 'integer',
            'label' => 'Coin expiry (days)',
            'description' => 'Awaiting approval.',
        ],
        [
            'group' => 'coins',
            'key' => 'coins.transferable',
            'value' => '0',
            'type' => 'boolean',
            'label' => 'Coins are transferable between customers',
            'description' => 'Awaiting approval.',
        ],

        // --- Subscriptions ----------------------------------------------------
        [
            'group' => 'subscriptions',
            'key' => 'subscriptions.grace_period_days',
            'value' => '7',
            'type' => 'integer',
            'label' => 'Grace period after a failed renewal (days)',
            'description' => 'Premium access continues for this long while payment is retried. Core security features are never affected.',
        ],
        [
            'group' => 'subscriptions',
            'key' => 'subscriptions.payment_retry_attempts',
            'value' => '3',
            'type' => 'integer',
            'label' => 'Payment retry attempts',
            'description' => 'Awaiting approval of the retry policy.',
        ],

        // --- Devices (PENDING integration) -----------------------------------
        [
            'group' => 'devices',
            'key' => 'devices.command_ack_timeout_seconds',
            'value' => '60',
            'type' => 'integer',
            'label' => 'Device command acknowledgement timeout (seconds)',
            'description' => 'After this, a command is reported as timeout - never as successful. To be confirmed against the tracker protocol.',
        ],
        [
            'group' => 'devices',
            'key' => 'devices.command_max_attempts',
            'value' => '3',
            'type' => 'integer',
            'label' => 'Maximum device command attempts',
            'description' => 'To be confirmed against the tracker protocol.',
        ],

        // --- Support / privacy ------------------------------------------------
        [
            'group' => 'support',
            'key' => 'support.grant_minutes',
            'value' => '60',
            'type' => 'integer',
            'label' => 'Default sensitive-access grant duration (minutes)',
            'description' => 'Support access to location, video, voice or documents is always time-boxed and audited.',
        ],
        [
            'group' => 'privacy',
            'key' => 'privacy.location_retention_days',
            'value' => '90',
            'type' => 'integer',
            'label' => 'Location history retention (days)',
            'description' => 'Awaiting approval of the retention policy.',
        ],
        [
            'group' => 'privacy',
            'key' => 'privacy.video_retention_days',
            'value' => '30',
            'type' => 'integer',
            'label' => 'Dashcam recording retention (days)',
            'description' => 'Awaiting approval. Provider retention also applies.',
        ],

        // --- Public (safe to expose to the mobile app) -----------------------
        [
            'group' => 'app',
            'key' => 'app.minimum_mobile_version',
            'value' => '1.0.0',
            'type' => 'string',
            'label' => 'Minimum supported mobile app version',
            'description' => 'Used to force an upgrade prompt on outdated builds.',
            'is_public' => true,
        ],
        [
            'group' => 'app',
            'key' => 'app.theft_trigger_requires_pin',
            'value' => '1',
            'type' => 'boolean',
            'label' => 'Theft trigger requires PIN or biometric confirmation',
            'description' => 'Product rule from the proposal. Do not disable without a documented decision.',
            'is_public' => true,
        ],
    ];

    public function run(): void
    {
        foreach ($this->settings as $setting) {
            AppSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting + ['is_public' => false],
            );
        }

        $this->command?->info('App settings: '.count($this->settings).' seeded.');
    }
}
