<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
        // insertOrIgnore: skips duplicates instead of crashing
        DB::table('roles')->insertOrIgnore($roles);

        $permissions = [
            // Auth Service Internal Permissions
            ['name' => 'View Dashboard',       'slug' => 'view-dashboard',       'system' => 'auth'],
            ['name' => 'Manage Users',          'slug' => 'manage-users',         'system' => 'auth'],
            ['name' => 'Manage Roles',          'slug' => 'manage-roles',         'system' => 'auth'],
            ['name' => 'View Audit Logs',       'slug' => 'view-audit-logs',      'system' => 'auth'],
            ['name' => 'Manage Departments',    'slug' => 'manage-departments',   'system' => 'auth'],

            // CRMS Specific Permissions
            ['name' => 'Manage CRMS Roles', 'slug' => 'crms.roles.manage', 'system' => 'crms'],
            ['name' => 'Manage Templates',  'slug' => 'crms.templates.manage', 'system' => 'crms'],
            ['name' => 'Use Templates',     'slug' => 'crms.templates.use', 'system' => 'crms'],
            ['name' => 'OCR Upload',        'slug' => 'crms.ocr.upload', 'system' => 'crms'],
            ['name' => 'OCR Process',       'slug' => 'crms.ocr.process', 'system' => 'crms'],
            ['name' => 'OCR Review',        'slug' => 'crms.ocr.review', 'system' => 'crms'],
            ['name' => 'Generate Draft',    'slug' => 'crms.contracts.generate', 'system' => 'crms'],
            ['name' => 'Run Risk Assessment', 'slug' => 'crms.risk.assess', 'system' => 'crms'],
            ['name' => 'View Risk Highlights', 'slug' => 'crms.risk.view', 'system' => 'crms'],
            ['name' => 'Approve/Override Risk', 'slug' => 'crms.risk.approve', 'system' => 'crms'],
        ];

        foreach ($permissions as $permission) {
            \App\Models\Permission::updateOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        // --- Role Assignment Logic ---

        // 1. IT Admin / Super Admin (Auth Management)
        $authAdminRoles = \App\Models\Role::whereIn('name', ['Super Admin', 'IT Admin'])->get();
        $authPermissions = \App\Models\Permission::where('system', 'auth')->get();
        foreach ($authAdminRoles as $role) {
            $role->permissions()->syncWithoutDetaching($authPermissions->pluck('id'));
        }

        // 2. CRMS Admin
        $crmsAdmin = \App\Models\Role::where('name', 'Admin')->first();
        if ($crmsAdmin) {
            $adminPerms = \App\Models\Permission::whereIn('slug', [
                'crms.roles.manage', 
                'crms.templates.manage',
                'manage-users' // Allow Admin to manage users within their department
            ])->get();
            $crmsAdmin->permissions()->syncWithoutDetaching($adminPerms->pluck('id'));
        }

        // 3. CRMS Manager
        $crmsManager = \App\Models\Role::where('name', 'Manager')->first();
        if ($crmsManager) {
            $managerPerms = \App\Models\Permission::whereIn('slug', [
                'crms.templates.use',
                'crms.ocr.upload',
                'crms.ocr.process',
                'crms.ocr.review',
                'crms.contracts.generate',
                'crms.risk.assess',
                'crms.risk.view',
                'crms.risk.approve'
            ])->get();
            $crmsManager->permissions()->syncWithoutDetaching($managerPerms->pluck('id'));
        }

        // 4. CRMS Sales
        $crmsSales = \App\Models\Role::where('name', 'Sales')->first();
        if ($crmsSales) {
            $salesPerms = \App\Models\Permission::whereIn('slug', [
                'crms.templates.use',
                'crms.ocr.upload',
                'crms.ocr.process',
                'crms.ocr.review',
                'crms.contracts.generate',
                'crms.risk.assess',
                'crms.risk.view',
                'crms.risk.approve'
            ])->get();
            $crmsSales->permissions()->syncWithoutDetaching($salesPerms->pluck('id'));
        }
    }
}
