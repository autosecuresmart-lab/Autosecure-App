<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Creates the first staff account.
 *
 * Credentials come from the environment so a password is never committed:
 *   ADMIN_EMAIL, ADMIN_PASSWORD, ADMIN_NAME
 *
 * If no password is supplied a random one is generated and printed once.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@autosecure.ng');
        $password = env('ADMIN_PASSWORD');
        $generated = false;

        if (blank($password)) {
            $password = Str::password(16);
            $generated = true;
        }

        $admin = Admin::firstOrNew(['email' => $email]);

        $admin->fill([
            'name' => env('ADMIN_NAME', 'AUTOSECURE Administrator'),
            'phone' => env('ADMIN_PHONE'),
            'job_title' => 'Platform Owner',
            'status' => 'active',
        ]);

        if (! $admin->exists) {
            $admin->password = Hash::make($password);
        }

        $admin->is_super_admin = true;
        $admin->save();

        $admin->assignRoleBySlug('super-admin');

        $this->command?->info("Super admin ready: {$email}");

        if ($generated) {
            $this->command?->warn("Generated password (store it now, it is not shown again): {$password}");
        }
    }
}
