<?php

namespace App\Http\Controllers;

use Hash;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

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
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|min:3',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6'
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => bcrypt($request->password),
            'role'     => 'USER'
        ]);

        return response()->json([
            'status'=> true,
        ]);
    }

    // login user
   public function doLogin(Request $request)
{
    // ✅ Step 1: Validate request
    $request->validate([
        'email'    => 'required|email',
        'password' => 'required'
    ]);

    // ✅ Step 2: Find user
    $user = User::where('email', $request->email)->first();

    
    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials'
        ], 401);
    }

    // ✅ Step 3: Create token (Sanctum)
    $token = $user->createToken('angular-token')->plainTextToken;

    // ✅ Step 4: Send JSON response (NO redirect)
    return response()->json([
        'success' => true,
        'message' => 'Login successful',
        'token'   => $token,
        'role'    => $user->role,
        'user'    => $user
    ]);
}


    // logout
   public function logout(Request $request)
{
    $request->user()->currentAccessToken()->delete();

    return response()->json([
        'status'  => true,
        'message' => 'Logged out successfully'
    ]);
}
public function me(Request $request)
{
     return response()->json([
        'user' => $request->user()->load('address')
    ]);
}


}
