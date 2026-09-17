<?php

namespace Tests\Feature\Manage;

use App\Models\Admin;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Staff and customers must never be able to use each other's surface.
 */
class AdminGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_the_admin_table_is_separate_from_the_users_table(): void
    {
        $admin = Admin::factory()->create(['email' => 'staff@autosecure.ng']);

        $this->assertDatabaseHas('admins', ['email' => 'staff@autosecure.ng']);
        $this->assertDatabaseMissing('users', ['email' => 'staff@autosecure.ng']);

        // An admin's credentials are useless on the customer guard.
        $this->assertNull(auth('web')->getProvider()->retrieveByCredentials([
            'email' => $admin->email,
            'password' => 'password',
        ]));
    }

    public function test_a_guest_is_redirected_to_the_staff_login(): void
    {
        $this->get('/manage')->assertRedirect(route('manage.login'));
    }

    public function test_a_customer_session_does_not_authenticate_the_staff_portal(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/manage')
            ->assertRedirect(route('manage.login'));
    }

    public function test_an_admin_can_sign_in_and_reach_the_dashboard(): void
    {
        $admin = Admin::factory()->create(['password' => 'Password!2345']);

        $this->post('/manage/auth/login', [
            'email' => $admin->email,
            'password' => 'Password!2345',
        ])->assertRedirect(route('manage.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->get('/manage/dashboard')->assertOk()->assertSee('Dashboard');
        $this->get('/manage')->assertRedirect(route('manage.dashboard'));
    }

    public function test_a_suspended_admin_cannot_sign_in(): void
    {
        $admin = Admin::factory()->suspended()->create(['password' => 'Password!2345']);

        $this->post('/manage/login', [
            'email' => $admin->email,
            'password' => 'Password!2345',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_a_super_admin_reaches_every_module(): void
    {
        $admin = Admin::factory()->superAdmin()->create();

        foreach (['customers', 'vendors', 'payments', 'audit-logs'] as $module) {
            $this->actingAs($admin, 'admin')
                ->get('/manage/'.$module)
                ->assertOk();
        }
    }

    public function test_a_module_is_blocked_without_its_permission(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get('/manage/payments')
            ->assertForbidden()
            ->assertSee('payments.view');
    }

    public function test_a_role_grants_access_to_its_modules(): void
    {
        $admin = Admin::factory()->create();

        $role = Role::where('slug', 'vendor-officer')->firstOrFail();
        $role->assignTo($admin);

        $this->actingAs($admin->fresh(), 'admin')
            ->get('/manage/vendors')
            ->assertOk();

        $this->actingAs($admin, 'admin')
            ->get('/manage/settings')
            ->assertForbidden();
    }

    public function test_permissions_are_created_under_grouped_slugs(): void
    {
        $this->assertDatabaseHas('permissions', [
            'slug' => 'vendors.verify',
            'group' => 'vendors',
        ]);

        $this->assertGreaterThan(30, Permission::count());
    }

    public function test_the_super_admin_role_holds_every_permission(): void
    {
        $role = Role::where('slug', 'super-admin')->firstOrFail();

        $this->assertSame(Permission::count(), $role->permissions()->count());
    }

    public function test_every_role_permission_link_has_a_uuid(): void
    {
        $this->assertSame(
            0,
            \App\Models\RolePermission::whereNull('uuid')->count(),
        );

        $this->assertSame(
            0,
            \App\Models\AdminRole::whereNull('uuid')->count(),
        );
    }
}
