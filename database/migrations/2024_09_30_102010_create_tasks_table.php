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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->date('start_date');
            $table->date('due_date')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('low');
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade'); // Project relation
            $table->json('assignees'); // Array of assigned user IDs
            $table->text('task_description')->nullable();
            $table->enum('status', ['not started', 'in progress', 'testing', 'awaiting feedback', 'completed'])->default('not started');
            $table->string('attach_file')->nullable(); // File path for attachments
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
