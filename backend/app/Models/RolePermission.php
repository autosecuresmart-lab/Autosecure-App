<?php

namespace App\Models;

/**
 * Role <-> Permission link.
 *
 * A first-class model rather than a bare join row, so the `uuid` every
 * AUTOSECURE table carries is generated on every insert. Laravel's
 * `attach()`/`sync()` insert straight through the query builder and would skip
 * that, which is why Role::syncPermissions() is used instead.
 */
class RolePermission extends BaseModel
{
    protected $table = 'role_permission';

    protected $fillable = [
        'role_id',
        'permission_id',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}
