<?php

namespace App\Support;

/**
 * The single registry of AUTOSECURE 2.0 areas that cannot be built yet.
 *
 * Both the HTTP placeholder (App\Http\Controllers\Api\V1\PendingController) and
 * the device provider drivers (App\Services\Devices\Providers\Null*) read from
 * here, so the "what is still outstanding" answer can never drift between the
 * API response and the driver error.
 *
 * When a provider is integrated, remove its entry from `MODULES` and delete the
 * matching 501 route group in routes/api.php.
 */
class PendingIntegrations
{
    /**
     * @var array<string, array{title: string, summary: string, blocked_by: array<int, string>}>
     */
    public const MODULES = [
        'security' => [
            'title' => 'Tracker / Security',
            'summary' => 'Live location, trip playback, call vehicle and remote shutdown.',
            'blocked_by' => [
                'Tracker API documentation and base URL',
                'Tracker authentication method and credential lifecycle',
                'Vehicle/device identification scheme',
                'GPS location endpoint and update cadence',
                'Playback/history endpoint and retention period',
                'Command endpoints (remote shutdown, restore, call vehicle)',
                'Remote shutdown safety rules and interlock behaviour',
                'Command acknowledgement payload and status codes',
                'Device status endpoint and webhook/callback contract',
            ],
        ],
        'dashcam' => [
            'title' => 'Dashcam',
            'summary' => 'Live video, playback, emergencies, snapshots and device controls.',
            'blocked_by' => [
                'Dashcam SDK/API documentation and distribution rights',
                'Live streaming method (SDK vs HLS/WebRTC gateway)',
                'Playback/recording access and file listing endpoints',
                'Device pairing (QR/barcode) contract',
                'Device controls (restart, unbind, SD card, sharing)',
                'Credential/token exchange so streaming secrets stay server side',
                'iOS and Android support matrix and background behaviour',
            ],
        ],
        'autodoc' => [
            'title' => 'AutoDoc connection',
            'summary' => 'Vehicle documentation and renewal reminders from the AutoDoc app.',
            'blocked_by' => [
                'Agreed integration level (deep link / shared sign-in / summary API)',
                'Shared identity contract and stable customer id',
                'Vehicle matching key (internal vehicle id, not plate number)',
                'Deep link URL scheme and store fallback URLs',
                'Renewal summary API contract (if level 3 is approved)',
                'Consent and data-sharing wording',
            ],
        ],
        'care' => [
            'title' => 'Vehicle Care',
            'summary' => 'Service history, reminders, mileage and fuel insights.',
            'blocked_by' => [
                'Tracker odometer availability for automatic mileage sync',
                'Reminder channel configuration (email/SMS provider)',
            ],
        ],
        'finder' => [
            'title' => 'Finder marketplace',
            'summary' => 'Verified parts sellers, car washes and mechanics near you.',
            'blocked_by' => [
                'Approved final vendor annual fee and tiers',
                'Commission percentage or flat fee per vendor category',
                'Vendor verification document list and operating policy',
            ],
        ],
        'bookings' => [
            'title' => 'Bookings & orders',
            'summary' => 'Book a service, track it and pay in the app.',
            'blocked_by' => [
                'Cancellation and dispute policy per category',
                'Fulfilment rules (in-store, mobile, delivery, pickup)',
                'Vendor settlement schedule',
            ],
        ],
        'payments' => [
            'title' => 'Payments',
            'summary' => 'Subscriptions, bookings and refunds.',
            'blocked_by' => [
                'Payment gateway selection and API credentials',
                'Webhook signature/verification method',
                'Refund ownership and reversal flow',
                'Payout/settlement account verification provider',
            ],
        ],
    ];

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::MODULES);
    }

    public static function has(string $module): bool
    {
        return array_key_exists($module, self::MODULES);
    }

    /**
     * @return array{title: string, summary: string, blocked_by: array<int, string>}|null
     */
    public static function definition(string $module): ?array
    {
        return self::MODULES[$module] ?? null;
    }

    public static function title(string $module): string
    {
        return self::MODULES[$module]['title'] ?? 'Unknown module';
    }

    /**
     * @return array<int, string>
     */
    public static function blockedBy(string $module): array
    {
        return self::MODULES[$module]['blocked_by'] ?? [];
    }

    /**
     * The standard 501 body used by both the placeholder controller and the
     * device provider drivers.
     *
     * @return array<string, mixed>
     */
    public static function payload(string $module): array
    {
        return [
            'code' => 'integration_pending',
            'module' => $module,
            'title' => self::title($module),
            'message' => 'This part of the AUTOSECURE 2.0 API is not implemented yet because the '
                .'required AUTOSECURE/provider documentation has not been supplied.',
            'blocked_by' => self::blockedBy($module),
            'documentation' => 'docs/phase-1/PENDING-INFORMATION.md',
        ];
    }
}
