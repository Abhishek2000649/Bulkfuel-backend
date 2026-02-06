@extends('layouts.app')

@section('content')

<h2>Checkout</h2>

<hr>

{{-- CART SUMMARY --}}
<h3>Order Summary</h3>
@if(session('error'))
    <p style="color:red">{{ session('error') }}</p>
@endif
<table border="1" width="100%" cellpadding="10">
    <tr>
        <th>Product</th>
        <th>Price</th>
        <th>Qty</th>
        <th>Subtotal</th>
    </tr>

    @foreach($cartItems as $item)
    <tr>
        <td>{{ $item->product->name }}</td>
        <td>₹ {{ $item->product->price }}</td>
        <td>{{ $item->quantity }}</td>
        <td>₹ {{ $item->product->price * $item->quantity }}</td>
    </tr>
    @endforeach
</table>

<h3>Total Amount: ₹ {{ $totalAmount }}</h3>

<hr>

{{-- USER + ADDRESS + PAYMENT --}}
<h3>Delivery Details</h3>

<form action="{{ route('user.place.order') }}" method="POST">
    @csrf

    <p>
        <strong>Name:</strong> {{ $user->name }} <br>
        <strong>Email:</strong> {{ $user->email }}
    </p>
    @foreach($cartIds as $cid)
    <input type="hidden" name="cart_ids[]" value="{{ $cid }}">
@endforeach

    <label>Address</label><br>
    <textarea name="address" required>{{ $user_address->address ?? '' }}</textarea><br><br>

    <label>City</label><br>
    <input type="text" name="city" value="{{ $user_address->city ?? '' }}" required><br><br>

    <label>State</label><br>
    <input type="text" name="state" value="{{ $user_address->state ?? '' }}" required><br><br>

    <label>Pincode</label><br>
    <input type="text" name="pincode" value="{{ $user_address->pincode ?? '' }}" required><br><br>

    <label>Payment Method</label><br>
    <input type="radio" name="payment_method" value="COD" checked> COD<br>
<input type="radio" name="payment_method" value="online"> Online
<br>

    <button type="submit" style="padding:10px 20px;">
        Place Order
    </button>
</form>

@endsection
