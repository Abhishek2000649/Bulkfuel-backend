<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class CartController extends Controller
{
    
//    public function index()
// {
//     $cartItems = Cart::where('user_id', Auth::id())
//                       ->with('product')
//                       ->get();

//     // 🔹 Total payment calculate
//     $totalAmount = $cartItems->sum(function ($item) {
//         return $item->product->price * $item->quantity;
//     });

//      return response()->json([
//             'cartItems' => $cartItems,
//             'totalAmount' => $totalAmount,
//         ]);
// }


    
//     public function add($id)
//     {
//         $cart = Cart::where('user_id', Auth::id())
//                     ->where('product_id', $id)
//                     ->first();

//         if ($cart) {
//             $cart->quantity += 1;
//             $cart->save();
//         } else {
//             Cart::create([
//                 'user_id' => Auth::id(),
//                 'product_id' => $id,
//                 'quantity' => 1
//             ]);
//         }

//         return redirect()->back()->with('success','Product added to cart');
//     }

    
//  public function update(Request $request, $productId)
// {
//     $userId = Auth::id();

//     $cart = Cart::where('user_id', $userId)
//                 ->where('product_id', $productId)
//                 ->first();

//     // 🔥 total stock from all warehouses
//     $totalStock = Product::find($productId)->totalStock();

//     /* =========================
//        INCREASE
//        ========================= */
//     if ($request->action === 'increase') {

//         // cart not exists → create
//         if (!$cart) {
//             if ($totalStock > 0) {
//                 Cart::create([
//                     'user_id'    => $userId,
//                     'product_id' => $productId,
//                     'quantity'   => 1
//                 ]);
//             }
//         } else {
//             // increase only till totalStock
//             if ($cart->quantity < $totalStock) {
//                 $cart->increment('quantity');
//             }
//         }
//     }

//     /* =========================
//        DECREASE
//        ========================= */
//     if ($request->action === 'decrease' && $cart) {

//         if ($cart->quantity > 1) {
//             $cart->decrement('quantity');
//         } else {
//             $cart->delete(); // qty 0
//         }
//     }

//     return back();
// }



    
//     public function remove($id)
//     {
//         Cart::destroy($id);
//         return redirect()->back();
//     }


  public function index()
    {
        $cartItems = Cart::where('user_id', Auth::id())
            ->with('product')
            ->get();

        $totalAmount = $cartItems->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        return response()->json([
            'status' => true,
            'cartItems' => $cartItems,
            'totalAmount' => $totalAmount,
        ]);
    }

    /* ======================
       ADD TO CART
       ====================== */
    public function add($productId)
    {
        $cart = Cart::where('user_id', Auth::id())
            ->where('product_id', $productId)
            ->first();

        if ($cart) {
            
            $cart->increment('quantity');
        } else {
            Cart::create([
                'user_id' => Auth::id(),
                'product_id' => $productId,
                'quantity' => 1,
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Product added to cart',
        ]);
    }

    /* ======================
       UPDATE CART (+ / -)
       ====================== */
  public function update(Request $request, $productId)
{
    $userId = Auth::id();

    $cart = Cart::where('user_id', $userId)
        ->where('product_id', $productId)
        ->first();
    $totalStock = Product::findOrFail($productId)->totalStock;
    if ($request->action === 'increase') {

        if (!$cart) {
            if ($totalStock > 0) {
                Cart::create([
                    'user_id' => $userId,
                    'product_id' => $productId,
                    'quantity' => 1,
                ]);
            }
        } else {
            if ($cart->quantity < $totalStock) {
                $cart->increment('quantity');
            }
        }
    }

    if ($request->action === 'decrease' && $cart) {
        if ($cart->quantity > 1) {
            $cart->decrement('quantity');
        } else {
            $cart->delete();
        }
    }

    return response()->json([
        'status' => true,
        'message' => 'Cart updated successfully',
    ]);
}

    /* ======================
       REMOVE ITEM
       ====================== */
    public function remove($cartId)
    {
        Cart::where('id', $cartId)
            ->where('user_id', Auth::id())
            ->delete();

        return response()->json([
            'status' => true,
            'message' => 'Item removed from cart',
        ]);
    }

}
