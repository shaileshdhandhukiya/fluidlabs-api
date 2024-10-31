<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Mail\OtpMail;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
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
        Mail::to($user->email)->send(new OtpMail($user,$otpCode));

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
}
