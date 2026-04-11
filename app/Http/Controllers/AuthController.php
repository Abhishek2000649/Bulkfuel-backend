<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Models\Otp;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        try {


            if (!$request->all()) {
                return response()->json([
                    'status' => false,
                    'code'   => 400,
                    'message' => 'Bad Request - No data received'
                ], 400);
            }


            $validator = Validator::make($request->all(), [
                'name'     => 'required|min:3',
                'email'    => 'required|email',
                'password' => 'required|min:6'
            ]);


            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'code'    => 422,
                    'message' => 'Validation Error',
                    'errors'  => $validator->errors()
                ], 422);
            }


            if (User::where('email', $request->email)->exists()) {
                return response()->json([
                    'status'  => false,
                    'code'    => 409,
                    'message' => 'Email already registered'
                ], 409);
            }


            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'role'     => 'USER'
            ]);


            return response()->json([
                'status'  => true,
                'code'    => 201,
                'message' => 'User Registered Successfully',
                'data'    => $user
            ], 201);
        } catch (Exception $e) {


            return response()->json([
                'status'  => false,
                'code'    => 500,
                'message' => 'Internal Server Error',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function sendOtp(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'name'  => 'required|min:3',
                'email' => 'required|email'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $email = strtolower($request->email);

            // Check existing user
            if (User::where('email', $email)->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email already registered'
                ], 409);
            }

            // Generate OTP
            // $otp = rand(100000, 999999);
            $otp = 123456;

            // Save OTP (replace old)
            Otp::updateOrCreate(
                ['email' => $email],
                [
                    'otp' => Hash::make($otp),
                    'expires_at' => now()->addMinutes(5)
                ]
            );

            // Send email
            // Mail::raw("Your OTP is: $otp", function ($message) use ($email) {
            //     $message->to($email)->subject('OTP Verification');
            // });

            return response()->json([
                'status' => true,
                'message' => 'OTP sent successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error sending OTP'
            ], 500);
        }
    }

    public function verifyOtp(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'name'     => 'required|min:3',
                'email'    => 'required|email',
                'otp'      => 'required',
                'password' => 'required|min:6'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $email = strtolower($request->email);

            $otpRecord = Otp::where('email', $email)->first();

            // OTP not found
            if (!$otpRecord) {
                return response()->json([
                    'status' => false,
                    'message' => 'OTP not found'
                ], 400);
            }

            // Expired OTP
            if ($otpRecord->isExpired()) {
                $otpRecord->delete();

                return response()->json([
                    'status' => false,
                    'message' => 'OTP expired'
                ], 400);
            }

            // Verify OTP
            if (!Hash::check($request->otp, $otpRecord->otp)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid OTP'
                ], 400);
            }

            // Check duplicate user
            if (User::where('email', $email)->exists()) {
                $otpRecord->delete();

                return response()->json([
                    'status' => false,
                    'message' => 'User already exists'
                ], 409);
            }

            // Create user
            $user = User::create([
                'name'     => $request->name,
                'email'    => $email,
                'password' => Hash::make($request->password),
                'role'     => 'USER'
            ]);

            // Delete OTP after success
            $otpRecord->delete();
            $token = $user->createToken('angular-token')->plainTextToken;

            return response()->json([
                'status' => true,
                'message' => 'Account created successfully',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error verifying OTP'
            ], 500);
        }
    }
    public function doLogin(Request $request)
    {
        try {


            $validator = Validator::make($request->all(), [
                'email'    => 'required|email',
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'code'    => 422,
                    'message' => 'Validation error',
                    'errors'  => $validator->errors()
                ], 422);
            }


            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'code'    => 404,
                    'message' => 'User not found'
                ], 404);
            }


            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'code'    => 401,
                    'message' => 'Invalid email or password'
                ], 401);
            }


            if ($user->status === 'inactive') {
                return response()->json([
                    'success' => false,
                    'code'    => 403,
                    'message' => 'Account is inactive. Please contact support.'
                ], 403);
            }


            if ($user->login_attempts >= 5) {
                return response()->json([
                    'success' => false,
                    'code'    => 429,
                    'message' => 'Too many login attempts. Please try again later.'
                ], 429);
            }


            $token = $user->createToken('angular-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'code'    => 200,
                'message' => 'Login successful',
                'data'    => [
                    'token' => $token,
                    'user'  => [
                        'id'    => $user->id,
                        'name'  => $user->name,
                        'email' => $user->email,
                        'role'  => $user->role,
                    ]
                ]
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'code'    => 500,
                'message' => 'Internal server error',
                'error'   => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }




    public function logout(Request $request)
    {
        try {


            if (!$request->user()) {
                return response()->json([
                    'success' => false,
                    'code'    => 401,
                    'message' => 'Unauthorized. Please login first.'
                ], 401);
            }


            if ($request->user()->status === 'inactive') {
                return response()->json([
                    'success' => false,
                    'code'    => 403,
                    'message' => 'Account is inactive. Logout not allowed.'
                ], 403);
            }


            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'code'    => 200,
                'message' => 'Logged out successfully'
            ], 200);
        } catch (\Throwable $e) {


            return response()->json([
                'success' => false,
                'code'    => 500,
                'message' => 'Internal server error',
                'error'   => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    // public function me(Request $request)
    // {
    //     return response()->json([
    //         'user' => $request->user()->load('address')
    //     ]);
    // }
    public function me(Request $request)
{
    $user = $request->user()->load('address');

    // 🔥 Add full image URL
    if ($user->profile_image) {
        $user->profile_image_url = asset($user->profile_image);
    } else {
        $user->profile_image_url = null;
    }

    return response()->json([
        'user' => $user
    ]);
}

    public function forgotPassword(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'email' => 'required|email'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $email = strtolower($request->email);

            // ✅ Check user exists
            $user = User::where('email', $email)->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Generate OTP
            // $otp = rand(100000, 999999);
            $otp = 123456;

            // Save OTP
            Otp::updateOrCreate(
                ['email' => $email],
                [
                    'otp' => Hash::make($otp),
                    'expires_at' => now()->addMinutes(5)
                ]
            );

            // Send email (optional)
            // Mail::raw("Your reset OTP is: $otp", function ($message) use ($email) {
            //     $message->to($email)->subject('Reset Password OTP');
            // });

            return response()->json([
                'status' => true,
                'message' => 'Reset OTP sent successfully'
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => 'Error sending OTP'
            ], 500);
        }
    }

    public function resetPassword(Request $request)
{
    try {

        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'otp'      => 'required',
            'password' => 'required|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $email = strtolower($request->email);

        $otpRecord = Otp::where('email', $email)->first();

        // ❌ OTP not found
        if (!$otpRecord) {
            return response()->json([
                'status' => false,
                'message' => 'OTP not found'
            ], 400);
        }

        // ❌ Expired OTP
        if ($otpRecord->isExpired()) {
            $otpRecord->delete();

            return response()->json([
                'status' => false,
                'message' => 'OTP expired'
            ], 400);
        }

        // ❌ Invalid OTP
        if (!Hash::check($request->otp, $otpRecord->otp)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid OTP'
            ], 400);
        }

        // ✅ Update password
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // ✅ Delete OTP after success
        $otpRecord->delete();

        return response()->json([
            'status' => true,
            'message' => 'Password reset successfully'
        ]);

    } catch (\Exception $e) {

        return response()->json([
            'status' => false,
            'message' => 'Error resetting password'
        ], 500);
    }
}
}
