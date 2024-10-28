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
        Schema::table('task_timers', function (Blueprint $table) {
            $table->string('total_hours', 10)->change();  // Update to VARCHAR(10)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_timers', function (Blueprint $table) {
            $table->integer('total_hours')->change();  // Rollback to original type if needed
        });
    }
};
