<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Settlement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
class DeliveryDashboardController extends Controller
{
  

public function dashboard()
{
    try {

        
        if (!Auth::check()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Please login first.'
            ], 401);
        }

        
        $assigned = Delivery::with([
            'order.user',
            'order.warehouses',
            'order.items.product',
            'order.orderAddress'
        ])
        ->where('delivery_agent_id', Auth::id())
        ->where('order_status', 'OUT_FOR_DELIVERY')
        ->get();

        return response()->json([
            'status' => true,
            'message' => 'Dashboard data fetched successfully.',
            'orders' => $assigned
        ], 200);

    } 

    
    catch (ModelNotFoundException $e) {
        return response()->json([
            'status' => false,
            'message' => 'Data not found.',
            'error' => $e->getMessage()
        ], 404);
    }

    
    catch (QueryException $e) {
        return response()->json([
            'status' => false,
            'message' => 'Database error occurred.',
            'error' => $e->getMessage()
        ], 500);
    }

    
    catch (HttpException $e) {
        return response()->json([
            'status' => false,
            'message' => $e->getMessage()
        ], $e->getStatusCode());
    }

    
    catch (Throwable $e) {
        return response()->json([
            'status' => false,
            'message' => 'Something went wrong.',
            'error' => $e->getMessage()
        ], 500);
    }
}



    public function availableOrders()
{
    try {

        
        if (!Auth::check()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Please login first.'
            ], 401);
        }

        
        $orders = Delivery::with([
            'order.user',
            'order.warehouses',
            'order.items.product',
            'order.orderAddress'
        ])
        ->whereNull('delivery_agent_id')
        ->get();

        
        if ($orders->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'No available orders found.',
                'orders' => []
            ], 200);
        }

        return response()->json([
            'status' => true,
            'message' => 'Available orders fetched successfully.',
            'orders' => $orders
        ], 200);

    }

   
    catch (ModelNotFoundException $e) {
        return response()->json([
            'status' => false,
            'message' => 'Data not found.',
            'error' => $e->getMessage()
        ], 404);
    }

   
    catch (QueryException $e) {
        return response()->json([
            'status' => false,
            'message' => 'Database error occurred.',
            'error' => $e->getMessage()
        ], 500);
    }

   
    catch (HttpException $e) {
        return response()->json([
            'status' => false,
            'message' => $e->getMessage()
        ], $e->getStatusCode());
    }

    
    catch (Throwable $e) {
        return response()->json([
            'status' => false,
            'message' => 'Something went wrong.',
            'error' => $e->getMessage()
        ], 500);
    }
}
  public function accept($id)
{
    try {

        
        if (!Auth::check()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Please login first.'
            ], 401);
        }

        
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

      
        $delivery->order->update([
            'status' => 'OUT_FOR_DELIVERY'
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Delivery accepted successfully'
        ], 200);
    }

   
    catch (ModelNotFoundException $e) {
        return response()->json([
            'status' => false,
            'message' => 'Delivery not found.',
            'error' => $e->getMessage()
        ], 404);
    }

   
    catch (QueryException $e) {
        return response()->json([
            'status' => false,
            'message' => 'Database error occurred.',
            'error' => $e->getMessage()
        ], 500);
    }

    
    catch (HttpException $e) {
        return response()->json([
            'status' => false,
            'message' => $e->getMessage()
        ], $e->getStatusCode());
    }

   
    catch (Throwable $e) {
        return response()->json([
            'status' => false,
            'message' => 'Something went wrong.',
            'error' => $e->getMessage()
        ], 500);
    }
}
    

