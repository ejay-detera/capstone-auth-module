<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use App\Models\Permission;
use App\Models\UserProfile;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create Finance Manager and Finance Employee roles
        $financeManager = Role::firstOrCreate(
            ['name' => 'Finance Manager'],
            ['description' => 'Finance department manager']
        );

        $financeEmployee = Role::firstOrCreate(
            ['name' => 'Finance Employee'],
            ['description' => 'Finance department employee']
        );

        // 2. Sync default permissions to Finance Manager (matching standard Manager)
        $managerPerms = Permission::whereIn('slug', [
            'crms.templates.use',
            'crms.ocr.upload',
            'crms.ocr.process',
            'crms.ocr.review',
            'crms.contracts.generate',
            'crms.risk.assess',
            'crms.risk.view',
            'crms.risk.approve',
            'crms.contracts.view',
            'crms.contracts.create',
            'crms.contracts.edit',
            'crms.users.view',
            'crms.partners.view',
            'crms.partners.create',
            'crms.partners.edit',
        ])->pluck('id');
        $financeManager->permissions()->syncWithoutDetaching($managerPerms);

        // 3. Migrate existing Finance users who have Manager/Employee roles to the new department-specific roles
        $financeDept = DB::table('departments')->where('name', 'Finance')->first();
        if ($financeDept) {
            $managerRole = DB::table('roles')->where('name', 'Manager')->first();
            $employeeRole = DB::table('roles')->where('name', 'Employee')->first();

            if ($managerRole) {
                UserProfile::where('department_id', $financeDept->id)
                    ->where('role_id', $managerRole->id)
                    ->update(['role_id' => $financeManager->id]);
            }

            if ($employeeRole) {
                UserProfile::where('department_id', $financeDept->id)
                    ->where('role_id', $employeeRole->id)
                    ->update(['role_id' => $financeEmployee->id]);
            }
        }
    }

    public function down(): void
    {
        // No down migration is necessary for this custom logic in this context
    }
};
