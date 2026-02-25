<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;


class AuthController extends Controller
{

    public function register(Request $request)
{
    try {

        
        if (!$request->all()) {
            return response()->json([
                'status' => false,
                'code'   => 400,
                'message'=> 'Bad Request - No data received'
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

public function me(Request $request)
{
     return response()->json([
        'user' => $request->user()->load('address')
    ]);
}


}