public function delivered($id)
{
    try {

        
        if (!Auth::check()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Please login first.'
            ], 401);
        }

        $delivery = Delivery::findOrFail($id);

        
        if ($delivery->delivery_agent_id !== Auth::id()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        
        $delivery->update([
            'order_status' => 'DELIVERED',
            'delivered_at' => now(),
        ]);

        $order = $delivery->order;

        
        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Associated order not found.'
            ], 404);
        }

       
        if ($order->payment_method === 'COD') {

            $settlement = Settlement::where('delivery_agent_id', $delivery->delivery_agent_id)
                ->where('status', 'PENDING')
                ->first();

            if (!$settlement) {
                $settlement = Settlement::create([
                    'delivery_agent_id' => $delivery->delivery_agent_id,
                    'total_amount'      => 0,
                    'from_date'         => now(),
                    'to_date'           => now(),
                    'status'            => 'PENDING',
                ]);
            }

            $order->update([
                'status'            => 'DELIVERED',
                'payment_status'    => 'PAID',
                'settlement_status' => 'PENDING',
                'settlement_id'     => $settlement->id,
            ]);

            $settlement->increment('total_amount', $order->total_amount);
            $settlement->update(['to_date' => now()]);
        }
        
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

    
    catch (ModelNotFoundException $e) {
        return response()->json([
            'status' => false,
            'message' => 'Delivery not found.',
            'error' => $e->getMessage()
        ], 404);
    }

    
    catch (QueryException $e) {
        return response()->json([
            'status' => false,
            'message' => 'Database error occurred.',
            'error' => $e->getMessage()
        ], 500);
    }

    
    catch (HttpException $e) {
        return response()->json([
            'status' => false,
            'message' => $e->getMessage()
        ], $e->getStatusCode());
    }

    catch (Throwable $e) {
        return response()->json([
            'status' => false,
            'message' => 'Something went wrong.',
            'error' => $e->getMessage()
        ], 500);
    }
}


   
   public function cancel(Request $request, $id)
{
    try {

        
        if (!Auth::check()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Please login first.'
            ], 401);
        }

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
            'cancelled_at'  => now(),
        ]);

        
        if (!$delivery->order) {
            return response()->json([
                'status' => false,
                'message' => 'Associated order not found.'
            ], 404);
        }

       
        $delivery->order->update([
            'status' => 'CANCELLED'
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Delivery cancelled successfully'
        ], 200);
    }

    
    catch (ValidationException $e) {
        return response()->json([
            'status' => false,
            'message' => 'Validation failed.',
            'errors' => $e->errors()
        ], 422);
    }

   
    catch (ModelNotFoundException $e) {
        return response()->json([
            'status' => false,
            'message' => 'Delivery not found.',
            'error' => $e->getMessage()
        ], 404);
    }

  
    catch (QueryException $e) {
        return response()->json([
            'status' => false,
            'message' => 'Database error occurred.',
            'error' => $e->getMessage()
        ], 500);
    }

    
    catch (HttpException $e) {
        return response()->json([
            'status' => false,
            'message' => $e->getMessage()
        ], $e->getStatusCode());
    }

   
    catch (Throwable $e) {
        return response()->json([
            'status' => false,
            'message' => 'Something went wrong.',
            'error' => $e->getMessage()
        ], 500);
    }
}


    
   public function history()
{
    try {

        
        if (!Auth::check()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Please login first.'
            ], 401);
        }

        $history = Delivery::with([
            'order.user',
            'order.warehouses',
            'order.items.product',
            'order.orderAddress'
        ])
        ->where('delivery_agent_id', Auth::id())
        ->whereIn('order_status', ['DELIVERED', 'CANCELLED'])
        ->orderBy('updated_at', 'desc')
        ->get();

        return response()->json([
            'status' => true,
            'message' => 'Delivery history fetched successfully.',
            'orders' => $history
        ], 200);
    }

  
    catch (ModelNotFoundException $e) {
        return response()->json([
            'status' => false,
            'message' => 'Data not found.',
            'error' => $e->getMessage()
        ], 404);
    }

   
    catch (QueryException $e) {
        return response()->json([
            'status' => false,
            'message' => 'Database error occurred.',
            'error' => $e->getMessage()
        ], 500);
    }

   
    catch (HttpException $e) {
        return response()->json([
            'status' => false,
            'message' => $e->getMessage()
        ], $e->getStatusCode());
    }

    
    catch (Throwable $e) {
        return response()->json([
            'status' => false,
            'message' => 'Something went wrong.',
            'error' => $e->getMessage()
        ], 500);
    }
}

}
