<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{ 
    public function index()
    {
        return response()->json(
        User::where('role', '!=', 'ADMIN')->get()
    );
    }
    public function add()
    {
        return view('admin.userManagement.addUser');
    }

    /* =========================
       STORE NEW USER
       ========================= */
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6'
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'USER'
        ]);

        return response()->json([
            'status'=>true,
        ]);
    }

    /* =========================
       EDIT USER
       ========================= */
    public function edit($id)
    {
        
         return response()->json(
        User::findOrFail($id)
    );
    }

    /* =========================
       UPDATE USER
       ========================= */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name'  => 'required',
            'email' => 'required|email|unique:users,email,' . $id,
            'role' =>'required',
        ]);

        $user->update([
            'name'  => $request->name,
            'email' => $request->email,
            'role'=>$request->role,
        ]);

          return response()->json(['status' => true]);
    }

    /* =========================
       DELETE USER
       ========================= */
    public function destroy($id)
    {
        if (auth()->id() == $id) {
        return response()->json([
            'message' => 'You cannot delete yourself'
        ], 403);
    }

    User::where('id', $id)->delete();

    return response()->json(['status' => true]);
    }
}
