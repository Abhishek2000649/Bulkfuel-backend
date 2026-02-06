@extends('layouts.app')

@section('content')

<h2>Products</h2>

@foreach($products as $p)
<div style="border:1px solid #ccc;padding:15px;margin-bottom:10px">

<h3>{{ $p->name }}</h3>
<p>{{ $p->description }}</p>
<p>Price: ₹{{ $p->price }}</p>
{{-- @if($p->stock > 0) --}}
{{-- <form method="POST" action="{{ route('user.order') }}">
@csrf
<input type="hidden" name="product_id" value="{{ $p->id }}">
<input type="number" name="quantity" min="1" max="{{ $p->stock }}">
<button type="submit">add</button>
</form> --}}
@php
    $totalStock = $p->totalStock(); // 🔥 sum from warehouse_products
@endphp

@if($totalStock == 0)
    <p style="color:red; font-weight:bold;">
         Out of Stock
    </p>
@else
<form action="{{ route('user.cart.update', $p->id) }}" method="POST"
      style="display:flex;gap:5px;align-items:center;">
    @csrf

    @php
        $qty = $cartItems[$p->id] ?? 0;
    @endphp

    <!-- Minus -->
    <button type="submit"
            name="action"
            value="decrease"
            @if($qty == 0) disabled  @endif>
        −
    </button>

    <!-- Quantity -->
    <input type="text"
           value="{{ $qty }}"
           readonly
           style="width:40px;text-align:center;">

    <!-- Plus -->
    <button type="submit"
            name="action"
            value="increase"
              @if($qty >= $totalStock) disabled @endif>
        +
    </button>
</form>
@endif
</div>
@endforeach
<br>
<br>

<a href="{{  route('user.cart')}}" style="background-color:black; border-radius: 6px; padding: 6px; color: white; font-size: 20px; ">Go to cart</a>

@endsection
