<?php

/*
|--------------------------------------------------------------------------
| AUTOSECURE 2.0 — Domain configuration
|--------------------------------------------------------------------------
|
| Central place for commercial rules, entitlements and integration drivers so
| they can be changed without touching application code. Anything that the
| business must be able to tune later is also mirrored in the `app_settings`
| table (editable from /manage).
|
| NOTE: The values below are DEFAULTS. Several commercial numbers are still
| awaiting management sign-off (see docs/phase-1/PENDING-INFORMATION.md).
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Mobile API
    |--------------------------------------------------------------------------
    */
    'api' => [
        'prefix' => env('API_PREFIX', 'api'),
        'version' => env('API_VERSION', 'v1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cron Secret Key
    |--------------------------------------------------------------------------
    | Used to secure HTTP cron webhook triggers.
    */
    'cron' => [
        'key' => env('CRON_KEY', 'autosecure-gprs-cron-secret'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mobile app
    |--------------------------------------------------------------------------
    | `scheme` is the custom URL scheme declared in frontend/app.json. Password
    | reset and AutoDoc hand-off deep links are built from it.
    */
    'mobile' => [
        'scheme' => env('MOBILE_APP_SCHEME', 'autosecure'),
        'ios_store_url' => env('MOBILE_IOS_STORE_URL'),
        'android_store_url' => env('MOBILE_ANDROID_STORE_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscription plans
    |--------------------------------------------------------------------------
    | Security features are ALWAYS available on the free plan. Premium only
    | unlocks the vehicle care / ownership modules.
    */
    'subscription' => [
        'currency' => 'NGN',
        'grace_period_days' => (int) env('SUBSCRIPTION_GRACE_DAYS', 7),
        'payment_retry_attempts' => (int) env('SUBSCRIPTION_RETRY_ATTEMPTS', 3),
        'plans' => [
            'monthly' => ['label' => 'Monthly', 'price' => 4200, 'duration_days' => 30, 'discount_percent' => 0],
            'half_yearly' => ['label' => 'Half-Year', 'price' => 23940, 'duration_days' => 182, 'discount_percent' => 5],
            'yearly' => ['label' => 'Yearly', 'price' => 45360, 'duration_days' => 365, 'discount_percent' => 10],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature entitlements
    |--------------------------------------------------------------------------
    | Keys are checked server side. `free` keys must never be removed by a
    | downgrade — core security and dashcam access stay available forever.
    */
    'entitlements' => [
        'free' => [
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
        'premium' => [
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

    /*
    |--------------------------------------------------------------------------
    | Loyalty coins ("Coins")
    |--------------------------------------------------------------------------
    | Every value here is PENDING commercial sign-off.
    */
    'coins' => [
        'naira_per_coin' => (float) env('COINS_NAIRA_PER_COIN', 1.0),
        'earn_rate_percent' => (float) env('COINS_EARN_RATE_PERCENT', 1.0),
        'min_redemption' => (int) env('COINS_MIN_REDEMPTION', 500),
        'max_percent_payable' => (float) env('COINS_MAX_PERCENT_PAYABLE', 20.0),
        'expiry_days' => (int) env('COINS_EXPIRY_DAYS', 365),
        'transferable' => (bool) env('COINS_TRANSFERABLE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Finder marketplace
    |--------------------------------------------------------------------------
    */
    'finder' => [
        'default_commission_percent' => (float) env('FINDER_COMMISSION_PERCENT', 7.5),
        'default_vendor_annual_fee' => (float) env('FINDER_VENDOR_ANNUAL_FEE', 15000),
        'require_verification_before_listing' => true,
        'default_service_radius_km' => (int) env('FINDER_SERVICE_RADIUS_KM', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Vehicle care reminder windows
    |--------------------------------------------------------------------------
    */
    'care' => [
        'due_soon_days' => (int) env('CARE_DUE_SOON_DAYS', 30),
        'due_soon_km' => (int) env('CARE_DUE_SOON_KM', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Device integrations — ALL PENDING
    |--------------------------------------------------------------------------
    | No tracker or dashcam API has been supplied yet. The contracts in
    | App\Contracts\Devices describe what the platform expects from a provider.
    | Setting a driver to anything other than "null" requires a matching
    | implementation plus real provider documentation.
    */
    'devices' => [
        'tracker' => [
            // Driver is configured for GPRS hardware protocol.
            'driver' => env('TRACKER_DRIVER') ?: 'gprs',
            'base_url' => env('TRACKER_BASE_URL'),
            'api_key' => env('TRACKER_API_KEY'),
            'api_secret' => env('TRACKER_API_SECRET'),
            // Commands the platform will send.
            'supports' => [
                'live_location' => true,
                'history' => true,
                'remote_shutdown' => true,
                'remote_restore' => true,
                'call_vehicle' => true,
                'theft_trigger' => true,
            ],
        ],

        'dashcam' => [
            'driver' => env('DASHCAM_DRIVER') ?: 'openapi',
            'base_url' => env('DASHCAM_BASE_URL'),
            'app_key' => env('DASHCAM_APP_KEY'),
            'app_secret' => env('DASHCAM_APP_SECRET'),
            'token_ttl_seconds' => (int) env('DASHCAM_TOKEN_TTL', 300),
            'supports' => [
                'live_stream' => null,
                'playback' => null,
                'emergencies' => null,
                'snapshot' => null,
                'pairing' => null,
                'unbind' => null,
            ],
        ],

        // A command that never receives a device acknowledgement must never be
        // reported as successful. These values drive that behaviour.
        'commands' => [
            'ack_timeout_seconds' => (int) env('DEVICE_COMMAND_ACK_TIMEOUT', 60),
            'max_attempts' => (int) env('DEVICE_COMMAND_MAX_ATTEMPTS', 3),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AutoDoc (separate AUTOSECURE application)
    |--------------------------------------------------------------------------
    */
    'autodoc' => [
        'driver' => env('AUTODOC_DRIVER') ?: 'null',
        'base_url' => env('AUTODOC_BASE_URL'),
        'client_id' => env('AUTODOC_CLIENT_ID'),
        'client_secret' => env('AUTODOC_CLIENT_SECRET'),
        'ios_store_url' => env('AUTODOC_IOS_URL'),
        'android_store_url' => env('AUTODOC_ANDROID_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Support access
    |--------------------------------------------------------------------------
    | Support agents may only see customer location/video through an explicit,
    | time-boxed, audited grant.
    */
    'support' => [
        'grant_minutes' => (int) env('SUPPORT_GRANT_MINUTES', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit log
    |--------------------------------------------------------------------------
    */
    'audit' => [
        'enabled' => (bool) env('AUDIT_LOG_ENABLED', true),
        'retention_days' => (int) env('AUDIT_RETENTION_DAYS', 730),
    ],
];
