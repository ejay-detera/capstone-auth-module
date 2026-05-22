<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Optimize reverse lookups on the role_permission pivot table
        Schema::table('role_permission', function (Blueprint $table) {
            $table->index('permission_id', 'role_permission_permission_id_index');
        });

        // 2. Optimize user filtering by role and department
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->index('role_id', 'user_profiles_role_id_index');
            $table->index('department_id', 'user_profiles_department_id_index');
            
            // Index name columns for prefix search (e.g., "John%")
            $table->index('first_name', 'user_profiles_first_name_index');
            $table->index('last_name', 'user_profiles_last_name_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('role_permission', function (Blueprint $table) {
            $table->dropIndex('role_permission_permission_id_index');
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropIndex('user_profiles_role_id_index');
            $table->dropIndex('user_profiles_department_id_index');
            $table->dropIndex('user_profiles_first_name_index');
            $table->dropIndex('user_profiles_last_name_index');
        });
    }
};
