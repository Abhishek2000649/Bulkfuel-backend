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
use Cloudinary\Api\Upload\UploadApi;
use Illuminate\Support\Str;

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
            if (!Auth::check()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthorized. Please login first.'
                ], 401);
            }


            $orders = Order::where('user_id', Auth::id())
                ->with([
                    'items.product' => function ($query) {
                        $query->select('id', 'name', 'price', 'image', 'category_id');
                    },
                    'delivery',
                    'orderAddress'
                ])
                ->latest()
                ->get()
                ->map(function ($order) {

                    $order->items = $order->items->map(function ($item) {

                        if ($item->product && $item->product->image) {
                            $item->product->image_url = asset($item->product->image);
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
            $user->profile_image = $user->profile_image ?? null;

            return response()->json([
                'status' => true,
                'message' => 'Profile fetched successfully.',
                'user' => $user
            ], 200,  [], JSON_UNESCAPED_SLASHES);
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

            // 🔐 AUTH CHECK
            if (!Auth::guard('sanctum')->check()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthorized. Please login first.'
                ], 401);
            }

            $user = Auth::guard('sanctum')->user();

            if (!$user) {
                return response()->json([
                    'status'  => false,
                    'message' => 'User not found.'
                ], 404);
            }

            // ✅ VALIDATION
            $validated = $request->validate([
                'name'  => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'phone' => 'required|digits:10|unique:users,phone,' . $user->id,
                'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120'
            ]);

            $imageUrl = $user->profile_image;
            if ($request->hasFile('image')) {

                $file = $request->file('image');

                if ($file->isValid()) {

                    $folder = "profile_images";

                    // ✅ Upload FIRST (safe approach)
                    $upload = (new \Cloudinary\Api\Upload\UploadApi())->upload(
                        $file->getRealPath(),
                        [
                            'folder' => $folder,
                            'transformation' => [
                                'width' => 300,
                                'height' => 300,
                                'crop' => 'fill',
                                'quality' => 'auto'
                            ]
                        ]
                    );

                    $imageUrl = $upload['secure_url'];

                    // ❌ Delete OLD image (after success)
                    if ($user->profile_image) {
                        $this->deleteFromCloudinary($user->profile_image);
                    }
                }
            }

            // =====================================================
            // 💾 UPDATE USER
            // =====================================================
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
            ], 200,  [], JSON_UNESCAPED_SLASHES);
        } catch (\Illuminate\Validation\ValidationException $e) {

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
    private function deleteFromCloudinary($url)
    {
        try {

            if (!$url) return;

            // ✅ Extract path from URL
            $path = parse_url($url, PHP_URL_PATH);

            // remove `/image/upload/` or similar
            $path = preg_replace('/^\/.*\/upload\//', '', $path);

            // remove version (v12345/)
            $path = preg_replace('/^v\d+\//', '', $path);

            // remove extension
            $publicId = pathinfo($path, PATHINFO_DIRNAME) . '/' . pathinfo($path, PATHINFO_FILENAME);

            // ✅ Delete from Cloudinary
            (new \Cloudinary\Api\Upload\UploadApi())->destroy($publicId, [
                'resource_type' => 'image'
            ]);
        } catch (\Exception $e) {
            // optional: \Log::error($e->getMessage());
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
                'phone_number' => 'required|digits:10',
                'alternate_phone' => 'nullable|digits:10',

                'address' => 'required|string',

                'house_no' => 'required|string',
                'building_name' => 'nullable|string',
                'street' => 'nullable|string',
                'area' => 'required|string',
                'landmark' => 'nullable|string',

                'city' => 'required|string',
                'state' => 'required|string',
                'pincode' => 'required|digits:6',

                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',

                'delivery_instructions' => 'nullable|string',
            ]);


            $address = Address::create([
                'user_id' => $user->id,

                'phone_number' => $validated['phone_number'],
                'alternate_phone' => $validated['alternate_phone'] ?? null,

                'address' => $validated['address'],

                'house_no' => $validated['house_no'],
                'building_name' => $validated['building_name'] ?? null,
                'street' => $validated['street'] ?? null,
                'area' => $validated['area'],
                'landmark' => $validated['landmark'] ?? null,

                'city' => $validated['city'],
                'state' => $validated['state'],
                'pincode' => $validated['pincode'],

                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,

                'delivery_instructions' => $validated['delivery_instructions'] ?? null,

                'is_current' => 0
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Address Added successfully.',
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
    public function getAddresses()
    {
        $user = Auth::user();

        $addresses = Address::where('user_id', $user->id)->get();

        return response()->json([
            'status' => true,
            'data' => $addresses
        ]);
    }
    public function setCurrentAddress($id)
    {
        $user = Auth::user();

        // reset old
        Address::where('user_id', $user->id)
            ->update(['is_current' => 0]);

        // set new
        Address::where('id', $id)
            ->where('user_id', $user->id)
            ->update(['is_current' => 1]);

        return response()->json([
            'status' => true,
            'message' => 'Current address updated'
        ]);
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


    public function deleteAddress($id)
    {
        try {

            if (!Auth::guard('sanctum')->check()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthorized. Please login first.'
                ], 401);
            }

            $user = Auth::user();


            $address = Address::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$address) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Address not found.'
                ], 404);
            }



            $isCurrent = $address->is_current;


            $address->delete();


            if ($isCurrent) {
                Address::where('user_id', $user->id)
                    ->update(['is_current' => 0]);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Address deleted successfully.'
            ], 200);
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
