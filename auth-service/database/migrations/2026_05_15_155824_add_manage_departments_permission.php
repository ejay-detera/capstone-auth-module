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
            ['slug' => 'manage-departments'],
            [
                'name' => 'Manage Departments',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $permission = DB::table('permissions')->where('slug', 'manage-departments')->first();
        
        if ($permission) {
            $permissionId = $permission->id;

            // Get Admin roles
            $adminRoles = DB::table('roles')->whereIn('name', ['Super Admin', 'IT Admin'])->pluck('id');

            // Link permission to roles
            foreach ($adminRoles as $roleId) {
                DB::table('role_permission')->insertOrIgnore([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permission = DB::table('permissions')->where('slug', 'manage-departments')->first();

        if ($permission) {
            DB::table('role_permission')->where('permission_id', $permission->id)->delete();
            DB::table('permissions')->where('id', $permission->id)->delete();
        }
    }
};
