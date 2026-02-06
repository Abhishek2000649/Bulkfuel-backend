<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Settlement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeliveryDashboardController extends Controller
{
    public function dashboard()
    {
        $assigned = Delivery::with([
            'order.user',
            'order.warehouses',
            'order.items.product'
        ])
            ->where('delivery_agent_id', Auth::id())
            ->where('order_status', 'OUT_FOR_DELIVERY')
            ->get();

        return response()->json([
            'status' => true,
            'orders' => $assigned
        ], 200);
    }



    public function availableOrders()
    {
        $orders = Delivery::with([
            'order.user',
            'order.warehouses',
            'order.items.product',
        ])
            ->whereNull('delivery_agent_id')
            ->get();

        return response()->json([
            'status' => true,
            'orders' => $orders
        ], 200);
    }
    public function accept($id)
    {
        $delivery = Delivery::findOrFail($id);

        if ($delivery->delivery_agent_id) {
            return response()->json([
                'status' => false,
                'message' => 'Already assigned'
            ], 400);
        }

        $delivery->update([
            'delivery_agent_id' => Auth::id(),
            'delivery_status' => 'ACCEPTED',
            'order_status' => 'OUT_FOR_DELIVERY',
            'out_for_delivery_at' => now(),
        ]);

        // sync order status
        $delivery->order->update([
            'status' => 'OUT_FOR_DELIVERY'
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Delivery accepted successfully'
        ], 200);
    }

    /* ===============================
       MARK DELIVERED
       =============================== */

public function delivered($id)
{
    $delivery = Delivery::findOrFail($id);

    if ($delivery->delivery_agent_id !== Auth::id()) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized'
        ], 403);
    }

    // 1️⃣ Delivery update
    $delivery->update([
        'order_status' => 'DELIVERED',
        'delivered_at' => now(),
    ]);

    $order = $delivery->order;

    // 2️⃣ COD orders
    if ($order->payment_method === 'COD') {

        // 🔍 Find existing pending settlement
        $settlement = Settlement::where('delivery_agent_id', $delivery->delivery_agent_id)
            ->where('status', 'PENDING')
            ->first();

        // 🆕 Create if not exists
        if (!$settlement) {
            $settlement = Settlement::create([
                'delivery_agent_id' => $delivery->delivery_agent_id,
                'total_amount'      => 0,
                'from_date'         => now(),
                'to_date'           => now(),
                'status'            => 'PENDING',
            ]);
        }

        // 🔗 Attach order to settlement
        $order->update([
            'status'            => 'DELIVERED',
            'payment_status'    => 'PAID',
            'settlement_status' => 'PENDING',
            'settlement_id'     => $settlement->id,
        ]);

        // 💰 Update settlement amount
        $settlement->increment('total_amount', $order->total_amount);
        $settlement->update(['to_date' => now()]);

    } 
    // 3️⃣ ONLINE payment
    else {
        $order->update([
            'status'            => 'DELIVERED',
            'payment_status'    => 'PAID',
            'settlement_status' => 'NOT_REQUIRED'
        ]);
    }

    return response()->json([
        'status' => true,
        'message' => 'Order delivered successfully'
    ], 200);
}



    /* ===============================
       CANCEL DELIVERY
       =============================== */
    public function cancel(Request $request, $id)
    {
        $request->validate([
            'cancel_reason' => 'required|string'
        ]);

        $delivery = Delivery::findOrFail($id);

        if ($delivery->delivery_agent_id !== Auth::id()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $delivery->update([
            'order_status' => 'CANCELLED',
            'cancel_reason' => $request->cancel_reason,
            'cancelled_at' => now(),
        ]);

        $delivery->order->update([
            'status' => 'CANCELLED'
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Delivery cancelled successfully'
        ], 200);
    }

    public function history()
    {
        $history = Delivery::with([
            'order.user',
            'order.warehouses',
            'order.items.product'
        ])
            ->where('delivery_agent_id', Auth::id())
            ->whereIn('order_status', ['DELIVERED', 'CANCELLED'])
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'orders' => $history
        ], 200);
    }

}
