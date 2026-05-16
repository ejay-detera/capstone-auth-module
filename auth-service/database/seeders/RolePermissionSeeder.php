<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
// RolePermissionSeeder.php

    public function run(): void
    {
        $roles = [
            ['name' => 'Super Admin',         'description' => 'System wide access'],
            ['name' => 'IT Admin',            'description' => 'IT infrastructure and user management'],
            ['name' => 'Admin',               'description' => 'General administration'],
            ['name' => 'Manager',             'description' => 'Management of specific department'],
            ['name' => 'Sales',               'description' => 'Sales staff access'],
            ['name' => 'Finance',             'description' => 'Finance staff access'],
            ['name' => 'Employee',            'description' => 'Regular staff access'],
        ];

        // insertOrIgnore: skips duplicates instead of crashing
        \DB::table('roles')->insertOrIgnore($roles);

        $permissions = [
            ['name' => 'View Dashboard',  'slug' => 'view-dashboard'],
            ['name' => 'Manage Users',    'slug' => 'manage-users'],
            ['name' => 'Manage Roles',    'slug' => 'manage-roles'],
            ['name' => 'View Audit Logs', 'slug' => 'view-audit-logs'],
        ];

        \DB::table('permissions')->insertOrIgnore($permissions);

        // Link management permissions to Admin roles
        $adminRoles = \DB::table('roles')->whereIn('name', ['Super Admin', 'IT Admin'])->pluck('id');
        $managementPermissions = \DB::table('permissions')
            ->whereIn('slug', ['manage-users', 'manage-roles', 'view-audit-logs'])
            ->pluck('id');

        foreach ($adminRoles as $roleId) {
            foreach ($managementPermissions as $permissionId) {
                \DB::table('role_permission')->insertOrIgnore([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId
                ]);
            }
        }
    }
}
