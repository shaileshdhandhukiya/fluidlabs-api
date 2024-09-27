<?php

use Illuminate\Http\Request;
use App\Http\Controllers\API;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\PasswordResetController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\RoleController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('register', [RegisterController::class, 'register'])->name('register');
Route::post('login', [RegisterController::class, 'login'])->name('login');


Route::middleware(['auth:api', 'verified'])->group(function () {

    Route::resource('products', ProductController::class);
    // Protected routes
    Route::post('/password/reset', [PasswordResetController::class, 'reset'])->name('password.update');    

    Route::get('projects', [API\ProjectController::class, 'index']);                // List all projects
    Route::post('projects', [API\ProjectController::class, 'store']);               // Create a new project
    Route::get('projects/{project}', [API\ProjectController::class, 'show']);       // Show a specific project
    Route::put('projects/{project}', [API\ProjectController::class, 'update']);     // Update an existing project
    Route::delete('projects/{project}', [API\ProjectController::class, 'destroy']); // Delete a project

    Route::get('users', [API\UserController::class, 'index']);                      // List all users
    Route::post('users', [API\UserController::class, 'store']);                     // Create a new user
    Route::get('users/{id}', [API\UserController::class, 'show']);                  // Show a specific user
    Route::put('users/{id}', [API\UserController::class, 'update']);                // Update a user
    Route::delete('users/{id}', [API\UserController::class, 'destroy']);            // Delete a user

    Route::get('roles', [API\RoleController::class, 'index']);                      // List all roles
    Route::post('roles', [API\RoleController::class, 'store']);                     // Create a new role
    Route::get('roles/{id}', [API\RoleController::class, 'show']);                  // Show a specific role
    Route::put('roles/{id}', [API\RoleController::class, 'update']);                // Update a role
    Route::delete('roles/{id}', [API\RoleController::class, 'destroy']);            // Delete a role

    Route::post('/email/resend', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return response()->json(['message' => 'Verification email resent']);
    });

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return response()->json(['message' => 'Email verified successfully']);
    })->middleware(['auth:api', 'signed'])->name('verification.verify');

    Route::get('/password/reset/{token}', function ($token) {  
        return response()->json(['token' => $token]);
    })->name('password.reset');
    
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');    
});

