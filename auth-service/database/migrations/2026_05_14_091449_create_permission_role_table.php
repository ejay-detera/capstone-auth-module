<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration is intentionally a no-op.
     * The role_permission pivot table (created in 2026_01_01_000004) is
     * the canonical pivot table used by Role and Permission models.
     * This migration previously created a duplicate 'permission_role' table
     * which caused confusion and data inconsistency.
     */
    public function up(): void
    {
        // No-op: role_permission table already handles this relationship
    }

    public function down(): void
    {
        // No-op
    }
};
