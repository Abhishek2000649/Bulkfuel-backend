<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderWarehouse;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\WarehouseProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\BadRequestError;
use Razorpay\Api\Errors\Base;
use Razorpay\Api\Errors\ServerError;

class PaymentController extends Controller
{


    public function createOrder(Request $request)
    {
        try {

            $validated = $request->validate([
                'order_id' => 'required|exists:orders,id'
            ]);

            $order = Order::where('id', $validated['order_id'])
                ->where('user_id', Auth::id())
                ->first();

            if (!$order) {
                return response()->json([
                    'status' => false,
                    'message' => 'Order not found or unauthorized'
                ], 404);
            }

            if ($order->payment_status === 'PAID') {
                return response()->json([
                    'status' => false,
                    'message' => 'Order already paid'
                ], 400);
            }

            if ($order->payment_method !== 'online') {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid payment method for this order'
                ], 400);
            }

            $api = new Api(
                config('services.razorpay.key'),
                config('services.razorpay.secret')
            );

            $razorpayOrder = $api->order->create([
                'receipt' => 'order_' . $order->id,
                'amount' => (int) ($order->total_amount * 100),
                'currency' => 'INR',
                'payment_capture' => 1,
                'notes' => [
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'email' => Auth::user()->email ?? '',
                    'phone' => Auth::user()->phone ?? ''
                ]
            ]);

            DB::beginTransaction();

