<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\orderAddress;
use App\Models\OrderWarehouse;
use App\Models\User;
use App\Models\WarehouseProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\AuthenticationException;
use Exception;

class OrderController extends Controller
{

    public function checkout(Request $request)
    {
        try {

            if (!Auth::check()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthorized. Please login first.'
                ], 401);
            }

            $validated = $request->validate([
                'cart_ids'   => 'required|array|min:1',
                'cart_ids.*' => 'integer|exists:carts,id'
            ]);

            $cartIds = $validated['cart_ids'];

            // ✅ FIRST FETCH CART
            $cartItems = Cart::with('product')
                ->where('user_id', Auth::id())
                ->whereIn('id', $cartIds)
                ->get();

            if ($cartItems->isEmpty()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Invalid cart selection'
                ], 404);
            }

            foreach ($cartItems as $item) {
                if (!$item->product) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Product not found in cart.'
                    ], 404);
                }
            }

            // ✅ NOW APPLY PAYMENT LOGIC
            $hasOnlineOnly = false;
            $hasCashOnly   = false;
            $allBoth       = true;

            foreach ($cartItems as $item) {
                $type = $item->product->payment_type;

                if ($type === 'online') {
                    $hasOnlineOnly = true;
                }

                if ($type === 'cash') {
                    $hasCashOnly = true;
                }

                if ($type !== 'both') {
                    $allBoth = false;
                }
            }

            // ✅ FINAL DECISION
            if ($hasOnlineOnly) {
                $allowedPayment = ['online'];
            } elseif ($allBoth) {
                $allowedPayment = ['cash', 'online'];
            } elseif ($hasCashOnly) {
                $allowedPayment = ['cash'];
            } else {
                $allowedPayment = ['online'];
            }

            // ✅ TOTAL
            $totalAmount = $cartItems->sum(function ($item) {
                return $item->product->price * $item->quantity;
            });

            return response()->json([
                'status'         => true,
                'cartItems'      => $cartItems,
                'totalAmount'    => $totalAmount,
                'user'           => Auth::user()->load('address'),
                'cartIds'        => $cartIds,
                'allowedPayment' => $allowedPayment
            ], 200);
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
            $validated = $request->validate([
                'address_id'     => 'required|exists:addresses,id',
                'payment_method' => 'required|in:online,cash',
                'cart_ids'       => 'required|array|min:1',
                'cart_ids.*'     => 'integer|exists:carts,id'
            ]);

            $userId = Auth::id();

            // ✅ 2. Get Cart Items
            $cartItems = Cart::with('product')
                ->where('user_id', $userId)
                ->whereIn('id', $validated['cart_ids'])
                ->get();

            if ($cartItems->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Cart is empty'
                ], 400);
            }

            // ✅ 3. Validate Products
            foreach ($cartItems as $item) {
                if (!$item->product) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Product not found in cart'
                    ], 404);
                }
            }

            // ✅ 4. Calculate Total
            $totalAmount = $cartItems->sum(
                fn($item) => $item->product->price * $item->quantity
            );

            // ✅ 5. Warehouse Allocation
            $itemWarehouseMap = [];

            foreach ($cartItems as $item) {

                $stocks = WarehouseProduct::where('product_id', $item->product_id)->get();

                $selectedWarehouse = null;

                foreach ($stocks as $stock) {
                    if ($stock->stock_quantity >= $item->quantity) {
                        $selectedWarehouse = $stock->warehouse_id;
                        break;
                    }
                }

                if (!$selectedWarehouse) {
                    return response()->json([
                        'status' => false,
                        'message' => "Product '{$item->product->name}' is out of stock"
                    ], 400);
                }

                $itemWarehouseMap[$item->id] = $selectedWarehouse;
            }

            // ✅ 6. Get Address (IMPORTANT)
            $address = Address::where('id', $validated['address_id'])
                ->where('user_id', $userId)
                ->first();

            if (!$address) {
                return response()->json([
                    'status' => false,
                    'message' => 'Address not found'
                ], 404);
            }

            DB::transaction(function () use (
                $validated,
                $cartItems,
                $itemWarehouseMap,
                $totalAmount,
                $userId,
                $address
            ) {

               
                $orderAddress = orderAddress::create([
                    'user_address_id' => $address->id,

                    'phone_number'    => $address->phone_number,
                    'alternate_phone' => $address->alternate_phone,

                    'address'        => $address->address,

                    'house_no'       => $address->house_no,
                    'building_name'  => $address->building_name,
                    'street'         => $address->street,
                    'area'           => $address->area,
                    'landmark'       => $address->landmark,

                    'city'           => $address->city,
                    'state'          => $address->state,
                    'pincode'        => $address->pincode,

                    'latitude'       => $address->latitude,
                    'longitude'      => $address->longitude,

                    'delivery_instructions' => $address->delivery_instructions,

                    
                ]);

                // 🔥 7.2 Create Order
                $order = Order::create([
                    'user_id'          => $userId,
                    'total_amount'     => $totalAmount,
                    'status'           => 'CONFIRMED',
                    'payment_method'   => $validated['payment_method'],
                    'order_address_id' => $orderAddress->id,
                ]);

                $usedWarehouses = [];

                // 🔥 7.3 Create Order Items + Deduct Stock
                foreach ($cartItems as $item) {

                    $warehouseId = $itemWarehouseMap[$item->id];
                    $usedWarehouses[] = $warehouseId;

                    $order->items()->create([
                        'product_id'   => $item->product_id,
                        'warehouse_id' => $warehouseId,
                        'quantity'     => $item->quantity,
                        'price'        => $item->product->price,
                    ]);

                    WarehouseProduct::where('warehouse_id', $warehouseId)
                        ->where('product_id', $item->product_id)
                        ->decrement('stock_quantity', $item->quantity);
                }

                // 🔥 7.4 Save Order Warehouses
                foreach (array_unique($usedWarehouses) as $wid) {
                    OrderWarehouse::create([
                        'order_id'     => $order->id,
                        'warehouse_id' => $wid
                    ]);
                }

                // 🔥 7.5 Clear Cart
                Cart::whereIn('id', $validated['cart_ids'])->delete();
            });

            // ✅ 8. Success Response
            return response()->json([
                'status'  => true,
                'message' => 'Order placed successfully'
            ], 200);
        } catch (ValidationException $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors()
            ], 422);
        } catch (Exception $e) {

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage() // 🔥 helpful for debug
            ], 500);
        }
    }
}
