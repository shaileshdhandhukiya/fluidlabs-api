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
        Schema::create('subtasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->string('subject');
            $table->date('start_date');
            $table->date('due_date')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent']);
            $table->unsignedBigInteger('project_id');
            $table->json('assignees')->nullable(); // Store assignees as JSON array
            $table->text('task_description')->nullable();
            $table->enum('status', ['not started', 'in progress', 'testing', 'awaiting feedback', 'completed'])->default('not started');
            $table->string('attach_file')->nullable(); // Store file path as a string
            $table->timestamps();

            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subtasks');
    }
};
