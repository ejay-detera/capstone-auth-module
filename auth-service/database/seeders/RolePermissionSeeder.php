<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'Super Admin', 'description' => 'System wide access'],
            ['name' => 'IT Admin', 'description' => 'IT infrastructure and user management'],
            ['name' => 'Department Manager', 'description' => 'Management of specific department'],
            ['name' => 'Employee', 'description' => 'Regular staff access'],
        ];

        foreach ($roles as $role) {
            \DB::table('roles')->updateOrInsert(['name' => $role['name']], $role);
        }

        $permissions = [
            ['name' => 'View Dashboard', 'slug' => 'view-dashboard'],
            ['name' => 'Manage Users', 'slug' => 'manage-users'],
            ['name' => 'Manage Roles', 'slug' => 'manage-roles'],
            ['name' => 'View Audit Logs', 'slug' => 'view-audit-logs'],
        ];

        foreach ($permissions as $permission) {
            \DB::table('permissions')->updateOrInsert(['slug' => $permission['slug']], $permission);
        }
    }
}
