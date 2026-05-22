<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class RolePolicy
{
    /**
     * Determine whether the user can manage roles.
     */
    public function manage(User $user): bool
    {
        if (!$user->profile || !$user->profile->role_id) {
            return false;
        }

        $role = DB::table('roles')->where('id', $user->profile->role_id)->first();
        
        if ($role && $role->name === 'Super Admin') {
            return true;
        }

        $department = DB::table('departments')->where('id', $user->profile->department_id)->first();
        if ($role && $role->name === 'Admin' && $department && $department->name === 'Finance') {
            return true;
        }

        return DB::table('role_permission')
            ->join('permissions', 'role_permission.permission_id', '=', 'permissions.id')
            ->where('role_permission.role_id', $user->profile->role_id)
            ->where('permissions.slug', 'manage-roles')
            ->exists();
    }
}
