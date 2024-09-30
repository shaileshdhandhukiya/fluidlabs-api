<?php

use Illuminate\Http\Request;
use App\Http\Controllers\API;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\PasswordResetController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\API\ProductController;
// use App\Http\Controllers\API\RoleController;
// use App\Http\Controllers\Api\SubscriptionController;


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
   

    Route::get('projects', [API\ProjectController::class, 'index']);               
    Route::post('projects', [API\ProjectController::class, 'store']);               
    Route::get('projects/{project}', [API\ProjectController::class, 'show']);       
    Route::put('projects/{project}', [API\ProjectController::class, 'update']);     
    Route::delete('projects/{project}', [API\ProjectController::class, 'destroy']); 

    Route::get('users', [API\UserController::class, 'index']);                      
    Route::post('users', [API\UserController::class, 'store']);                     
    Route::get('users/{id}', [API\UserController::class, 'show']);                  
    Route::put('users/{id}', [API\UserController::class, 'update']);                
    Route::delete('users/{id}', [API\UserController::class, 'destroy']);            

    Route::get('roles', [API\RoleController::class, 'index']);                      
    Route::post('roles', [API\RoleController::class, 'store']);                     
    Route::get('roles/{id}', [API\RoleController::class, 'show']);                  
    Route::put('roles/{id}', [API\RoleController::class, 'update']);                
    Route::delete('roles/{id}', [API\RoleController::class, 'destroy']);            

    Route::get('/subscriptions', [API\SubscriptionController::class, 'index']);
    Route::post('/subscriptions', [API\SubscriptionController::class, 'store']);
    Route::get('/subscriptions/{id}', [API\SubscriptionController::class, 'show']);
    Route::put('/subscriptions/{id}', [API\SubscriptionController::class, 'update']);
    Route::delete('/subscriptions/{id}', [API\SubscriptionController::class, 'destroy']);

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

