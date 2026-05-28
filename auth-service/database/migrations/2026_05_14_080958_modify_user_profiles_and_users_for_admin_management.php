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
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null');
        });

        // Insert permission for user management
        DB::table('permissions')->insertOrIgnore([
            'name' => 'Manage Users',
            'slug' => 'manage-users',
            'description' => 'Can create, view, and manage system users.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Link to IT Admin role
        $role = DB::table('roles')->where('name', 'IT Admin')->first();
        $permission = DB::table('permissions')->where('slug', 'manage-users')->first();
        
        if ($role && $permission) {
            DB::table('role_permission')->insertOrIgnore([
                'role_id' => $role->id,
                'permission_id' => $permission->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropForeign(['department_id']);
            $table->dropColumn(['role_id', 'department_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null');
        });
        
        DB::table('permissions')->where('slug', 'manage-users')->delete();
    }
};
