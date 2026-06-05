<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\AdminVerificationCodeMail;
use App\Mail\VerificationMail;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $admins = Admin::all();

        return response()->json([
            "message" => "Get all admins successful!",
            "data" => $admins
        ],200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = validator($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:admin',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
        ])->validate();

        $existingIds = DB::table('admin')->pluck('admin_id')->toArray();
        
        $nextId = 1;
        while (in_array($nextId, $existingIds)) {
            $nextId++;
        }

        // $verificationCode = random_int(100000, 999999);

        // $admin = Admin::create([
        //     'admin_id'          => $nextId,
        //     'full_name'         => $validatedData['full_name'],
        //     'email'             => $validatedData['email'],
        //     'password'          => Hash::make($validatedData['password']),
        //     'phone'             => $validatedData['phone'] ?? null,
        //     'verification_code' => $verificationCode,
        // ]);

        // Mail::to($admin->email)->send(new AdminVerificationCodeMail($admin->full_name, $verificationCode));

        $admin = Admin::create(array_merge($validatedData, [
            'admin_id' => $nextId
        ]));

        return response()->json([
            "message" => "Registration successful!",
            "data" => [
                "admin_id" => $admin->admin_id,
                "email" => $admin->email,
            ]
        ], 201);
    }

    public function login(Request $request)
    {
        // $credentials = $request->validate([
        //     'email' => 'required|email',
        //     'password' => 'required|string',
        // ]);

        // if (!Auth::guard('admin')->attempt($credentials)) {
        //     return response()->json([
        //         "message" => "Invalid email or password!"
        //     ],401);
        // }

        // $admin = Auth::guard('admin')->user();
        // $token = $admin->createToken('auth_token',['*'],now()->addHours(24))->plainTextToken;

        // return response()->json([
        //     "message" => "Login successful!",
        //     "access_token" => $token,
        //     "token_type" => "Bearer",
        // ],200);

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // 1. Find the admin by email
        $admin = Admin::firstWhere('email', $credentials['email'])->first();

        // 2. Check password
        if (!$admin || !Hash::check($credentials['password'], $admin->password)) {
            return response()->json([
                "message" => "Invalid email or password!"
            ], 401);
        }

        // 3. Generate a fresh 6-Digit Code for login & set 10 minutes expiry
        $verificationCode = random_int(100000, 999999);
        
        $admin->update([
            'verification_code' => $verificationCode,
            'code_expires_at'   => now()->addMinutes(10), 
        ]);

        // 4. Send the code via your working Mail class (Name first, Code second)
        Mail::to($admin->email)->send(new AdminVerificationCodeMail($admin->full_name, $verificationCode));

        // 5. Prompt the frontend to collect the 6-digit code
        return response()->json([
            "status" => "verification_required",
            "message" => "A login verification code has been sent to your email address.",
            "email" => $admin->email
        ], 200);
    }

    public function verifyLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code'  => 'required|numeric',
        ]);

        $admin = Admin::firstWhere('email', $request->email)->first();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Admin account record not found.'
            ], 404);
        }

        // Verify code matches
        if ((int)$admin->verification_code !== (int)$request->code) {
            return response()->json([
                'status' => 'error',
                'message' => 'The verification code provided is incorrect.'
            ], 401);
        }

        // Check if code expired
        if ($admin->code_expires_at && now()->isAfter($admin->code_expires_at)) {
            return response()->json([
                'status' => 'error',
                'message' => 'This verification code has expired. Please log in again.'
            ], 401);
        }

        // Clear security code out upon successful entry
        $admin->verification_code = null;
        $admin->code_expires_at = null;
        $admin->save();

        // Issue Sanctum Bearer Token
        $token = $admin->createToken('auth_token', ['*'], now()->addHours(24))->plainTextToken;

        return response()->json([
            "message" => "Login successful!",
            "access_token" => $token,
            "token_type" => "Bearer",
            "user" => $admin
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            "message" => "Logout successful!"
        ],200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $admin = Admin::findOrFail($id);

        if (!$admin) {
            return response()->json([
                "message" => "Admin not found!"
            ],404);
        }

        return response()->json([
            "message" => "Get admin successful!",
            "data" => $admin
        ],200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $loggedInAdmin = Auth::user(); 

        if ((string) $loggedInAdmin->admin_id !== $id) {
            return response()->json([
                "message" => "Forbidden. You can only update your own profile!"
            ], 403);
        }

        $validatedData = validator($request->all(), [
            'full_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:admin,email,' . $loggedInAdmin->admin_id . ',admin_id',
            'password' => 'sometimes|string|min:8',
            'phone' => 'nullable|string|max:20',
        ])->validate();

        $loggedInAdmin->fill($validatedData);
        $loggedInAdmin->save();

        return response()->json([
            "message" => "Your profile updated successfully!",
            "data" => $loggedInAdmin
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(Request $request, string $id)
    // {
    //     $loggedInAdmin = Auth::user(); 

    //     if ((string) $loggedInAdmin->admin_id !== $id) {
    //         return response()->json([
    //             "message" => "Forbidden. You can only delete your own profile!"
    //         ], 403);
    //     }

    //     $request->validate([
    //         'password' => 'required|string',
    //     ]);

    //     if (!Hash::check($request->password, $loggedInAdmin->password)) {
    //         return response()->json([
    //             "message" => "Incorrect password. Account deletion canceled."
    //         ], 422);
    //     }

    //     $loggedInAdmin->tokens()->delete();
    //     Admin::destroy($loggedInAdmin->admin_id);

    //     return response()->json([
    //         "message" => "Your account has been deleted successfully."
    //     ], 200);
    // }

    public function destroy(Request $request, string $id)
    {
        // 1. Fetch the user and cast it cleanly to your explicit Admin model type
        $user = Auth::user();
        if (!$user) {
            return response()->json(["message" => "Unauthenticated."], 401);
        }
        
        $loggedInAdmin = Admin::findOrFail($user->admin_id);

        // 2. Authorization check: Ensure they can only delete their own profile
        if (!$loggedInAdmin || (string) $loggedInAdmin->admin_id !== $id) {
            return response()->json([
                "message" => "Forbidden. You can only delete your own profile!"
            ], 403);
        }

        // 3. Security Check (Bypass password if Google User)
        if (!empty($loggedInAdmin->google_id)) {
            $request->validate([
                'verification_code' => 'required|string|size:6',
            ]);

            // Check if the code matches
            if ($request->verification_code !== $loggedInAdmin->verification_code) {
                return response()->json(["message" => "Incorrect verification code."], 422);
            }

            // Check if the code has expired using the column in your model
            if (now()->greaterThan($loggedInAdmin->code_expires_at)) {
                return response()->json(["message" => "Verification code has expired. Please request a new one."], 422);
            }
        } else {
            $request->validate([
                'password' => 'required|string',
            ]);

            if (!Hash::check($request->password, $loggedInAdmin->password)) {
                return response()->json([
                    "message" => "Incorrect password. Account deletion canceled."
                ], 422);
            }
        }

        // 4. Clear everything up in the exact order MySQL expects (Bottom-to-Top)
        $loggedInAdmin->tokens()->delete();
        Admin::destroy($loggedInAdmin->admin_id);


        return response()->json([
            "message" => "Your account and all associated property data have been permanently deleted."
        ], 200);
    }

    public function sendDeletionCode(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(["message" => "Unauthenticated."], 401);
        }

        // Fetch a fresh instance to ensure we can modify it
        $admin = Admin::findOrFail($user->admin_id);

        // Security check: Only generate codes for Google Auth users
        if (empty($admin->google_id)) {
            return response()->json([
                "message" => "Action not allowed. Standard accounts must use their password to delete profiles."
            ], 403);
        }

        // 🔥 Cooldown Check: Prevent generating a new code if one was sent less than 1 minute ago
        // (Since expiry is 10 mins, if more than 9 mins remain, it means it's been less than 60 seconds)
        if ($admin->verification_code && $admin->code_expires_at && now()->addMinutes(9)->greaterThan($admin->code_expires_at)) {
            return response()->json([
                "message" => "Please wait at least 1 minute before requesting a new verification code."
            ], 429);
        }

        // 1. Generate a secure random 6-digit numeric string
        $code = (string) rand(100000, 999999);

        // 2. Save the code and its expiration time (valid for 10 minutes) to the database
        $admin->update([
            'verification_code' => $code,
            'code_expires_at'   => now()->addMinutes(10),
        ]);

        // 3. Send the email containing the code to the admin
        Mail::to($admin->email)->send(new AdminVerificationCodeMail($admin->full_name, $code));

        // For testing purposes right now, we return the code in the JSON response
        return response()->json([
            "message" => "A 6-digit verification code has been sent to your registered email address.",
            "debug_testing_code" => $code // Remember to remove this line before deploying to production!
        ], 200);
    }
}
