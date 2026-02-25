<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Address;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Exception;



class UserController extends Controller
{
    
 public function home(Request $request)
    {
        $products = Product::with(['warehouseProducts', 'category'])->get();

        $cartItems = [];

          if ($request->bearerToken()) {
        $token = PersonalAccessToken::findToken($request->bearerToken());

        if ($token) {
            $user = $token->tokenable;
            $cartItems = Cart::where('user_id', $user->id)
                ->pluck('quantity', 'product_id');
        }
    }

        return response()->json([
            'status' => true,
            'products' => $products,
            'cartItems' => $cartItems,
        ]);
    }

    
    public function placeOrder(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
        ]);

        $totalAmount = 0;

        foreach ($request->items as $item) {
            $product = Product::find($item['product_id']);

            if ($item['quantity'] > $product->totalStock()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Not enough stock for ' . $product->name,
                ], 400);
            }

            $totalAmount += $product->price * $item['quantity'];
        }

        $order = Order::create([
            'user_id' => Auth::id(),
            'total_amount' => $totalAmount,
            'status' => 'PENDING',
        ]);

        foreach ($request->items as $item) {
            $order->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => Product::find($item['product_id'])->price,
            ]);
        }

        Payment::create([
            'order_id' => $order->id,
            'method' => 'COD',
            'status' => 'SUCCESS',
        ]);

        Cart::where('user_id', Auth::id())->delete();

        return response()->json([
            'status' => true,
            'message' => 'Order placed successfully',
            'order_id' => $order->id,
        ]);
    }

    
    public function orders()
    {
        $orders = Order::where('user_id', Auth::id())
            ->with([
                'items.product',
                'delivery',
            ])
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'orders' => $orders,
        ]);
    }

    
  public function profile()
{
    try {

        
        if (!Auth::check()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Please login first.'
            ], 401);
        }

        
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found.'
            ], 404);
        }

        
        $user->load('address');

        return response()->json([
            'status' => true,
            'message' => 'Profile fetched successfully.',
            'user' => $user
        ], 200);

    } catch (AuthenticationException $e) {

        return response()->json([
            'status' => false,
            'message' => 'Authentication failed.',
            'error' => $e->getMessage()
        ], 401);

    } catch (ModelNotFoundException $e) {

        return response()->json([
            'status' => false,
            'message' => 'User not found.',
            'error' => $e->getMessage()
        ], 404);

    } catch (QueryException $e) {

        return response()->json([
            'status' => false,
            'message' => 'Database error occurred.',
            'error' => $e->getMessage()
        ], 500);

    } catch (Exception $e) {

        return response()->json([
            'status' => false,
            'message' => 'Something went wrong.',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function updateBasic(Request $request)
{
    try {

        // 1️⃣ Check Authentication (Sanctum)
        if (!Auth::guard('sanctum')->check()) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthorized. Please login first.'
            ], 401);
        }

        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'User not found.'
            ], 404);
        }

        // 2️⃣ Validation
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        // 3️⃣ Update
        $user->update($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Name & email updated successfully.',
            'data'    => $user
        ], 200);

    } catch (ValidationException $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Validation failed.',
            'errors'  => $e->errors()
        ], 422);

    } catch (AuthenticationException $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Authentication failed.'
        ], 401);

    } catch (ModelNotFoundException $e) {

        return response()->json([
            'status'  => false,
            'message' => 'User not found.'
        ], 404);

    } catch (QueryException $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Database error occurred.'
        ], 500);

    } catch (Exception $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Something went wrong.'
        ], 500);
    }
}
public function updateAddress(Request $request)
{
    try {

        // 1️⃣ Check Sanctum Authentication
        if (!Auth::guard('sanctum')->check()) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthorized. Please login first.'
            ], 401);
        }

        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'User not found.'
            ], 404);
        }

        // 2️⃣ Validate Request
        $validated = $request->validate([
            'address' => 'required|string',
            'city'    => 'required|string',
            'state'   => 'required|string',
            'pincode' => 'required|string',
        ]);

        // 3️⃣ Create or Update Address
        $address = Address::updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        return response()->json([
            'status'  => true,
            'message' => 'Address updated successfully.',
            'data'    => $address,
        ], 200);

    } catch (ValidationException $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Validation failed.',
            'errors'  => $e->errors()
        ], 422);

    } catch (AuthenticationException $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Authentication failed.'
        ], 401);

    } catch (ModelNotFoundException $e) {

        return response()->json([
            'status'  => false,
            'message' => 'User not found.'
        ], 404);

    } catch (QueryException $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Database error occurred.'
        ], 500);

    } catch (Exception $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Something went wrong.'
        ], 500);
    }
}
public function updatePassword(Request $request)
{
    try {

        // 1️⃣ Check Authentication (Sanctum)
        if (!Auth::guard('sanctum')->check()) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthorized. Please login first.'
            ], 401);
        }

        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'User not found.'
            ], 404);
        }

        // 2️⃣ Validate Request
        $validated = $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:6|confirmed',
        ]);

        // 3️⃣ Check Old Password
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'status'  => false,
                'message' => 'Current password is incorrect.',
                'errors'  => [
                    'current_password' => ['Current password is incorrect.']
                ]
            ], 422);
        }

        // 4️⃣ Update Password
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Password updated successfully.'
        ], 200);

    } catch (ValidationException $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Validation failed.',
            'errors'  => $e->errors()
        ], 422);

    } catch (AuthenticationException $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Authentication failed.'
        ], 401);

    } catch (QueryException $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Database error occurred.'
        ], 500);

    } catch (Exception $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Something went wrong.'
        ], 500);
    }
}

}
