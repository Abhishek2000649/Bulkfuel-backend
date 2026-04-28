<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Delivery;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

class AdminOrderController extends Controller
{
   public function index()
{
    try {

        $orders = Order::with(['orderAddress', 'items.product','items.warehouse'])
            ->whereIn('status', ['PENDING', 'CONFIRMED', 'PACKED'])
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Orders fetched successfully.',
            'data' => $orders
        ], 200);

    } catch (ModelNotFoundException $e) {

        return response()->json([
            'status' => false,
            'message' => 'Orders not found.',
            'error' => $e->getMessage()
        ], 404);

    } catch (QueryException $e) {

        return response()->json([
            'status' => false,
            'message' => 'Database query error.',
            'error' => $e->getMessage()
        ], 500);

    } catch (HttpException $e) {

        return response()->json([
            'status' => false,
            'message' => 'HTTP error occurred.',
            'error' => $e->getMessage()
        ], $e->getStatusCode());

    } catch (Throwable $e) {

        return response()->json([
            'status' => false,
            'message' => 'Something went wrong.',
            'error' => $e->getMessage()
        ], 500);
    }
}

  

public function updateStatus(Request $request, $id)
{
    try {

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

        return response()->json([
            'status' => true,
            'message' => 'Order status updated successfully.',
            'data' => $order
        ], 200);

    } catch (ModelNotFoundException $e) {

        return response()->json([
            'status' => false,
            'message' => 'Order not found.',
            'error' => $e->getMessage()
        ], 404);

    } catch (QueryException $e) {

        return response()->json([
            'status' => false,
            'message' => 'Database error occurred.',
            'error' => $e->getMessage()
        ], 500);

    } catch (HttpException $e) {

        return response()->json([
            'status' => false,
            'message' => 'HTTP error occurred.',
            'error' => $e->getMessage()
        ], $e->getStatusCode());

    } catch (Throwable $e) {

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

        $orders = Order::with([
            'user:id,name,email',
            'items.product:id,name,price',
            'items.warehouse:id,name,city,state,pincode',
            'delivery.agent:id,name',
            'orderAddress',
        ])
        ->latest()
        ->get();

        if ($orders->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No order history found.',
                'data' => []
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Order history fetched successfully.',
            'data' => $orders
        ], 200);

    } catch (ModelNotFoundException $e) {

        return response()->json([
            'status' => false,
            'message' => 'Orders not found.',
            'error' => $e->getMessage()
        ], 404);

    } catch (QueryException $e) {

        return response()->json([
            'status' => false,
            'message' => 'Database query error.',
            'error' => $e->getMessage()
        ], 500);

    } catch (HttpException $e) {

        return response()->json([
            'status' => false,
            'message' => 'HTTP error occurred.',
            'error' => $e->getMessage()
        ], $e->getStatusCode());

    } catch (Throwable $e) {

        return response()->json([
            'status' => false,
            'message' => 'Something went wrong.',
            'error' => $e->getMessage()
        ], 500);
    }
}
}
