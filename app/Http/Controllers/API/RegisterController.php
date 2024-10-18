<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class RegisterController extends BaseController
{

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request): JsonResponse
    {

        $validator = Validator::make($request->all(), [
            "name" => "required",
            "email" => "required|email",
            "password" => "required",
            "c_password" => "required|same:password",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error.',
                'errors' => $validator->errors(),
                'status' => 422
            ], 422); // HTTP 422 Unprocessable Entity
        }

        $input = $request->all();

        $input["password"] = bcrypt($input["password"]);

        $user = User::create($input);

        $user->sendEmailVerificationNotification();

        $success["token"] = $user->createToken("auth_token")->accessToken;
        $success["email"] = $user->email;

        return response()->json([
            'success' => true,
            'data' => $success,
            'message' => 'User registered successfully. Please verify your email.',
            'status' => 201
        ], 201); // HTTP 201 Created
    }

    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request): JsonResponse
    {
        if (Auth::attempt([
            "email" => $request->email,
            "password" => $request->password,
        ])) {

            $user = Auth::user();

            $success["token"] = $user->createToken("auth_token")->accessToken;
            $success["first_name"] = $user->first_name;
            $success["last_name"] = $user->last_name;
            $success["email"] = $user->email;
            $success["role"] = $user->role;
            $success["user_id"] = $user->id;

            return response()->json([
                'success' => true,
                'data' => $success,
                'message' => 'User login successfully.',
                'status' => 200
            ], 200); // HTTP 200 OK

        } else {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised',
                'error' => 'Unauthorised',
                'status' => 401
            ], 401); // HTTP 401 Unauthorized
        }
    }

    public function handleGoogleCallback()
    {
        try {
            
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Check if the user already exists in the database
            $user = User::where('email', $googleUser->email)->first();

            if ($user) {
                // User exists, log them in
                Auth::login($user);
            } else {
                // Create a new user if they don't exist
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'password' => bcrypt(Str::random(16)), // Assign a random password for security
                    'email_verified_at' => now(),
                ]);
            }

            // Generate the access token
            $success["token"] = $user->createToken("auth_token")->accessToken;
            $success["first_name"] = $user->first_name;
            $success["last_name"] = $user->last_name;
            $success["email"] = $user->email;
            $success["role"] = $user->role; // Assuming role is part of your User model
            $success["user_id"] = $user->id;

            return response()->json([
                'success' => true,
                'data' => $success,
                'message' => 'User logged in successfully via Google.',
                'status' => 200
            ], 200); // HTTP 200 OK

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Google login failed',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500); // HTTP 500 Internal Server Error
        }
    }
}
