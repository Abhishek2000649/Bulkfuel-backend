<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Address;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class UserController extends Controller
{
    
//    public function home()
// {
//     $products = Product::with('warehouseProducts')->get();

//     $cartItems = [];

//     if (Auth::check()) {
//         $cartItems = Cart::where('user_id', Auth::id())
//                          ->pluck('quantity','product_id')
//                          ->toArray();
//     }

//     return view('user.home', compact('products','cartItems'));
// }


    
//     public function placeOrder(Request $request)
//     {
        
//         $request->validate([
//             'product_id' => 'required|exists:products,id',
//             'quantity'   => 'required|numeric|min:1'
//         ]);

        
//         $product = Product::find($request->product_id);

        
//         if ($request->quantity > $product->stock) {
//             return back()->with('error', 'Not enough stock available');
//         }

        
//         $order = Order::create([
//             'user_id'      => auth()->id(),
//             'product_id'   => $product->id,
//             'quantity'     => $request->quantity,
//             'total_amount' => $product->price * $request->quantity,
//             'status'       => 'PENDING'
//         ]);

        
//         $product->decrement('stock', $request->quantity);

        
//         Payment::create([
//             'order_id' => $order->id,
//             'method'   => 'COD',
//             'status'   => 'SUCCESS'
//         ]);

//         return redirect('user/my-orders')->with('success', 'Order placed successfully');
//     }

//    public function orders()
// {
//     $orders = Order::where('user_id', auth()->id())
//         ->with([
//             'items.product',   // order items + product
//             'delivery'         // ✅ delivery details
//         ])
//         ->latest()
//         ->get();

//     return view('user.orders', compact('orders'));
// }


    
//     public function profile()
//     {
//         return view('user.profile');
//     }

    
//     public function saveProfile(Request $request)
//     {
//         $request->validate([
//             'address' => 'required',
//             'city'    => 'required',
//             'state'   => 'required',
//             'pincode' => 'required'
//         ]);

//         Address::updateOrCreate(
//             ['user_id' => auth()->id()],
//             [
//                 'address' => $request->address,
//                 'city'    => $request->city,
//                 'state'   => $request->state,
//                 'pincode' => $request->pincode
//             ]
//         );

//         return back()->with('success', 'Profile updated successfully');
//     }





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

        // if (Auth::check()) {
        //     $cartItems = Cart::where('user_id', Auth::id())
        //         ->pluck('quantity', 'product_id')
        //         ->toArray();
        // }

        return response()->json([
            'status' => true,
            'products' => $products,
            'cartItems' => $cartItems,
        ]);
    }

    /* =========================
       PLACE ORDER (FROM CART)
       ========================= */
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

    /* =========================
       USER ORDERS
       ========================= */
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

    /* =========================
       USER PROFILE
       ========================= */
    public function profile()
    {
        return response()->json([
            'status' => true,
            'user' => Auth::user()->load('address'),
        ]);
    }
//   public function saveProfile(Request $request)
// {
//     $user = Auth::user();

//     /* ============================
//        VALIDATION
//     ============================ */
//     $request->validate([
//         'name'    => 'required|string|max:255',
//         'email'   => 'required|email|unique:users,email,' . $user->id,

//         'address' => 'required|string',
//         'city'    => 'required|string',
//         'state'   => 'required|string',
//         'pincode' => 'required|string',

//         // password fields (optional)
//         'current_password' => 'nullable|required_with:password',
//         'password' => 'nullable|min:6|confirmed',
//     ]);

//     /* ============================
//        UPDATE USER (NAME, EMAIL)
//     ============================ */
//     $user->update([
//         'name'  => $request->name,
//         'email' => $request->email,
//     ]);

//     /* ============================
//        UPDATE PASSWORD (OPTIONAL)
//     ============================ */
//     if ($request->filled('password')) {

//         if (!Hash::check($request->current_password, $user->password)) {
//             throw ValidationException::withMessages([
//                 'current_password' => ['Current password is incorrect'],
//             ]);
//         }

//         $user->update([
//             'password' => Hash::make($request->password),
//         ]);
//     }

//     /* ============================
//        UPDATE ADDRESS
//     ============================ */
//     $address = Address::updateOrCreate(
//         ['user_id' => $user->id],
//         [
//             'address' => $request->address,
//             'city'    => $request->city,
//             'state'   => $request->state,
//             'pincode' => $request->pincode,
//         ]
//     );

//     return response()->json([
//         'status'  => true,
//         'message' => 'Profile updated successfully',
//         'user'    => $user->load('address'),
//     ]);
// }

public function updateBasic(Request $request)
{
    $user = Auth::user();

    $request->validate([
        'name'  => 'required|string|max:255',
        'email' => 'required|email|unique:users,email,' . $user->id,
    ]);

    $user->update([
        'name'  => $request->name,
        'email' => $request->email,
    ]);

    return response()->json([
        'status'  => true,
        'message' => 'Name & email updated successfully',
        'user'    => $user,
    ]);
}
public function updateAddress(Request $request)
{
    $user = Auth::user();

    $request->validate([
        'address' => 'required|string',
        'city'    => 'required|string',
        'state'   => 'required|string',
        'pincode' => 'required|string',
    ]);

    $address = Address::updateOrCreate(
        ['user_id' => $user->id],
        [
            'address' => $request->address,
            'city'    => $request->city,
            'state'   => $request->state,
            'pincode' => $request->pincode,
        ]
    );

    return response()->json([
        'status'  => true,
        'message' => 'Address updated successfully',
        'address' => $address,
    ]);
}
public function updatePassword(Request $request)
{
    $user = Auth::user();

    $request->validate([
        'current_password' => 'required',
        'password' => 'required|min:6|confirmed',
    ]);

    // 🔐 check old password
    if (!Hash::check($request->current_password, $user->password)) {
        throw ValidationException::withMessages([
            'current_password' => ['Current password is incorrect'],
        ]);
    }

    $user->update([
        'password' => Hash::make($request->password),
    ]);

    return response()->json([
        'status'  => true,
        'message' => 'Password updated successfully',
    ]);
}

}
