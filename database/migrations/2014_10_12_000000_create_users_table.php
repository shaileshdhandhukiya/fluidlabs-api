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
        Schema::create('users', function (Blueprint $table) {
            $table->id();            
            // New fields
            $table->string('first_name'); // First name of the user
            $table->string('last_name');  // Last name of the user
            $table->string('profile_photo')->nullable();  // URL/path to profile photo
            $table->string('type')->nullable();  // User type (can define your own values)
            $table->string('phone')->nullable();  // Phone number
            $table->date('date_of_birth')->nullable();  // Date of birth
            $table->string('role')->default('user');  // Role (default is 'user')
            $table->string('designation')->nullable();  // Designation/Position
            $table->date('date_of_join')->nullable();  // Date of joining
            
            // Existing fields
            $table->string('email')->unique();  // Email
            $table->timestamp('email_verified_at')->nullable();  // Email verification timestamp
            $table->string('password');  // Password
            $table->rememberToken();  // Token for remembering login
            $table->timestamps();  // Timestamps for created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
