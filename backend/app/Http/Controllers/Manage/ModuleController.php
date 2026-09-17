<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Renders the module shell for admin areas that are planned but not built.
 *
 * Each module is a separate phase of AUTOSECURE 2.0. Rather than fake screens,
 * /manage shows which module it is, what it will contain and which permission
 * guards it. This keeps the navigation and the authorisation model real from
 * day one without pretending the features exist.
 */
class ModuleController extends Controller
{
    /**
     * Planned admin modules and what each will contain.
     *
     * @var array<string, array{title: string, summary: string, features: array<int, string>, phase: string}>
     */
    public const MODULES = [
        'incidents' => [
            'title' => 'Incidents',
            'summary' => 'Active vehicle theft reports, telemetry investigation and live recovery coordination.',
            'features' => ['Incident dispatch queue', 'Telemetry & GPS trail playback', 'Evidence & incident log', 'Recovery status update'],
            'phase' => 'Phase 2 onwards',
        ],
        'live-map' => [
            'title' => 'Live Map',
            'summary' => 'Real-time GPS tracking and geofence monitoring of all connected fleet vehicles.',
            'features' => ['Live vehicle map marker clustering', 'Geofence boundaries', 'Speed & ignition telemetry', 'Emergency command dispatch'],
            'phase' => 'Phase 2 onwards',
        ],
        'assignments' => [
            'title' => 'Assignments',
            'summary' => 'Field recovery team assignment, dispatch routing and real-time response management.',
            'features' => ['Recovery officer allocation', 'Incident task checklist', 'Field communication log', 'Resolution verification'],
            'phase' => 'Phase 2 onwards',
        ],
        'alerts' => [
            'title' => 'Alerts',
            'summary' => 'Instant telemetry alarms, speed alerts, geo-fence breaches and system notifications.',
            'features' => ['Real-time alarm pipeline', 'Severity classification', 'Automated push & SMS rules', 'Acknowledge & resolve queue'],
            'phase' => 'Phase 2 onwards',
        ],
        'reports' => [
            'title' => 'Reports',
            'summary' => 'Subscriptions, churn, bookings, revenue, vendors, Coins and feature usage.',
            'features' => ['Revenue and commission', 'Conversion and churn', 'Device command success rate', 'Loyalty liability'],
            'phase' => 'Phase 6',
        ],
        'sla-monitoring' => [
            'title' => 'SLA Monitoring',
            'summary' => 'Incident response time metrics, officer recovery SLA targets and performance benchmarks.',
            'features' => ['Response time tracking', 'SLA breach notifications', 'Officer performance scorecard', 'Weekly SLA trends'],
            'phase' => 'Phase 2 onwards',
        ],
        'audit-logs' => [
            'title' => 'Audit logs',
            'summary' => 'Every customer, staff, device and sensitive-data action.',
            'features' => ['Searchable audit trail', 'Actor and record filters', 'Sensitive access review', 'Export'],
            'phase' => 'Phase 2 onwards',
        ],
        'users-roles' => [
            'title' => 'Users & Roles',
            'summary' => 'Administrative staff permissions, operational roles and access control policies.',
            'features' => ['Staff directory', 'Role & permission assignment', 'Session management', '2FA enforcement'],
            'phase' => 'Phase 2 onwards',
        ],
        'settings' => [
            'title' => 'Settings',
            'summary' => 'Platform settings: commission, coin rates, grace period, retention.',
            'features' => ['Commercial rules', 'Feature toggles', 'Retention policy', 'Change audit'],
            'phase' => 'Phase 3 onwards',
        ],
        'customers' => [
            'title' => 'Customers & Vehicles',
            'summary' => 'Customer records, linked vehicles and devices, approved account actions.',
            'features' => ['Customer search and profile', 'Vehicle and device links', 'Account status actions', 'Support cases'],
            'phase' => 'Phase 2 onwards',
        ],
        'vehicles' => [
            'title' => 'Vehicles',
            'summary' => 'Every vehicle on the platform with its tracker and dashcam bindings.',
            'features' => ['Vehicle register', 'Device binding history', 'Mileage source (manual vs tracker)'],
            'phase' => 'Phase 2 onwards',
        ],
        'devices' => [
            'title' => 'Devices',
            'summary' => 'Tracker and dashcam inventory, status, firmware and command history.',
            'features' => ['Device inventory', 'Online/offline status', 'Command history and acknowledgements', 'Provider mapping'],
            'phase' => 'Phase 2 (needs provider docs)',
        ],
        'vendors' => [
            'title' => 'Vendors',
            'summary' => 'Applications, approval, suspension, renewal and categorisation.',
            'features' => ['Vendor list and filters', 'Approve / reject / suspend', 'Subscription renewal', 'Category assignment'],
            'phase' => 'Phase 5',
        ],
        'vendor-verifications' => [
            'title' => 'Vendor verification',
            'summary' => 'Identity, business, location and payout checks before activation.',
            'features' => ['Verification pipeline', 'Document review', 'Pass / needs update / reject', 'Verification audit'],
            'phase' => 'Phase 5',
        ],
        'bookings' => [
            'title' => 'Bookings & orders',
            'summary' => 'Status, cancellations, disputes, refunds and commission.',
            'features' => ['Booking monitor', 'Cancellation and dispute handling', 'Commission calculation', 'Refund workflow'],
            'phase' => 'Phase 5',
        ],
        'payments' => [
            'title' => 'Payments & settlements',
            'summary' => 'Customer payments, vendor payouts, fees, refunds and exceptions.',
            'features' => ['Payment reconciliation', 'Vendor payouts', 'Exceptions queue', 'Refund ownership'],
            'phase' => 'Phase 5 (needs gateway sign-off)',
        ],
        'subscriptions' => [
            'title' => 'Subscriptions',
            'summary' => 'Plans, discounts, grace periods, entitlements and promo codes.',
            'features' => ['Plan configuration', 'Grace period and retry policy', 'Entitlement matrix', 'Promo codes'],
            'phase' => 'Phase 3',
        ],
        'coins' => [
            'title' => 'Coins',
            'summary' => 'Earn/redeem rule configuration and the full transaction ledger.',
            'features' => ['Earn and redeem rules', 'Ledger inspection', 'Reversals and expiry', 'Liability balance'],
            'phase' => 'Phase 6',
        ],
        'content' => [
            'title' => 'Content',
            'summary' => 'Finder categories, help content, notification templates and policies.',
            'features' => ['Finder categories', 'Help content', 'Notification templates', 'Policy documents'],
            'phase' => 'Phase 5',
        ],
        'support' => [
            'title' => 'Support',
            'summary' => 'Customer support cases and controlled, audited access to sensitive data.',
            'features' => ['Support cases', 'Time-boxed access grants', 'Sensitive access log'],
            'phase' => 'Phase 2 onwards',
        ],
    ];

