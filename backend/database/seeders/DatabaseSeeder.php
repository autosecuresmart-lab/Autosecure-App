<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Order matters: roles/permissions must exist before the first admin is
     * given the super-admin role, and subscription plans before any customer
     * subscription is created.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            SubscriptionPlanSeeder::class,
            VendorCategorySeeder::class,
            AppSettingSeeder::class,
        ]);

        // Creates the first /manage account. Set ADMIN_EMAIL + ADMIN_PASSWORD in
        // the environment before running this in a shared environment.
        $this->call(AdminUserSeeder::class);
    }
}
