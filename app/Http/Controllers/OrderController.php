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

class OrderController extends Controller
{
    /* =========================
       CHECKOUT (SELECTED ITEMS)
       ========================= */

       
    // public function checkout(Request $request)
    // {
    //     $cartIds = $request->cart_ids;

    //     if (!$cartIds || count($cartIds) === 0) {
    //         return back()->with('error', 'Please select at least one product');
    //     }

    //     $cartItems = Cart::with('product')
    //         ->where('user_id', Auth::id())
    //         ->whereIn('id', $cartIds)
    //         ->get();

    //     if ($cartItems->isEmpty()) {
    //         return back()->with('error', 'Invalid cart selection');
    //     }

    //     $totalAmount = $cartItems->sum(function ($item) {
    //         return $item->product->price * $item->quantity;
    //     });

    //     $user = Auth::user();
    //     $user_address = Address::where('user_id', Auth::id())->first();

    //     return view('user.checkout', compact(
    //         'cartItems',
    //         'totalAmount',
    //         'user',
    //         'user_address',
    //         'cartIds'
    //     ));
    // }

    public function checkout(Request $request)
{
    $cartIds = $request->cart_ids;

    if (!$cartIds || count($cartIds) === 0) {
        return response()->json([
            'status' => false,
            'message' => 'Please select at least one product new',
            'card'=>$cartIds,
        ], 422);
    }

    $cartItems = Cart::with('product')
        ->where('user_id', Auth::id())
        ->whereIn('id', $cartIds)
        ->get();

    if ($cartItems->isEmpty()) {
        return response()->json([
            'status' => false,
            'message' => 'Invalid cart selection'
        ], 404);
    }

    $totalAmount = $cartItems->sum(fn ($item) =>
        $item->product->price * $item->quantity
    );

    return response()->json([
        'status' => true,
        'cartItems' => $cartItems,
        'totalAmount' => $totalAmount,
        'user' => Auth::user()->load('address'),
        'cartIds' => $cartIds
    ]);
}
// public function placeOrder(Request $request)
// {
//     $request->validate([
//         'address' => 'required|string',
//         'city' => 'required|string',
//         'state' => 'required|string',
//         'pincode' => 'required|string',
//         'payment_method' => 'required|string',
//         'cart_ids' => 'required|array'
//     ]);

//     $cartIds = $request->cart_ids;
//     $cartItems = Cart::with('product')
//         ->where('user_id', Auth::id())
//         ->whereIn('id', $cartIds)
//         ->get();

//     if ($cartItems->isEmpty()) {
//         return response()->json([
//             'status' => false,
//             'message' => 'Cart is empty'
//         ], 400);
//     }
//     $totalAmount = $cartItems->sum(function ($item) {
//         return $item->product->price * $item->quantity;
//     });

   
//     $itemWarehouseMap = []; 

//     foreach ($cartItems as $item) {

//         $warehouseStocks = WarehouseProduct::where('product_id', $item->product_id)
//             ->orderBy('warehouse_id', 'asc')
//             ->get();

//         $selectedWarehouse = null;

//         foreach ($warehouseStocks as $stock) {
//             if ($stock->stock_quantity >= $item->quantity) {
//                 $selectedWarehouse = $stock->warehouse_id;
//                 break; 
//             }
//         }

//         if (!$selectedWarehouse) {
//             return response()->json([
//                 'status' => false,
//                 'message' => "Product '{$item->product->name}' is out of stock"
//             ], 400);
//         }

//         $itemWarehouseMap[$item->id] = $selectedWarehouse;
//     }
//     DB::transaction(function () use (
//         $request,
//         $cartItems,
//         $cartIds,
//         $itemWarehouseMap,
//         $totalAmount
//     ) {
//         $order = Order::create([
//             'user_id' => Auth::id(),
//             'total_amount' => $totalAmount,
//             'status' => 'PENDING',
//             'address' => $request->address,
//             'city' => $request->city,
//             'state' => $request->state,
//             'pincode' => $request->pincode,
//             'payment_method' => $request->payment_method,
//         ]);

//         $usedWarehouses = [];

//         foreach ($cartItems as $item) {

//             $warehouseId = $itemWarehouseMap[$item->id];
//             $usedWarehouses[] = $warehouseId;
//             $order->items()->create([
//                 'product_id' => $item->product_id,
//                 'warehouse_id' => $warehouseId,
//                 'quantity' => $item->quantity,
//                 'price' => $item->product->price,
//             ]);
//             WarehouseProduct::where('warehouse_id', $warehouseId)
//                 ->where('product_id', $item->product_id)
//                 ->decrement('stock_quantity', $item->quantity);
//         }
//         foreach (array_unique($usedWarehouses) as $wid) {
//             OrderWarehouse::create([
//                 'order_id' => $order->id,
//                 'warehouse_id' => $wid
//             ]);
//         }
//         Cart::whereIn('id', $cartIds)->delete();
//     });
//     return response()->json([
//         'status' => true,
//         'message' => 'Order placed successfully'
//     ], 200);
// }


public function placeOrder(Request $request)
{
    // ==============================
    // 1️⃣ Validate Request
    // ==============================
    $request->validate([
        'address' => 'required|string',
        'city' => 'required|string',
        'state' => 'required|string',
        'pincode' => 'required|string',
        'payment_method' => 'required|string',
        'cart_ids' => 'required|array'
    ]);

    // ==============================
    // 2️⃣ Fetch Cart Items
    // ==============================
    $cartIds = $request->cart_ids;

    $cartItems = Cart::with('product')
        ->where('user_id', Auth::id())
        ->whereIn('id', $cartIds)
        ->get();

    if ($cartItems->isEmpty()) {
        return response()->json([
            'status' => false,
            'message' => 'Cart is empty'
        ], 400);
    }

    // ==============================
    // 3️⃣ Calculate Total Amount
    // ==============================
    $totalAmount = $cartItems->sum(function ($item) {
        return $item->product->price * $item->quantity;
    });

    // ==============================
    // 4️⃣ Select Warehouse per Item
    // ==============================
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
                'status' => false,
                'message' => "Product '{$item->product->name}' is out of stock"
            ], 400);
        }

        $itemWarehouseMap[$item->id] = $selectedWarehouse;
    }

    // ==============================
    // 5️⃣ Transaction Start
    // ==============================
    DB::transaction(function () use (
        $request,
        $cartItems,
        $cartIds,
        $itemWarehouseMap,
        $totalAmount
    ) {

        // ==============================
        // 5.1️⃣ Sync User Address
        // ==============================
        $address = Address::where('user_id', Auth::id())->first();

        if ($address) {
            if (
                $address->address !== $request->address ||
                $address->city !== $request->city ||
                $address->state !== $request->state ||
                $address->pincode !== $request->pincode
            ) {
                $address->update([
                    'address' => $request->address,
                    'city'    => $request->city,
                    'state'   => $request->state,
                    'pincode' => $request->pincode,
                ]);
            }
        } else {
            Address::create([
                'user_id' => Auth::id(),
                'address' => $request->address,
                'city'    => $request->city,
                'state'   => $request->state,
                'pincode' => $request->pincode,
            ]);
        }
        $order = Order::create([
            'user_id' => Auth::id(),
            'total_amount' => $totalAmount,
            'status' => 'PENDING',
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'pincode' => $request->pincode,
            'payment_method' => $request->payment_method,
        ]);

        // ==============================
        // 5.3️⃣ Create Order Items
        // ==============================
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

            // 🔻 Decrease stock
            WarehouseProduct::where('warehouse_id', $warehouseId)
                ->where('product_id', $item->product_id)
                ->decrement('stock_quantity', $item->quantity);
        }

        // ==============================
        // 5.4️⃣ Save Order Warehouses (Optional)
        // ==============================
        foreach (array_unique($usedWarehouses) as $wid) {
            OrderWarehouse::create([
                'order_id' => $order->id,
                'warehouse_id' => $wid
            ]);
        }

        // ==============================
        // 5.5️⃣ Clear Cart
        // ==============================
        Cart::whereIn('id', $cartIds)->delete();
    });

    // ==============================
    // 6️⃣ Response
    // ==============================
    return response()->json([
        'status' => true,
        'message' => 'Order placed successfully'
    ], 200);
}
}
