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

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        $request->validate([
            'access_token' => 'required|string',
        ]);

        try {
            // Use the access token to get the user details from Google
            $googleUser = Socialite::driver('google')->stateless()->userFromToken($request->access_token);

            // Check if the user exists in the database
            $user = User::where('email', $googleUser->email)->first();

            if ($user) {

                if (is_null($user->email_verified_at)) {
                    $user->email_verified_at = now();
                    $user->save(); // Save the updated user
                }
                // Log them in
                Auth::login($user);
            } else {
                // Create a new user
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'password' => bcrypt(Str::random(16)), // Random password
                    'email_verified_at' => now(),
                ]);
            }

            // Generate the access token
            $success = [
                "token" => $user->createToken("auth_token")->accessToken,
                "first_name" => $user->first_name,
                "last_name" => $user->last_name,
                "email" => $user->email,
                "role" => $user->role,
                "user_id" => $user->id,
            ];

            return response()->json([
                'success' => true,
                'data' => $success,
                'message' => 'User logged in successfully via Google.',
                'status' => 200
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Google login failed',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }
}
