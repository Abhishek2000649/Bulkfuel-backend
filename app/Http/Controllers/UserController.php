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
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class UserController extends Controller
{

    public function home(Request $request)
    {
        try {

            $products = Product::with(['warehouseProducts', 'category'])
                ->select('id', 'name', 'price', 'image', 'category_id') // optimize
                ->get()
                ->map(function ($product) {
                    $product->image_url = $product->image
                        ? asset($product->image)
                        : null;
                    return $product;
                });

            $cartItems = [];

            if ($request->bearerToken()) {

                $token = PersonalAccessToken::findToken($request->bearerToken());

                if (!$token) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Invalid or expired token.'
                    ], 401);
                }

                $user = $token->tokenable;

                if (!$user) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'User not found.'
                    ], 404);
                }

                $cartItems = Cart::where('user_id', $user->id)
                    ->pluck('quantity', 'product_id');
            }

            return response()->json([
                'status'    => true,
                'products'  => $products,
                'cartItems' => $cartItems,
            ], 200);
        } catch (QueryException $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Database error occurred.'
            ], 500);
        } catch (AuthenticationException $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Authentication failed.'
            ], 401);
        } catch (ModelNotFoundException $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Data not found.'
            ], 404);
        } catch (Exception $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong.'
            ], 500);
        }
    }

    public function placeOrder(Request $request)
    {
        try {

            // 1️⃣ Authentication Check (Sanctum protected route)
            if (!Auth::check()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthorized. Please login first.'
                ], 401);
            }

            // 2️⃣ Validation
            $validated = $request->validate([
                'items' => 'required|array',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|numeric|min:1',
            ]);

            $totalAmount = 0;

            // 3️⃣ Stock Checking
            foreach ($validated['items'] as $item) {

                $product = Product::find($item['product_id']);

                if (!$product) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Product not found.'
                    ], 404);
                }

                if ($item['quantity'] > $product->totalStock()) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Not enough stock for ' . $product->name,
                    ], 400);
                }

                $totalAmount += $product->price * $item['quantity'];
            }

            // 4️⃣ Create Order
            $order = Order::create([
                'user_id' => Auth::id(),
                'total_amount' => $totalAmount,
                'status' => 'PENDING',
            ]);

            // 5️⃣ Create Order Items
            foreach ($validated['items'] as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'price'      => Product::find($item['product_id'])->price,
                ]);
            }

            // 6️⃣ Create Payment
            Payment::create([
                'order_id' => $order->id,
                'method'   => 'COD',
                'status'   => 'SUCCESS',
            ]);

            // 7️⃣ Clear Cart
            Cart::where('user_id', Auth::id())->delete();

            return response()->json([
                'status'   => true,
                'message'  => 'Order placed successfully',
                'order_id' => $order->id,
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
                'message' => 'Resource not found.'
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

    public function orders()
    {
        try {

            // 1️⃣ Check Authentication (Sanctum Protected Route)
            if (!Auth::check()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthorized. Please login first.'
                ], 401);
            }

            $orders = Order::where('user_id', Auth::id())
                ->with(['items.product' => function ($query) {
                    $query->select('id', 'name', 'price', 'image', 'category_id');
                }], 'delivery')
                ->latest()
                ->get()
                ->map(function ($order) {

                    $order->items->map(function ($item) {

                        if ($item->product && $item->product->image) {
                            $item->product->image_url = asset($item->product->image);
                        } else {
                            $item->product->image_url = null;
                        }

                        return $item;
                    });

                    return $order;
                });

            return response()->json([
                'status' => true,
                'orders' => $orders,
            ], 200);
        } catch (AuthenticationException $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Authentication failed.'
            ], 401);
        } catch (ModelNotFoundException $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Orders not found.'
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

        // relation load
        $user->load('address');

        // ✅ Cloudinary URL (Direct)
        $user->profile_image_url = $user->profile_image ?? null;

        return response()->json([
            'status' => true,
            'message' => 'Profile fetched successfully.',
            'user' => $user
        ], 200);

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

        // Validation
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'required|digits:10|unique:users,phone,' . $user->id,
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120'
        ]);

        $imageUrl = $user->profile_image;

        if ($request->hasFile('image')) {

            $file = $request->file('image');

            $upload = Cloudinary::upload($file->getRealPath(), [
                'folder' => 'profile_images',
                'transformation' => [
                    'width' => 200,
                    'height' => 200,
                    'crop' => 'fill'
                ]
            ]);

            $imageUrl = $upload->getSecurePath();
        }

        $user->update([
            'name'          => $validated['name'],
            'email'         => $validated['email'],
            'phone'         => $validated['phone'],
            'profile_image' => $imageUrl
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Profile updated successfully.',
            'data'    => $user
        ], 200);

    } catch (ValidationException $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Validation failed.',
            'errors'  => $e->errors()
        ], 422);

    } catch (\Throwable $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Something went wrong.',
            'error'   => $e->getMessage(),
            'line'    => $e->getLine()
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
