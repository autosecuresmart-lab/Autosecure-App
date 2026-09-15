<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends BaseModel
{
    protected $fillable = [
        'name',
        'slug',
        'guard',
        'description',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission')
            ->withPivot(['uuid'])
            ->withTimestamps();
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(Admin::class, 'admin_role')
            ->withPivot(['uuid', 'assigned_by_admin_id'])
            ->withTimestamps();
    }

    /**
     * Replace this role's permission set.
     *
     * Explicitly creates/deletes RolePermission rows (instead of sync()) so every
     * link row receives a uuid like every other AUTOSECURE table.
     *
     * @param  array<int, int>  $permissionIds
     */
    public function syncPermissions(array $permissionIds): void
    {
        $permissionIds = array_values(array_unique(array_map('intval', $permissionIds)));

        RolePermission::query()
            ->where('role_id', $this->getKey())
            ->when(
                $permissionIds !== [],
                fn ($query) => $query->whereNotIn('permission_id', $permissionIds),
            )
            ->delete();

        $existing = RolePermission::query()
            ->where('role_id', $this->getKey())
            ->pluck('permission_id')
            ->all();

        foreach (array_diff($permissionIds, $existing) as $permissionId) {
            RolePermission::create([
                'role_id' => $this->getKey(),
                'permission_id' => $permissionId,
            ]);
        }

        $this->unsetRelation('permissions');
    }

    /**
     * Grant this role to a staff account.
     */
    public function assignTo(Admin $admin, ?Admin $assignedBy = null): AdminRole
    {
        $existing = AdminRole::query()
            ->where('admin_id', $admin->getKey())
            ->where('role_id', $this->getKey())
            ->first();

        if ($existing instanceof AdminRole) {
            return $existing;
        }

        return AdminRole::create([
            'admin_id' => $admin->getKey(),
            'role_id' => $this->getKey(),
            'assigned_by_admin_id' => $assignedBy?->getKey(),
        ]);
    }
}
