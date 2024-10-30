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
            $table->string('total_hours', 10)->change(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_hours_management', function (Blueprint $table) {
            $table->string('total_hours', 10)->change(); 
        });
    }
};
