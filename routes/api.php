<?php

use Illuminate\Http\Request;
use App\Http\Controllers\API;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\PasswordResetController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

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

Route::get('auth/google', [RegisterController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [RegisterController::class, 'handleGoogleCallback']);

Route::middleware(['auth:api', 'verified'])->group(function () {

    // dashboard Analytics
    Route::get('dashboard/analytics', [API\DashboardController::class, 'index']);
    
    // projects routes
    Route::get('projects', [API\ProjectController::class, 'index']);               
    Route::post('projects', [API\ProjectController::class, 'store']);               
    Route::get('projects/{project}', [API\ProjectController::class, 'show']);       
    Route::put('projects/{project}', [API\ProjectController::class, 'update']);     
    Route::delete('projects/{project}', [API\ProjectController::class, 'destroy']); 

    Route::get('users/{user_id}/projects', [API\ProjectController::class, 'getUserProjectsWithTasks']);
    Route::get('customers_projects/{customer_id}', [API\CustomerController::class, 'getProjectsWithTasksByCustomer']);


    // task routes
    Route::get('tasks', [API\TaskController::class, 'index']);                
    Route::post('tasks', [API\TaskController::class, 'store']);               
    Route::get('tasks/{id}', [API\TaskController::class, 'show']);          
    Route::put('tasks/{id}', [API\TaskController::class, 'update']);        
    Route::delete('tasks/{id}', [API\TaskController::class, 'destroy']);

    // Sub-task routes
    Route::get('tasks/{id}/subtasks', [API\TaskController::class, 'getSubTasks']);
    Route::post('tasks/{id}/subtasks', [API\TaskController::class, 'createSubTask']);
    Route::get('tasks/{task_id}/subtasks/{subtask_id}', [API\TaskController::class, 'getSubTaskById']);
    Route::put('tasks/{task_id}/subtasks/{subtask_id}', [API\TaskController::class, 'updateSubTask']);
    Route::delete('tasks/{task_id}/subtasks/{subtask_id}', [API\TaskController::class, 'destroySubTask']);

    // comments routes
    Route::get('tasks/{task_id}/comments', [API\CommentController::class, 'index']);
    Route::post('tasks/{task_id}/comments', [API\CommentController::class, 'store']); 
    Route::get('comments/{id}', [API\CommentController::class, 'show']);     
    Route::put('comments/{id}', [API\CommentController::class, 'update']); 
    Route::delete('comments/{id}', [API\CommentController::class, 'destroy']);

    // user routes
    Route::get('users', [API\UserController::class, 'index']);                      
    Route::post('users', [API\UserController::class, 'store']);                    
    Route::get('users/{id}', [API\UserController::class, 'show']);                  
    Route::put('users/{id}', [API\UserController::class, 'update']);                
    Route::delete('users/{id}', [API\UserController::class, 'destroy']);   
    Route::get('users-create', [API\UserController::class, 'create']);          

    // roles routes
    Route::get('roles', [API\RoleController::class, 'index']);                      
    Route::post('roles', [API\RoleController::class, 'store']);                     
    Route::get('roles/{id}', [API\RoleController::class, 'show']);                  
    Route::put('roles/{id}', [API\RoleController::class, 'update']);                
    Route::delete('roles/{id}', [API\RoleController::class, 'destroy']);
    Route::get('permissions', [API\RoleController::class, 'create']);               

    // subscriptions routes
    Route::get('/subscriptions', [API\SubscriptionController::class, 'index']);
    Route::post('/subscriptions', [API\SubscriptionController::class, 'store']);
    Route::get('/subscriptions/{id}', [API\SubscriptionController::class, 'show']);
    Route::put('/subscriptions/{id}', [API\SubscriptionController::class, 'update']);
    Route::delete('/subscriptions/{id}', [API\SubscriptionController::class, 'destroy']);

    // customers routes
    Route::get('/customers', [API\CustomerController::class, 'index']);        
    Route::post('/customers', [API\CustomerController::class, 'store']);        
    Route::get('/customers/{id}', [API\CustomerController::class, 'show']);     
    Route::put('/customers/{id}', [API\CustomerController::class, 'update']);   
    Route::delete('/customers/{id}', [API\CustomerController::class, 'destroy']); 

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

    Route::post('/password/reset', [PasswordResetController::class, 'reset'])->name('password.update');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');

});
