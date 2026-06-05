<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Mail\AdminVerificationCodeMail;
use App\Mail\WelcomeAdminMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class GoogleController extends Controller
{
    public function getGoogleUrl()
    {
        // Hardcoding the base OAuth endpoints keeps them clean and avoids server-side string bugs
        $baseUrl = 'https://accounts.google.com/o/oauth2/v2/auth';

        $query = http_build_query([
            'client_id'     => config('services.google.client_id') ?? env('GOOGLE_CLIENT_ID'),
            'redirect_uri'  => env('GOOGLE_REDIRECT_URL'),
            'scope'         => 'openid profile email',
            'response_type' => 'code',
            'access_type'   => 'offline',
            'prompt'        => 'select_account',
        ]);

        return response()->json([
            'url' => $baseUrl . '?' . $query,
        ]);
    }

    public function handleGoogleCallback(Request $request)
    {
        $code = $request->input('code') ?? $request->query('code');

        if (!$code) {
            return response()->json(['status' => 'error', 'message' => 'Authorization code missing.'], 400);
        }

        try {
            // 1. Fetch tokens from Google
            $tokenResponse = Http::post('https://oauth2.googleapis.com/token', [
                'client_id'     => config('services.google.client_id') ?? env('GOOGLE_CLIENT_ID'),
                'client_secret' => config('services.google.client_secret') ?? env('GOOGLE_CLIENT_SECRET'),
                'redirect_uri'  => env('GOOGLE_REDIRECT_URL'),
                'grant_type'    => 'authorization_code',
                'code'          => $code,
            ]);

            if ($tokenResponse->failed()) {
                return response()->json(['status' => 'error', 'message' => 'OAuth handshake failed.'], 400);
            }

            $googleUser = Http::withToken($tokenResponse->json()['access_token'])
                ->get('https://www.googleapis.com/oauth2/v3/userinfo')->json();

            // 2. Find or Create the Admin Account
            $admin = Admin::updateOrCreate(
                ['email' => $googleUser['email']],
                [
                    'full_name' => $googleUser['name'],
                    'google_id' => $googleUser['sub'],
                    'password'  => $admin->password ?? Hash::make(Str::random(24)),
                ]
            );

            // 3. Generate a 6-Digit Secure Verification Code
            $verificationCode = random_int(100000, 990000);
            
            $admin->update([
                'verification_code' => $verificationCode,
                'code_expires_at'   => now()->addMinutes(10), // Expire in 10 minutes
            ]);

            // 4. Send Code via your live Gmail server
            Mail::to($admin->email)->send(new AdminVerificationCodeMail($admin->full_name, $verificationCode));

            // 5. DO NOT send the Token yet. Return a step confirmation instead.
            return response()->json([
                'status' => 'verification_required',
                'message' => 'A 6-digit verification code has been dispatched to your email address.',
                'email' => $admin->email
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code'  => 'required|numeric',
        ]);

        // 🌟 FIX: Pass the conditions as an associative array to prevent driver argument conflicts
        $admin = Admin::firstWhere(['email' => $request->email])->first();

        // 1. Defend against null values if the account doesn't exist
        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Admin account record not found.'
            ], 404);
        }

        // 2. Safely check the code
        if ((int)$admin->verification_code !== (int)$request->code) {
            return response()->json([
                'status' => 'error',
                'message' => 'The verification code provided is incorrect.'
            ], 401);
        }

        if (now()->isAfter($admin->code_expires_at)) {
            return response()->json([
                'status' => 'error',
                'message' => 'This verification code has expired. Please request a new one.'
            ], 401);
        }

        // 🌟 FIX: Use direct attributes to clear the data safely, bypassing mass-assignment rules
        $admin->verification_code = null;
        $admin->code_expires_at = null;
        $admin->save();

        // Issue their final access token securely
        $token = $admin->createToken('admin_auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Authentication complete.',
            'user' => $admin,
            'token' => $token
        ]);
    }
}