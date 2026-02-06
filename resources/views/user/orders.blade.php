@extends('layouts.app')

@section('content')

<h2>My Orders</h2>
<hr>

@if($orders->isEmpty())
    <p>No orders found.</p>
@else

@foreach($orders as $order)

@php
    // 🔥 FLAGS PER ORDER (NOT GLOBAL)
    $showDeliveryStatus = false;
    $showOutForDelivery = false;
    $showDeliveredAt = false;
    $showCancelledAt = false;
    $showCancelReason = false;

    if ($order->delivery?->order_status) {
        $showDeliveryStatus = true;
    }
    if ($order->delivery?->out_for_delivery_at) {
        $showOutForDelivery = true;
    }
    if ($order->delivery?->delivered_at) {
        $showDeliveredAt = true;
    }
    if ($order->delivery?->cancelled_at) {
        $showCancelledAt = true;
    }
    if ($order->delivery?->cancel_reason) {
        $showCancelReason = true;
    }
@endphp

<div style="border:1px solid #ccc;padding:15px;margin-bottom:20px">

    <p><b>Order ID:</b> {{ $order->id }}</p>
    <p><b>Status:</b> {{ $order->status }}</p>
    <p><b>Total:</b> ₹{{ $order->total_amount }}</p>

    <h4>Items:</h4>

    <table border="1" width="100%" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Qty</th>
                <th>Date</th>
                <th>Time</th>

                @if($showDeliveryStatus)
                    <th>Delivery Status</th>
                @endif

                @if($showOutForDelivery)
                    <th>Out For Delivery Date</th>
                    <th>Out For Delivery Time</th>
                @endif

                @if($showDeliveredAt)
                    <th>Delivered At Date</th>
                    <th>Delivered At Time</th>
                @endif

                @if($showCancelledAt)
                    <th>Cancelled At Date</th>
                    <th>Cancelled At Time</th>
                @endif

                @if($showCancelReason)
                    <th>Cancel Reason</th>
                @endif

                <th>Subtotal</th>
            </tr>
        </thead>

        <tbody>
        @foreach($order->items as $item)
            <tr>
                <td>{{ $item->product->name ?? 'Deleted Product' }}</td>
                <td>₹{{ $item->price }}</td>
                <td>{{ $item->quantity }}</td>

                <td>{{ $item->created_at->format('d-m-Y') }}</td>
                <td>{{ $item->created_at->format('h:i A') }}</td>

                @if($showDeliveryStatus)
                    <td>{{ $order->delivery->order_status }}</td>
                @endif

                @if($showOutForDelivery)
                    <td>{{ $order->delivery->out_for_delivery_at->format('d-m-Y') }} </td>
                    <td>{{ $order->delivery->out_for_delivery_at->format('h:i A') }}</td>
                @endif

                @if($showDeliveredAt)
                    <td>{{ $order->delivery->delivered_at->format('d-m-Y') }}</td>
                    <td>{{ $order->delivery->delivered_at->format('h:i A') }}</td>
                @endif

                @if($showCancelledAt)
                    <td>{{ $order->delivery->cancelled_at->format('d-m-Y') }}</td>
                    <td>{{ $order->delivery->cancelled_at->format('h:i A') }}</td>
                @endif

                @if($showCancelReason)
                    <td>{{ $order->delivery->cancel_reason }}</td>
                @endif

                <td>₹{{ $item->price * $item->quantity }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

</div>
@endforeach

@endif

@endsection
