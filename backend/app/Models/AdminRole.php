<?php

namespace App\Models;

/**
 * Admin <-> Role link.
 *
 * A first-class model so `uuid` is populated on every insert; see the note on
 * RolePermission and Admin::syncRoles().
 */
class AdminRole extends BaseModel
{
    protected $table = 'admin_role';

    protected $fillable = [
        'admin_id',
        'role_id',
        'assigned_by_admin_id',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(Admin::class, 'assigned_by_admin_id');
    }
}