    /**
     * Permission slug that guards a module, e.g. `vendor-verifications` ->
     * `vendor_verifications.view`.
     */
    public static function permissionFor(string $module): string
    {
        return str_replace('-', '_', $module).'.view';
    }

    public function __invoke(Request $request): View
    {
        $module = (string) $request->route('module');
        $definition = self::MODULES[$module] ?? null;

        abort_if($definition === null, 404);

        $viewName = view()->exists('manage.'.$module) ? 'manage.'.$module : 'manage.module';

        $extraData = [];
        if ($module === 'incidents') {
            $extraData = [
                'incidents' => \App\Models\TheftEvent::with(['vehicle.user', 'device'])->latest()->paginate(15),
                'active_count' => \App\Models\TheftEvent::where('status', \App\Models\TheftEvent::STATUS_OPEN)->count(),
                'resolved_count' => \App\Models\TheftEvent::where('status', \App\Models\TheftEvent::STATUS_RESOLVED)->count(),
                'total_count' => \App\Models\TheftEvent::count(),
            ];
        } elseif ($module === 'live-map') {
            $extraData = [
                'vehicles' => \App\Models\Vehicle::with(['device', 'user'])->latest()->get(),
                'total_devices' => \App\Models\Device::count(),
                'active_devices' => \App\Models\Device::where('status', 'active')->count(),
            ];
        }

        return view($viewName, array_merge([
            'module' => $module,
            'definition' => $definition,
            'navigation' => self::MODULES,
        ], $extraData));
    }
}

