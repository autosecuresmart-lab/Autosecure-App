<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

/**
 * AUTOSECURE staff account.
 *
 * Deliberately separate from App\Models\User: admins authenticate on the
 * `admin` guard against the `admins` table, and their authorisation comes from
 * roles/permissions rather than a single boolean.
 */
class Admin extends Authenticatable
{
    use HasFactory;
    use HasUuid;
    use Notifiable;
    use SoftDeletes;

    protected $guard = 'admin';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'job_title',
        'avatar_path',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'two_factor_enabled' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            'is_super_admin' => 'boolean',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'admin_role')
            ->withPivot(['uuid', 'assigned_by_admin_id'])
            ->withTimestamps();
    }

    /**
     * Replace this admin's roles.
     *
     * Uses explicit AdminRole rows (not sync()) so every link row carries a uuid
     * like every other AUTOSECURE table.
     *
     * @param  array<int, int>  $roleIds
     */
    public function syncRoles(array $roleIds, ?Admin $assignedBy = null): void
    {
        $roleIds = array_values(array_unique(array_map('intval', $roleIds)));

        AdminRole::query()
            ->where('admin_id', $this->getKey())
            ->when(
                $roleIds !== [],
                fn ($query) => $query->whereNotIn('role_id', $roleIds),
            )
            ->delete();

        $existing = AdminRole::query()
            ->where('admin_id', $this->getKey())
            ->pluck('role_id')
            ->all();

        foreach (array_diff($roleIds, $existing) as $roleId) {
            AdminRole::create([
                'admin_id' => $this->getKey(),
                'role_id' => $roleId,
                'assigned_by_admin_id' => $assignedBy?->getKey(),
            ]);
        }

        $this->unsetRelation('roles');
    }

    /**
     * Grant a role by slug (used by seeders).
     */
    public function assignRoleBySlug(string $slug, ?Admin $assignedBy = null): bool
    {
        $role = Role::where('slug', $slug)->first();

        if (! $role instanceof Role) {
            return false;
        }

        $role->assignTo($this, $assignedBy);
        $this->unsetRelation('roles');

        return true;
    }

    /**
     * Effective permissions = union of every role's permissions.
     */
    public function permissions(): Collection
    {
        return $this->roles
            ->flatMap(fn (Role $role) => $role->permissions)
            ->unique('id')
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Authorisation helpers
    |--------------------------------------------------------------------------
    */

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles->contains('slug', $slug) || $this->isSuperAdmin();
    }

    public function hasPermission(string $slug): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->permissions()->contains('slug', $slug);
    }

    /**
     * @param  array<int, string>  $slugs
     */
    public function hasAnyPermission(array $slugs): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->permissions()->whereIn('slug', $slugs)->isNotEmpty();
    }
}
