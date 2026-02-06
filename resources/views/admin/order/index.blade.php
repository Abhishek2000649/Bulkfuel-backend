@extends('layouts.app')

@section('content')

<h2>All Orders</h2>
<hr>

@if(session('success'))
<p style="color:green">{{ session('success') }}</p>
@endif

@foreach($orders as $order)
<div style="border:1px solid #ccc;padding:15px;margin-bottom:20px">

    <p><b>Order ID:</b> {{ $order->id }}</p>
    <p><b>User:</b> {{ $order->user->name }} ({{ $order->user->email }})</p>
    <p><b>Date: </b>{{ $order->created_at->format('d M Y') }} </p>
    <p><b>Time: </b>{{ $order->created_at->format('h:i A')  }} </p>
    <p><b>Total:</b> ₹{{ $order->total_amount }}</p>
    <p><b>Current Status:</b> {{ $order->status }}</p>

    <p>
        <b>Address:</b>
        {{ $order->address->address ?? '' }},
        {{ $order->address->city ?? '' }} -
        {{ $order->address->pincode ?? '' }}
    </p>

    <h4>Order Items</h4>
    <table border="1" width="100%" cellpadding="8">
        <tr>
            <th>Product</th>
            <th>Price</th>
            <th>Qty</th>
            <th>Subtotal</th>
        </tr>

        @foreach($order->items as $item)
        <tr>
            <td>{{ $item->product->name ?? 'Deleted Product' }}</td>
            <td>₹{{ $item->price }}</td>
            <td>{{ $item->quantity }}</td>
            <td>₹{{ $item->price * $item->quantity }}</td>
        </tr>
        @endforeach
    </table>

    <br>

    {{-- Status Update --}}
    <form method="POST" action="{{ route('admin.orders.status', $order->id) }}">
        @csrf
        <select name="status">
            <option {{ $order->status=='PENDING'?'selected':'' }}>PENDING</option>
            <option {{ $order->status=='CONFIRMED'?'selected':'' }}>CONFIRMED</option>
            <option {{ $order->status=='PACKED'?'selected':'' }}>PACKED</option>
            <option {{ $order->status=='SHIPPED'?'selected':'' }}>SHIPPED</option>
            <option {{ $order->status=='OUT_FOR_DELIVERY'?'selected':'' }}>OUT_FOR_DELIVERY</option>
            <option {{ $order->status=='DELIVERED'?'selected':'' }}>DELIVERED</option>
            <option {{ $order->status=='CANCELLED'?'selected':'' }}>CANCELLED</option>
        </select>

        <button type="submit">Update Status</button>
    </form>

</div>
@endforeach

@endsection
