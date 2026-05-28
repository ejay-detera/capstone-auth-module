<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $row) {
            $row->string('system')->default('common')->after('name');
            $row->index('system');
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $row) {
            $row->dropColumn('system');
        });
    }
};
