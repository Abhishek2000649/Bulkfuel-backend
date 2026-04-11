<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Exception;

class CartController extends Controller
{



    public function index()
    {
        try {


            if (!Auth::check()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthorized. Please login first.'
                ], 401);
            }

            $cartItems = Cart::where('user_id', Auth::id())
                ->with(['product' => function ($query) {
                    $query->select('id', 'name', 'price', 'image', 'category_id');
                }])
                ->get()
                ->map(function ($item) {
                    if ($item->product && $item->product->image) {
                        $item->image_url = asset( $item->product->image);
                    } else {
                        $item->image_url = null;
                    }
                    return $item;
                });

            $totalAmount = $cartItems->sum(function ($item) {

                if (!$item->product) {
                    return 0;
                }

                return $item->product->price * ($item->quantity ?? 1);
            });

            return response()->json([
                'status'      => true,
                'cartItems'   => $cartItems,
                'totalAmount' => $totalAmount,
            ], 200);
        } catch (AuthenticationException $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Authentication failed.'
            ], 401);
        } catch (ModelNotFoundException $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Product not found in cart.'
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


    public function update(Request $request, $productId)
    {
        try {


            if (!Auth::check()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthorized. Please login first.'
                ], 401);
            }

            $userId = Auth::id();


            $validated = $request->validate([
                'action' => 'required|in:increase,decrease'
            ]);


            $product = Product::findOrFail($productId);
            $totalStock = $product->totalStock;


            $cart = Cart::where('user_id', $userId)
                ->where('product_id', $productId)
                ->first();


            if ($validated['action'] === 'increase') {

                if ($totalStock <= 0) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Product is out of stock.'
                    ], 400);
                }

                if (!$cart) {

                    Cart::create([
                        'user_id'    => $userId,
                        'product_id' => $productId,
                        'quantity'   => 1,
                    ]);
                } else {

                    if ($cart->quantity >= $totalStock) {
                        return response()->json([
                            'status'  => false,
                            'message' => 'Stock limit reached.'
                        ], 400);
                    }

                    $cart->increment('quantity');
                }
            }


            if ($validated['action'] === 'decrease') {

                if (!$cart) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Cart item not found.'
                    ], 404);
                }

                if ($cart->quantity > 1) {
                    $cart->decrement('quantity');
                } else {
                    $cart->delete();
                }
            }

            return response()->json([
                'status'  => true,
                'message' => 'Cart updated successfully',
            ], 200);
        } catch (ValidationException $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Product not found.'
            ], 404);
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


    public function remove($cartId)
    {
        try {


            if (!Auth::check()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthorized. Please login first.'
                ], 401);
            }


            $cart = Cart::where('id', $cartId)
                ->where('user_id', Auth::id())
                ->first();

            if (!$cart) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Cart item not found.'
                ], 404);
            }


            $cart->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Item removed from cart',
            ], 200);
        } catch (AuthenticationException $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Authentication failed.'
            ], 401);
        } catch (ModelNotFoundException $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Cart item not found.'
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
}
