<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderWarehouse;
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

       
        $totalAmount = $cartItems->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        return response()->json([
            'status'      => true,
            'cartItems'   => $cartItems,
            'totalAmount' => $totalAmount,
            'user'        => Auth::user()->load('address'),
            'cartIds'     => $cartIds
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



public function placeOrder(Request $request)
{
    try {

       
        $validated = $request->validate([
            'address'        => 'required|string',
            'city'           => 'required|string',
            'state'          => 'required|string',
            'pincode'        => 'required|string',
            'payment_method' => 'required|string',
            'cart_ids'       => 'required|array|min:1',
            'cart_ids.*'     => 'integer|exists:carts,id'
        ]);

        $cartIds = $validated['cart_ids'];

      
        $cartItems = Cart::with('product')
            ->where('user_id', Auth::id())
            ->whereIn('id', $cartIds)
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'status'  => false,
                'message' => 'Cart is empty'
            ], 400);
        }

      
        foreach ($cartItems as $item) {
            if (!$item->product) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Product not found in cart'
                ], 404);
            }
        }

        $totalAmount = $cartItems->sum(fn ($item) =>
            $item->product->price * $item->quantity
        );

        
        $itemWarehouseMap = [];

        foreach ($cartItems as $item) {

            $warehouseStocks = WarehouseProduct::where('product_id', $item->product_id)
                ->orderBy('warehouse_id')
                ->get();

            $selectedWarehouse = null;

            foreach ($warehouseStocks as $stock) {
                if ($stock->stock_quantity >= $item->quantity) {
                    $selectedWarehouse = $stock->warehouse_id;
                    break;
                }
            }

            if (!$selectedWarehouse) {
                return response()->json([
                    'status'  => false,
                    'message' => "Product '{$item->product->name}' is out of stock"
                ], 400);
            }

            $itemWarehouseMap[$item->id] = $selectedWarehouse;
        }

      
        DB::transaction(function () use (
            $validated,
            $cartItems,
            $cartIds,
            $itemWarehouseMap,
            $totalAmount
        ) {

            $userId = Auth::id();

            // Sync Address
            $address = Address::where('user_id', $userId)->first();

            if ($address) {
                $address->update([
                    'address' => $validated['address'],
                    'city'    => $validated['city'],
                    'state'   => $validated['state'],
                    'pincode' => $validated['pincode'],
                ]);
            } else {
                Address::create([
                    'user_id' => $userId,
                    'address' => $validated['address'],
                    'city'    => $validated['city'],
                    'state'   => $validated['state'],
                    'pincode' => $validated['pincode'],
                ]);
            }

            // Create Order
            $order = Order::create([
                'user_id'       => $userId,
                'total_amount'  => $totalAmount,
                'status'        => 'CONFIRMED',
                'address'       => $validated['address'],
                'city'          => $validated['city'],
                'state'         => $validated['state'],
                'pincode'       => $validated['pincode'],
                'payment_method'=> $validated['payment_method'],
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

                WarehouseProduct::where('warehouse_id', $warehouseId)
                    ->where('product_id', $item->product_id)
                    ->decrement('stock_quantity', $item->quantity);
            }

            foreach (array_unique($usedWarehouses) as $wid) {
                OrderWarehouse::create([
                    'order_id'     => $order->id,
                    'warehouse_id' => $wid
                ]);
            }

            Cart::whereIn('id', $cartIds)->delete();
        });

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

    } catch (AuthenticationException $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Authentication failed'
        ], 401);

    } catch (ModelNotFoundException $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Resource not found'
        ], 404);

    } catch (QueryException $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Database error occurred'
        ], 500);

    } catch (Exception $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Something went wrong'
        ], 500);
    }
}
}
