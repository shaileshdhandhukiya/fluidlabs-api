<?php

use Illuminate\Http\Request;
use App\Http\Controllers\API;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\PasswordResetController;
use Illuminate\Support\Facades\Auth;

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

Route::get('auth/google', [API\AuthController::class, 'redirectToGoogle']);
Route::post('auth/google/callback', [API\AuthController::class, 'handleGoogleCallback']);

//password reset
Route::post('password-forgot', [PasswordResetController::class, 'sendResetLinkEmail']);
Route::post('password-reset', [PasswordResetController::class, 'passwordReset']);

Route::middleware(['auth:api', 'verified'])->group(function () {

    // hours management
    Route::post('set-totalhours', [API\UserHoursController::class, 'setTotalHours']);
    Route::get('allusershours', [API\UserHoursController::class, 'getAllUsersHours']);
    Route::get('hours-management/user/{userId}', [API\UserHoursController::class, 'getUserHours']);

    // timer
    Route::get('task-timer/status/{taskId}', [API\TaskTimerController::class, 'isTaskTimerRunning']);  // status check
    Route::post('task-timer/start', [API\TaskTimerController::class, 'startTimer']);  // Start timer for a task
    Route::post('task-timer/stop/{id}', [API\TaskTimerController::class, 'stopTimer']);   // Stop timer for a task
    Route::get('task-timer/total-hours', [API\TaskTimerController::class, 'getAllTotalHours']);  // Get total hours for all assignees
    Route::get('task-timer/{id}', [API\TaskTimerController::class, 'getTaskTimer']);   // Get task timer details by ID
    Route::post('task-timer/update-timer/{id?}', [API\TaskTimerController::class, 'updateOrCreateTimerManually']);

    // dashboard Analytics
    Route::get('dashboard/analytics', [API\DashboardController::class, 'index']);

    // maintenances 
    Route::apiResource('maintenances', API\MaintenanceController::class);

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
    Route::get('users-tasks/{user_id}', [API\TaskController::class, 'getTasksByAssignee']);       

    // Sub-task routes
    Route::get('tasks/{task_id}/subtasks', [API\SubTaskController::class, 'index']);
    Route::post('tasks/{task_id}/subtasks', [API\SubTaskController::class, 'store']);
    Route::get('tasks/{task_id}/subtasks/{subtask_id}', [API\SubTaskController::class, 'show']);
    Route::put('tasks/{task_id}/subtasks/{subtask_id}', [API\SubTaskController::class, 'update']);
    Route::delete('tasks/{task_id}/subtasks/{subtask_id}', [API\SubTaskController::class, 'destroy']);

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
    Route::post('users/{id}', [API\UserController::class, 'update']);                
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
    Route::get('subscriptions', [API\SubscriptionController::class, 'index']);
    Route::post('subscriptions', [API\SubscriptionController::class, 'store']);
    Route::get('subscriptions/{id}', [API\SubscriptionController::class, 'show']);
    Route::put('subscriptions/{id}', [API\SubscriptionController::class, 'update']);
    Route::delete('subscriptions/{id}', [API\SubscriptionController::class, 'destroy']);

    // customers routes
    Route::get('customers', [API\CustomerController::class, 'index']);        
    Route::post('customers', [API\CustomerController::class, 'store']);        
    Route::get('customers/{id}', [API\CustomerController::class, 'show']);     
    Route::put('customers/{id}', [API\CustomerController::class, 'update']);   
    Route::delete('customers/{id}', [API\CustomerController::class, 'destroy']); 

    //email verification
    Route::post('send-otp', [API\AuthController::class, 'sendOtp']);
    Route::post('verify-otp', [API\AuthController::class, 'verifyOtp']);    

    
});
