<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Delivery;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
   public function index()
{
   return response()->json(
    Order::with(['user.address', 'items.product'])
        ->whereIn('status', ['PENDING', 'CONFIRMED', 'PACKED'])
        ->latest()
        ->get()
);

}

    public function updateStatus(Request $request, $id)
    {
        
        $order = Order::findOrFail($id);
            $order->status = $request->status;
            $order->save();
            if ($request->status === 'SHIPPED') {
                if (!$order->delivery) {
                    Delivery::create([
                    'order_id' => $order->id,
                ]);
            }
        }
        return response()->json(['status' => true]);
    }

  public function history()
{
    $orders = Order::with([
        'user:id,name,email',
        'items.product:id,name,price',
        'items.warehouse:id,name,city,state,pincode',
        'delivery.agent:id,name'
    ])
    ->latest()
    ->get();

    return response()->json([
        'status' => true,
        'orders' => $orders
    ]);
}



}
