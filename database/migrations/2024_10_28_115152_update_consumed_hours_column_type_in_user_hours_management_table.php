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
        Schema::table('user_hours_management', function (Blueprint $table) {
            $table->string('consumed_hours', 5)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_hours_management', function (Blueprint $table) {
            $table->integer('consumed_hours')->change();
        });
    }
};
