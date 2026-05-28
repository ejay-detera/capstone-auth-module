<?php

namespace App\Policies;

use App\Models\User;

class AdminPolicy
{
    /**
     * Determine whether the user can manage users.
     */
    public function manageUsers(User $user): bool
    {
        if (!$user->profile || !$user->profile->role_id) {
            return false;
        }
        
        $role = \DB::table('roles')->where('id', $user->profile->role_id)->first();
        
        if ($role && $role->name === 'Super Admin') {
            return true;
        }

        $hasPermission = \Illuminate\Support\Facades\DB::table('role_permission')
            ->join('permissions', 'role_permission.permission_id', '=', 'permissions.id')
            ->where('role_permission.role_id', $user->profile->role_id)
            ->where('permissions.slug', 'manage-users')
            ->exists();

        return $hasPermission;
    }
}
