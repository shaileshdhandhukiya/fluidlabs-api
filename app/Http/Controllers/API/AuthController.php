<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Mail\OtpMail;
use Illuminate\Http\JsonResponse;
use Laravel\Socialite\Facades\Socialite;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use GuzzleHttp\Client;
use Firebase\JWT\Key;

class AuthController extends BaseController
{

    const ALLOWED_ALGOS = ['RS256'];
    
    /**
     * Send OTP for email verification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendOtp(Request $request): JsonResponse
    {
        // Validate email
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Find user by email
        $user = User::where('email', $request->email)->first();

        // Generate a 6-digit OTP and set expiration time
        $otpCode = random_int(100000, 999999);
        $user->otp_code = $otpCode;
        $user->otp_expires_at = now()->addMinutes(10);
        $user->save();

        // Send OTP via email
        Mail::to($user->email)->send(new OtpMail($user, $otpCode));

        return response()->json([
            'success' => true,
            'message' => 'OTP has been sent to your email.',
        ]);
    }

    /**
     * Verify OTP for email verification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        // Validate email and OTP input
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp_code' => 'required|numeric|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Find user by email
        $user = User::where('email', $request->email)->first();

        // Check if the OTP is valid and not expired
        if (!$user || $user->otp_code !== $request->otp_code || $user->otp_expires_at < now()) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired OTP.'], 400);
        }

        // Verify the user's email and clear OTP fields
        $user->email_verified_at = now();
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully.',
        ]);
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

        $accessToken = $request->input('access_token');

        try {
            // Step 1: Get Google User
            $googleUser = $this->getGoogleUser($accessToken);

            // Step 2: Find or create user in the database
            $user = User::where('email', $googleUser->email)->first();

            if (is_null($user->email_verified_at)) {
                $user->email_verified_at = now();
                $user->save();
            }

            // Step 3: Generate Passport Token
            $tokenResult = $user->createToken("auth_token");

            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $tokenResult->accessToken,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'user_id' => $user->id,
                    'role' => $user->getRoleNames()->toArray(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ],
                'message' => 'User login successfully.',
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

    private function getGoogleUser($accessToken)
    {
        if (substr_count($accessToken, '.') === 2) {
            // ID Token (JWT)
            return $this->verifyIdToken($accessToken);
        } else {
            // OAuth Access Token
            return Socialite::driver('google')->stateless()->userFromToken($accessToken);
        }
    }

    private function verifyIdToken($idToken)
    {
        try {
            $client = new Client();
            $response = $client->get('https://www.googleapis.com/oauth2/v3/certs');
            $keys = json_decode($response->getBody()->getContents(), true);

            $publicKeys = JWK::parseKeySet($keys);

            $decoded = JWT::decode($idToken, $publicKeys);

            return (object) [
                'name' => $decoded->name ?? null,
                'email' => $decoded->email ?? null,
                'email_verified' => $decoded->email_verified ?? false,
            ];
        } catch (\Exception $e) {
            throw new \Exception('Failed to verify ID token: ' . $e->getMessage());
        }
    }
    

}
