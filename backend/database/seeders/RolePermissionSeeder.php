<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Roles and permissions for the /manage portal.
 *
 * Permission slugs are `<module>.<action>`; `ModuleController::permissionFor()`
 * maps a module to the `<module>.view` slug that guards its route, so the sidebar
 * and the server-side check always agree.
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * module => permission names, grouped for the admin permission matrix.
     *
     * @var array<string, array<string, string>>
     */
    protected array $permissions = [
        'customers' => [
            'customers.view' => 'View customers',
            'customers.update' => 'Edit customer details',
            'customers.suspend' => 'Suspend or reactivate a customer',
        ],
        'vehicles' => [
            'vehicles.view' => 'View vehicles',
            'vehicles.update' => 'Edit vehicle details',
            'vehicles.unbind_device' => 'Unbind a tracker or dashcam from a vehicle',
        ],
        'devices' => [
            'devices.view' => 'View device inventory and status',
            'devices.manage' => 'Register, bind and suspend devices',
            'devices.command' => 'Send device commands',
        ],
        'security' => [
            'security.view' => 'View security and theft events',
            'security.manage' => 'Resolve theft events',
            'security.override_shutdown' => 'Override a remote shutdown safety rule',
        ],
        'sensitive_access' => [
            'sensitive_access.location' => 'Access customer location data',
            'sensitive_access.video' => 'Access dashcam video',
            'sensitive_access.voice' => 'Access vehicle voice monitoring',
            'sensitive_access.documents' => 'Access vehicle documentation',
        ],
        'vendors' => [
            'vendors.view' => 'View vendors',
            'vendors.manage' => 'Create and edit vendors',
            'vendors.verify' => 'Approve or reject vendor verification',
            'vendors.suspend' => 'Suspend or remove a vendor',
        ],
        'vendor_verifications' => [
            'vendor_verifications.view' => 'View the verification queue',
            'vendor_verifications.review' => 'Review verification evidence',
        ],
        'bookings' => [
            'bookings.view' => 'View bookings and orders',
            'bookings.manage' => 'Manage booking status',
            'bookings.dispute' => 'Handle disputes and cancellations',
            'bookings.refund' => 'Issue refunds',
        ],
        'payments' => [
            'payments.view' => 'View payments',
            'payments.reconcile' => 'Reconcile payments and settlements',
            'payments.refund' => 'Process refunds and reversals',
        ],
        'subscriptions' => [
            'subscriptions.view' => 'View subscriptions',
            'subscriptions.manage' => 'Change a customer subscription',
            'subscriptions.plans' => 'Configure plans, discounts and grace periods',
        ],
        'coins' => [
            'coins.view' => 'View the Coins ledger',
            'coins.manage' => 'Configure earn and redemption rules',
            'coins.adjust' => 'Make manual wallet adjustments',
        ],
        'reports' => [
            'reports.view' => 'View reports',
            'reports.export' => 'Export reports',
        ],
        'content' => [
            'content.view' => 'View content',
            'content.manage' => 'Manage categories, help content and templates',
        ],
        'support' => [
            'support.view' => 'View support cases',
            'support.grant_access' => 'Grant time-boxed sensitive data access',
            'support.revoke_access' => 'Revoke sensitive data access',
        ],
        'audit_logs' => [
            'audit_logs.view' => 'View the audit trail',
            'audit_logs.export' => 'Export the audit trail',
        ],
        'settings' => [
            'settings.view' => 'View platform settings',
            'settings.manage' => 'Change platform settings',
        ],
    ];

    /**
     * role => [name, description, permission slugs]
     *
     * @var array<string, array{name: string, description: string, permissions: array<int, string>}>
     */
    protected array $roles = [
        'super-admin' => [
            'name' => 'Super Admin',
            'description' => 'Unrestricted platform access. Reserved for AUTOSECURE owners.',
            'permissions' => ['*'],
        ],
        'operations-manager' => [
            'name' => 'Operations Manager',
            'description' => 'Day-to-day customer, vehicle and device operations including theft events.',
            'permissions' => [
                'customers.view', 'customers.update', 'customers.suspend',
                'vehicles.view', 'vehicles.update', 'vehicles.unbind_device',
                'devices.view', 'devices.manage', 'devices.command',
                'security.view', 'security.manage',
                'sensitive_access.location',
                'support.view', 'support.grant_access', 'support.revoke_access',
                'audit_logs.view',
                'reports.view',
            ],
        ],
        'support-agent' => [
            'name' => 'Support Agent',
            'description' => 'Customer support with time-boxed, audited access to sensitive data only.',
            'permissions' => [
                'customers.view',
                'vehicles.view',
                'devices.view',
                'security.view',
                'support.view',
                'sensitive_access.location',
            ],
        ],
        'vendor-officer' => [
            'name' => 'Vendor Officer',
            'description' => 'Vendor onboarding, verification and category management.',
            'permissions' => [
                'vendors.view', 'vendors.manage', 'vendors.verify', 'vendors.suspend',
                'vendor_verifications.view', 'vendor_verifications.review',
                'content.view', 'content.manage',
                'reports.view',
            ],
        ],
        'finance-officer' => [
            'name' => 'Finance Officer',
            'description' => 'Payments, settlements, subscriptions, Coins and financial reporting.',
            'permissions' => [
                'payments.view', 'payments.reconcile', 'payments.refund',
                'subscriptions.view', 'subscriptions.manage', 'subscriptions.plans',
                'coins.view', 'coins.manage', 'coins.adjust',
                'bookings.view', 'bookings.refund',
                'reports.view', 'reports.export',
                'audit_logs.view',
            ],
        ],
        'content-manager' => [
            'name' => 'Content Manager',
            'description' => 'Finder categories, help content and notification templates.',
            'permissions' => [
                'content.view', 'content.manage',
                'bookings.view',
                'reports.view',
            ],
        ],
    ];

    public function run(): void
    {
        // --- Permissions ------------------------------------------------------
        $permissionIds = [];

        foreach ($this->permissions as $group => $items) {
            foreach ($items as $slug => $name) {
                $permission = Permission::firstOrCreate(
                    ['slug' => $slug],
                    ['name' => $name, 'group' => $group],
                );

                $permissionIds[$slug] = $permission->getKey();
            }
        }

        // --- Roles ------------------------------------------------------------
        foreach ($this->roles as $slug => $definition) {
            $role = Role::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $definition['name'],
                    'guard' => 'admin',
                    'description' => $definition['description'],
                    'is_system' => true,
                ],
            );

            $role->forceFill([
                'name' => $definition['name'],
                'description' => $definition['description'],
                'is_system' => true,
            ])->save();

            $slugs = $definition['permissions'] === ['*']
                ? array_keys($permissionIds)
                : $definition['permissions'];

            $role->syncPermissions(
                collect($slugs)
                    ->filter(fn (string $slug) => isset($permissionIds[$slug]))
                    ->map(fn (string $slug) => $permissionIds[$slug])
                    ->all(),
            );
        }

        $this->command?->info(sprintf(
            'Roles/permissions: %d permissions across %d roles.',
            count($permissionIds),
            count($this->roles),
        ));
    }
}
