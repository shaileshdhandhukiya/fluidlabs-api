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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_name');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade'); // Customer relation
            $table->enum('status', ['not started', 'in progress', 'on hold', 'cancelled', 'finished'])->default('not started');
            $table->integer('progress')->default(0); // To track completion percentage
            $table->json('members'); // Array of members (user IDs)
            $table->integer('estimated_hours')->nullable();
            $table->date('start_date');
            $table->date('deadline')->nullable();
            $table->text('description')->nullable();
            $table->boolean('send_project_created_email')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
