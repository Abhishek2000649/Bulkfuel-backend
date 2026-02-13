<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function index()
    {
        try {

            $users = User::where('role', '!=', 'ADMIN')->get();

            return response()->json([
                'success' => true,
                'message' => 'Users fetched successfully',
                'data'    => $users
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong'
            ], 500);
        }
    }


    /* =========================
       STORE NEW USER
       ========================= */
    public function store(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'name'     => 'required|string|max:255',
                'email'    => 'required|email|unique:users,email',
                'password' => 'required|min:6'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors'  => $validator->errors()
                ], 422);
            }

            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'role'     => 'USER'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data'    => $user
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong'
            ], 500);
        }
    }


    /* =========================
       EDIT USER
       ========================= */
    public function edit($id)
    {
        try {

            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data'    => $user
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong'
            ], 500);
        }
    }


    /* =========================
       UPDATE USER
       ========================= */
    public function update(Request $request, $id)
    {
        try {

            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name'  => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $id,
                'role'  => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors'  => $validator->errors()
                ], 422);
            }

            $user->update([
                'name'  => $request->name,
                'email' => $request->email,
                'role'  => $request->role,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data'    => $user
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong'
            ], 500);
        }
    }


    /* =========================
       DELETE USER
       ========================= */
    public function destroy($id)
    {
        try {

            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Prevent admin from deleting themselves
            if (auth()->id() == $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot delete yourself'
                ], 403);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong'
            ], 500);
        }
    }
}
