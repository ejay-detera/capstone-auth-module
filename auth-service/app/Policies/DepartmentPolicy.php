<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class DepartmentPolicy
{
    /**
     * Determine whether the user can manage departments.
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

        return DB::table('role_permission')
            ->join('permissions', 'role_permission.permission_id', '=', 'permissions.id')
            ->where('role_permission.role_id', $user->profile->role_id)
            ->where('permissions.slug', 'manage-departments')
            ->exists();
    }
}
