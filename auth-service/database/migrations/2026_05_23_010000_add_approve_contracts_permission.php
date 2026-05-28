<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ensure permission exists
        DB::table('permissions')->updateOrInsert(
            ['slug' => 'crms.contracts.approve'],
            [
                'name' => 'Approve Contracts',
                'system' => 'crms',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $permission = DB::table('permissions')->where('slug', 'crms.contracts.approve')->first();
        
        if ($permission) {
            $permissionId = $permission->id;

            // Get role IDs for Super Admin, Admin, Manager, Finance Manager
            $roles = DB::table('roles')->whereIn('name', ['Super Admin', 'Admin', 'Manager', 'Finance Manager'])->pluck('id');

            // Link permission to roles
            foreach ($roles as $roleId) {
                DB::table('role_permission')->insertOrIgnore([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permission = DB::table('permissions')->where('slug', 'crms.contracts.approve')->first();

        if ($permission) {
            DB::table('role_permission')->where('permission_id', $permission->id)->delete();
            DB::table('permissions')->where('id', $permission->id)->delete();
        }
    }
};
