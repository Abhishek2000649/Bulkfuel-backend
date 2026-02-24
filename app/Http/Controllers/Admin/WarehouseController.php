<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
class WarehouseController extends Controller
{
    
    public function index()
    {
        try {

            if (!Auth::guard('sanctum')->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Please login first.'
                ], 401);
            }

            $warehouses = Warehouse::all();

            if ($warehouses->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No warehouses found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Warehouses fetched successfully',
                'data'    => $warehouses
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch warehouses'
            ], 500);
        }
    }


    
    public function edit($id)
    {
        try {

            if (!Auth::guard('sanctum')->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Please login first.'
                ], 401);
            }

            $warehouse = Warehouse::find($id);

            if (!$warehouse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Warehouse not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data'    => $warehouse
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch warehouse'
            ], 500);
        }
    }


    /* =========================
       STORE NEW WAREHOUSE
       ========================= */
    public function store(Request $request)
    {
        try {

            if (!Auth::guard('sanctum')->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Please login first.'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'name'    => 'required|string|max:255',
                'address' => 'required|string',
                'city'    => 'required|string|max:100',
                'state'   => 'required|string|max:100',
                'pincode' => 'required|digits:6'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors'  => $validator->errors()
                ], 422);
            }

            $warehouse = Warehouse::create([
                'name'    => $request->name,
                'address' => $request->address,
                'city'    => $request->city,
                'state'   => $request->state,
                'pincode' => $request->pincode
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Warehouse created successfully',
                'data'    => $warehouse
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to create warehouse'
            ], 500);
        }
    }


    /* =========================
       UPDATE WAREHOUSE
       ========================= */
    public function update(Request $request, $id)
    {
        try {

            if (!Auth::guard('sanctum')->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Please login first.'
                ], 401);
            }

            $warehouse = Warehouse::find($id);

            if (!$warehouse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Warehouse not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name'    => 'required|string|max:255',
                'address' => 'required|string',
                'city'    => 'required|string|max:100',
                'state'   => 'required|string|max:100',
                'pincode' => 'required|digits:6'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors'  => $validator->errors()
                ], 422);
            }

            $warehouse->update([
                'name'    => $request->name,
                'address' => $request->address,
                'city'    => $request->city,
                'state'   => $request->state,
                'pincode' => $request->pincode
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Warehouse updated successfully',
                'data'    => $warehouse
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to update warehouse'
            ], 500);
        }
    }


    /* =========================
       DELETE WAREHOUSE
       ========================= */
    public function delete($id)
    {
        try {

            if (!Auth::guard('sanctum')->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Please login first.'
                ], 401);
            }

            $warehouse = Warehouse::find($id);

            if (!$warehouse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Warehouse not found'
                ], 404);
            }

            $warehouse->delete();

            return response()->json([
                'success' => true,
                'message' => 'Warehouse deleted successfully'
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to delete warehouse'
            ], 500);
        }
    }
}
