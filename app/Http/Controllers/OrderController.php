<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderAddress;
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

            // $pendingOrder = Order::where('user_id', $userId)
            //     ->where('status', 'PENDING')
            //     ->exists();

            // if ($pendingOrder) {
            //     return response()->json([
            //         'status' => false,
            //         'message' => 'You already have a pending order'
            //     ]);
            // }

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


            foreach ($cartItems as $item) {
                if (
                    $item->product->payment_type === 'online' &&
                    $validated['payment_method'] === 'cash'
                ) {

                    return response()->json([
                        'status' => false,
                        'message' => 'This product requires online payment'
                    ], 400);
                }
            }

            $totalAmount = $cartItems->sum(
                fn($item) => $item->product->price * $item->quantity
            );

            $itemWarehouseMap = [];

            foreach ($cartItems as $item) {

                $stocks = WarehouseProduct::where('product_id', $item->product_id)->get();

                $selectedWarehouse = null;

                foreach ($stocks as $stock) {

                    $availableStock = $stock->stock_quantity - $stock->reserved_quantity;

                    if ($availableStock >= $item->quantity) {
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

            $address = Address::where('id', $validated['address_id'])
                ->where('user_id', $userId)
                ->first();

            if (!$address) {
                return response()->json([
                    'status' => false,
                    'message' => 'Address not found'
                ], 404);
            }

            $order = DB::transaction(function () use (
                $validated,
                $cartItems,
                $itemWarehouseMap,
                $totalAmount,
                $userId,
                $address
            ) {

                $orderAddress = OrderAddress::create([
                    'user_address_id' => $address->id,
                    'phone_number'    => $address->phone_number,
                    'alternate_phone' => $address->alternate_phone,
                    'address'         => $address->address,
                    'house_no'        => $address->house_no,
                    'building_name'   => $address->building_name,
                    'street'          => $address->street,
                    'area'            => $address->area,
                    'landmark'        => $address->landmark,
                    'city'            => $address->city,
                    'state'           => $address->state,
                    'pincode'         => $address->pincode,
                    'latitude'        => $address->latitude,
                    'longitude'       => $address->longitude,
                    'delivery_instructions' => $address->delivery_instructions,
                ]);

                $status = $validated['payment_method'] === 'online'
                    ? 'PENDING'
                    : 'CONFIRMED';

                $paymentStatus = $validated['payment_method'] === 'online'
                    ? 'PENDING'
                    : 'PAID';

                $order = Order::create([
                    'user_id'          => $userId,
                    'total_amount'     => $totalAmount,
                    'status'           => $status,
                    'payment_method'   => $validated['payment_method'],
                    'payment_status'   => $paymentStatus,
                    'order_address_id' => $orderAddress->id,
                ]);

                $usedWarehouses = [];

                foreach ($cartItems as $item) {

                    $warehouseId = $itemWarehouseMap[$item->id];
                    $usedWarehouses[] = $warehouseId;

                    $order->items()->create([
                        'product_id'   => $item->product_id,
                        'warehouse_id' => $warehouseId,
                        'quantity'     => $item->quantity,
                        'price'        => $item->product->price,
                    ]);

                    if ($validated['payment_method'] === 'cash') {
                        WarehouseProduct::where('warehouse_id', $warehouseId)
                            ->where('product_id', $item->product_id)
                            ->decrement('stock_quantity', $item->quantity);
                    }
                    if ($validated['payment_method'] === 'online') {
                        WarehouseProduct::where('warehouse_id', $warehouseId)
                            ->where('product_id', $item->product_id)
                            ->increment('reserved_quantity', $item->quantity);
                    }
                }

                foreach (array_unique($usedWarehouses) as $wid) {
                    OrderWarehouse::create([
                        'order_id'     => $order->id,
                        'warehouse_id' => $wid
                    ]);
                }

                if ($validated['payment_method'] === 'cash') {
                    Cart::whereIn('id', $validated['cart_ids'])->delete();
                }

                return $order;
            });

            return response()->json([
                'status' => true,
                'message' => 'Order created successfully',
                'data' => [
                    'order_id' => $order->id,
                    'amount' => $order->total_amount,
                    'payment_method' => $order->payment_method
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function cancelOrder($id)
{
    $order = Order::where('id', $id)
        ->where('user_id', Auth::id())
        ->where('payment_status', 'PENDING')
        ->first();

    if (!$order) {
        return response()->json([
            'status' => false,
            'message' => 'Order already processed or not found'
        ]);
    }

    DB::transaction(function () use ($order) {

        foreach ($order->items as $item) {
            WarehouseProduct::where('warehouse_id', $item->warehouse_id)
                ->where('product_id', $item->product_id)
                ->decrement('reserved_quantity', $item->quantity);
        }

        $order->update([
            'status' => 'CANCELLED',
            'payment_status' => 'FAILED'
        ]);
    });

    return response()->json([
        'status' => true,
        'message' => 'Order cancelled successfully'
    ]);
}
}
