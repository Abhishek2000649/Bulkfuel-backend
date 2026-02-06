@extends('layouts.app')

@section('content')

<h2>My Cart</h2>

@if(session('error'))
    <p style="color:red">{{ session('error') }}</p>
@endif

@if($cartItems->isEmpty())
    <p>Your cart is empty</p>
@else

{{-- ================= CHECKOUT FORM ================= --}}
<form action="{{ route('user.checkout') }}" method="GET">
@csrf

<table border="1" width="100%" cellpadding="10">
    <thead>
        <tr>
            <th>Select</th>
            <th>Product</th>
            <th>Price</th>
            <th>Qty</th>
            <th>Subtotal</th>
            <th>Actions</th>
        </tr>
    </thead>

    <tbody>
    @php $grandTotal = 0; @endphp

    @foreach($cartItems as $item)
        @php
            $subtotal = $item->product->price * $item->quantity;
            $grandTotal += $subtotal;
        @endphp

        <tr>
            {{-- Select --}}
            <td align="center">
                <input type="checkbox"
                       name="cart_ids[]"
                       value="{{ $item->id }}"
                       class="cart-checkbox"
                       data-price="{{ $item->product->price }}"
                       data-qty="{{ $item->quantity }}">
            </td>

            {{-- Product --}}
            <td>{{ $item->product->name }}</td>

            {{-- Price --}}
            <td>₹ {{ $item->product->price }}</td>

            {{-- Quantity --}}
            <td>{{ $item->quantity }}</td>

            {{-- Subtotal --}}
            <td>₹ {{ $subtotal }}</td>

            {{-- Actions --}}
            <td>

                {{-- DECREASE --}}
                <form action="{{ route('user.cart.update', $item->product_id) }}"
                      method="POST" style="display:inline">
                    @csrf
                    <button name="action" value="decrease">−</button>
                </form>

                {{-- INCREASE --}}
                <form action="{{ route('user.cart.update', $item->product_id) }}"
                      method="POST" style="display:inline">
                    @csrf
                    <button name="action" value="increase">+</button>
                </form>

                {{-- REMOVE --}}
                <form action="{{ route('user.cart.remove', $item->id) }}"
                      method="POST" style="display:inline">
                    @csrf
                    @method('DELETE')
                    <button style="color:red">Remove</button>
                </form>

            </td>
        </tr>
    @endforeach
    </tbody>
</table>

<br>

<h3>
    Total Selected Amount: ₹ <span id="selectedTotal">0</span>
</h3>

<br>

<button type="submit"
        style="background:black;color:white;
               padding:10px 20px;
               border-radius:6px;
               font-size:16px;">
    Checkout Selected Products
</button>

</form>
@endif

{{-- ================= JS ================= --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    const checkboxes = document.querySelectorAll('.cart-checkbox');
    const totalSpan = document.getElementById('selectedTotal');

    function calculateTotal() {
        let total = 0;

        checkboxes.forEach(cb => {
            if (cb.checked) {
                const price = parseFloat(cb.dataset.price);
                const qty   = parseInt(cb.dataset.qty);
                total += price * qty;
            }
        });

        totalSpan.innerText = total;
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', calculateTotal);
    });
});
</script>

@endsection
