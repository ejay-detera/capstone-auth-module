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
        Schema::create('rate_limit_log', function (Blueprint $table) {
            $table->id();
            $table->string('key', 255);
            $table->integer('hits');
            $table->timestamp('window_start');
            
            $table->index(['key', 'window_start']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rate_limit_log');
    }
};
