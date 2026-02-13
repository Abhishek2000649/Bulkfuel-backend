<?php

namespace App\Http\Controllers;

use Exception;
use Hash;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // show login page
    public function login()
    {
        return view('auth.login');
    }

    // show signup page
    public function signup()
    {
        return view('auth.signup');
    }

    // register user
    // public function register(Request $request)
    // {
    //     $request->validate([
    //         'name'     => 'required|min:3',
    //         'email'    => 'required|email|unique:users',
    //         'password' => 'required|min:6'
    //     ]);

    //     User::create([
    //         'name'     => $request->name,
    //         'email'    => $request->email,
    //         'password' => bcrypt($request->password),
    //         'role'     => 'USER'
    //     ]);

    //     return response()->json([
    //         'status'=> true,
    //     ]);
    // }

    public function register(Request $request)
{
    try {

        // ✅ Check empty request
        if (!$request->all()) {
            return response()->json([
                'status' => false,
                'code'   => 400,
                'message'=> 'Bad Request - No data received'
            ], 400);
        }

        // ✅ Manual Validation
        $validator = Validator::make($request->all(), [
            'name'     => 'required|min:3',
            'email'    => 'required|email',
            'password' => 'required|min:6'
        ]);

        // ❌ Validation Error (422)
        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'code'    => 422,
                'message' => 'Validation Error',
                'errors'  => $validator->errors()
            ], 422);
        }

        // ❌ Email Already Exists (409)
        if (User::where('email', $request->email)->exists()) {
            return response()->json([
                'status'  => false,
                'code'    => 409,
                'message' => 'Email already registered'
            ], 409);
        }

        // ✅ Create User
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'USER'
        ]);

        // ✅ Success (201 Created)
        return response()->json([
            'status'  => true,
            'code'    => 201,
            'message' => 'User Registered Successfully',
            'data'    => $user
        ], 201);

    } catch (Exception $e) {

        // 💥 Server Error (500)
        return response()->json([
            'status'  => false,
            'code'    => 500,
            'message' => 'Internal Server Error',
            'error'   => $e->getMessage()
        ], 500);
    }
}

    // login user
  public function doLogin(Request $request)
{
    try {

        /* =====================================================
         * 1️⃣ 422 – Validation Error
         * ===================================================== */
        $validator = \Validator::make($request->all(), [
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

        /* =====================================================
         * 2️⃣ 404 – User Not Found (OPTIONAL)
         * (Many apps still return 401 for security)
         * ===================================================== */
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'code'    => 404,
                'message' => 'User not found'
            ], 404);
        }

        /* =====================================================
         * 3️⃣ 401 – Invalid Credentials
         * ===================================================== */
        if (!\Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'code'    => 401,
                'message' => 'Invalid email or password'
            ], 401);
        }

        /* =====================================================
         * 4️⃣ 403 – Account Inactive / Blocked
         * ===================================================== */
        if ($user->status === 'inactive') {
            return response()->json([
                'success' => false,
                'code'    => 403,
                'message' => 'Account is inactive. Please contact support.'
            ], 403);
        }

        /* =====================================================
         * 5️⃣ 429 – Too Many Requests (Manual example)
         * (Normally handled by throttle middleware)
         * ===================================================== */
        if ($user->login_attempts >= 5) {
            return response()->json([
                'success' => false,
                'code'    => 429,
                'message' => 'Too many login attempts. Please try again later.'
            ], 429);
        }

        /* =====================================================
         * 6️⃣ 200 – Login Success
         * ===================================================== */
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

        /* =====================================================
         * 7️⃣ 500 – Internal Server Error
         * ===================================================== */
        return response()->json([
            'success' => false,
            'code'    => 500,
            'message' => 'Internal server error',
            'error'   => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}



    // logout
  public function logout(Request $request)
{
    try {

        /* =====================================================
         * 1️⃣ 401 – Unauthorized (Token Missing / Invalid)
         * ===================================================== */
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'code'    => 401,
                'message' => 'Unauthorized. Please login first.'
            ], 401);
        }

        /* =====================================================
         * 2️⃣ 403 – Forbidden (Optional: If account inactive)
         * ===================================================== */
        if ($request->user()->status === 'inactive') {
            return response()->json([
                'success' => false,
                'code'    => 403,
                'message' => 'Account is inactive. Logout not allowed.'
            ], 403);
        }

        /* =====================================================
         * 3️⃣ 200 – Logout Success
         * ===================================================== */
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'Logged out successfully'
        ], 200);

    } catch (\Throwable $e) {

        /* =====================================================
         * 4️⃣ 500 – Internal Server Error
         * ===================================================== */
        return response()->json([
            'success' => false,
            'code'    => 500,
            'message' => 'Internal server error',
            'error'   => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}

public function me(Request $request)
{
    try {

        /* =====================================================
         * 1️⃣ 401 – Unauthorized
         * ===================================================== */
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        /* =====================================================
         * 2️⃣ Load User with Address
         * ===================================================== */
        $user = $request->user()->load('address');

        if (!$user) {
            return response()->json([
                'message' => 'User not found.'
            ], 404);
        }

        /* =====================================================
         * 3️⃣ 200 – Success (Same as Old Structure)
         * ===================================================== */
        return response()->json([
            'user' => $user
        ], 200);

    } catch (\Throwable $e) {

        /* =====================================================
         * 4️⃣ 500 – Internal Server Error
         * ===================================================== */
        return response()->json([
            'message' => 'Internal server error'
        ], 500);
    }
}




}