            $order->update([
                'razorpay_order_id' => $razorpayOrder['id']
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Razorpay order created successfully',
                'data' => [
                    'key' => config('services.razorpay.key'),
                    'amount' => (int) ($order->total_amount * 100),
                    'razorpay_order_id' => $razorpayOrder['id']
                ]
            ]);
        } catch (ValidationException $e) {

            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {

            DB::rollBack();

            if (isset($order)) {
                $this->failOrder($order);
            }

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function verify(Request $request)
    {
        try {
            $request->validate([
                'razorpay_order_id'   => 'required|string',
                'razorpay_payment_id' => 'required|string',
                'razorpay_signature'  => 'required|string',
            ]);
            if (Transaction::where('razorpay_payment_id', $request->razorpay_payment_id)->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Payment already recorded'
                ], 400);
            }



            $order = Order::where('razorpay_order_id', $request->razorpay_order_id)
                ->where('user_id', Auth::id())
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->payment_status === 'PAID') {
                return response()->json([
                    'status' => false,
                    'message' => 'Payment already verified'
                ], 400);
            }

            $api = new Api(
                config('services.razorpay.key'),
                config('services.razorpay.secret')
            );

            $api->utility->verifyPaymentSignature([
                'razorpay_order_id'   => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature'  => $request->razorpay_signature
            ]);

            $payment = $api->payment->fetch($request->razorpay_payment_id);

            if ($payment->status === 'failed') {

                DB::beginTransaction();

                $order->update([
                    'payment_status' => 'FAILED',
                    'status'         => 'CANCELLED',
                ]);

                foreach ($order->items as $item) {

                    WarehouseProduct::where('warehouse_id', $item->warehouse_id)
                        ->where('product_id', $item->product_id)
                        ->where('reserved_quantity', '>=', $item->quantity)
                        ->decrement('reserved_quantity', $item->quantity);
                }
                Transaction::create([
                    'order_id'              => $order->id,
                    'razorpay_order_id'     => $request->razorpay_order_id,
                    'razorpay_payment_id'   => $request->razorpay_payment_id,
                    'razorpay_signature'    => $request->razorpay_signature,
                    'amount'                => $order->total_amount,
                    'status'                => 'FAILED',
                    'method'                => $payment->method ?? null
                ]);

                DB::commit();

                return response()->json([
                    'status' => false,
                    'message' => 'Payment failed and stock released'
                ], 400);
            }

            if ($payment->status !== 'captured') {
                return response()->json([
                    'status' => false,
                    'message' => 'Payment not captured'
                ], 400);
            }

            DB::beginTransaction();

            $order->update([
                'payment_status' => 'PAID',
                'status'         => 'CONFIRMED',
            ]);
            $mappedStatus = match ($payment->status) {
                'captured' => 'PAID',
                'failed'   => 'FAILED',
                default    => 'PENDING',
            };

            Transaction::create([
                'order_id'              => $order->id,
                'razorpay_order_id'     => $request->razorpay_order_id,
                'razorpay_payment_id'   => $request->razorpay_payment_id,
                'razorpay_signature'    => $request->razorpay_signature,
                'amount'                => $order->total_amount,
                'status'                => $mappedStatus,
                'method'                => $payment->method
            ]);

            foreach ($order->items as $item) {

                WarehouseProduct::where('warehouse_id', $item->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->decrement('reserved_quantity', $item->quantity);

                WarehouseProduct::where('warehouse_id', $item->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->decrement('stock_quantity', $item->quantity);
            }
            $orderedProductIds = $order->items->pluck('product_id');

            Cart::where('user_id', $order->user_id)
                ->whereIn('product_id', $orderedProductIds)
                ->delete();

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Payment verified & saved successfully'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'status' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (BadRequestError $e) {

            if ($order) {
                $this->failOrder($order);
            }

            return response()->json([
                'status' => false,
                'message' => 'Invalid payment request'
            ], 400);
        } catch (ServerError $e) {

            if ($order) {
                $this->failOrder($order);
            }

            return response()->json([
                'status' => false,
                'message' => 'Razorpay server error',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {

            DB::rollBack();

            if ($order) {
                $this->failOrder($order);
            }

            return response()->json([
                'status' => false,
                'message' => 'Payment verification failed',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    private function failOrder($order, $reason = 'FAILED')
    {
        if (!$order) return;

        if ($order->payment_status === 'PAID') {
            return; // already successful, don't override
        }

        DB::beginTransaction();

        try {
            $order->update([
                'payment_status' => 'FAILED',
                'status' => 'CANCELLED',
            ]);

            foreach ($order->items as $item) {
                WarehouseProduct::where('warehouse_id', $item->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->where('reserved_quantity', '>=', $item->quantity)
                    ->decrement('reserved_quantity', $item->quantity);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
        }
    }
    public function handleWebhook(Request $request)
    {
        $secret = config('services.razorpay.webhook_secret');

        // 🔐 Verify signature
        $signature = $request->header('X-Razorpay-Signature');
        $generated = hash_hmac('sha256', $request->getContent(), $secret);

        if (!hash_equals($generated, $signature)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid signature'
            ], 400);
        }

        $payload = $request->all();

        $event = $payload['event'] ?? null;

        // 🔒 Safe extraction
        $orderId   = data_get($payload, 'payload.payment.entity.order_id');
        $paymentId = data_get($payload, 'payload.payment.entity.id');
        $status    = data_get($payload, 'payload.payment.entity.status');
        $method    = data_get($payload, 'payload.payment.entity.method');

        if (!$orderId) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid payload'
            ], 400);
        }

        try {

            DB::transaction(function () use ($orderId, $paymentId, $status, $method, $event) {

                $order = Order::where('razorpay_order_id', $orderId)
                    ->lockForUpdate()
                    ->first();

                if (!$order) return;

                // ❗ Prevent duplicate execution
                if ($order->payment_status !== 'PENDING') return;

                // ============================
                // ❌ PAYMENT FAILED → DELETE
                // ============================
                if ($event === 'payment.failed' || $status === 'failed') {

                    foreach ($order->items as $item) {
                        WarehouseProduct::where('warehouse_id', $item->warehouse_id)
                            ->where('product_id', $item->product_id)
                            ->where('reserved_quantity', '>=', $item->quantity)
                            ->decrement('reserved_quantity', $item->quantity);
                    }

                    // ❗ Avoid duplicate transaction
                    if (!Transaction::where('razorpay_payment_id', $paymentId)->exists()) {
                        Transaction::create([
                            'order_id' => $order->id,
                            'razorpay_order_id' => $orderId,
                            'razorpay_payment_id' => $paymentId,
                            'amount' => $order->total_amount,
                            'status' => 'FAILED',
                            'method' => $method
                        ]);
                    }

                    // 🔥 HARD DELETE
                    $order->items()->delete();
                    OrderWarehouse::where('order_id', $order->id)->delete();
                    OrderAddress::where('id', $order->order_address_id)->delete();
                    $order->delete();
                }

                // ============================
                // ✅ PAYMENT SUCCESS
                // ============================
                if (
                    $event === 'payment.captured' ||
                    $event === 'payment.authorized' ||
                    $status === 'captured'
                ) {

                    $order->update([
                        'payment_status' => 'PAID',
                        'status' => 'CONFIRMED'
                    ]);

                    foreach ($order->items as $item) {

                        WarehouseProduct::where('warehouse_id', $item->warehouse_id)
                            ->where('product_id', $item->product_id)
                            ->decrement('reserved_quantity', $item->quantity);

                        WarehouseProduct::where('warehouse_id', $item->warehouse_id)
                            ->where('product_id', $item->product_id)
                            ->decrement('stock_quantity', $item->quantity);
                    }

                    Cart::where('user_id', $order->user_id)->delete();

                    // ❗ Avoid duplicate transaction
                    if (!Transaction::where('razorpay_payment_id', $paymentId)->exists()) {
                        Transaction::create([
                            'order_id' => $order->id,
                            'razorpay_order_id' => $orderId,
                            'razorpay_payment_id' => $paymentId,
                            'amount' => $order->total_amount,
                            'status' => 'PAID',
                            'method' => $method
                        ]);
                    }
                }
            });

            return response()->json(['status' => true]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => 'Webhook failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
