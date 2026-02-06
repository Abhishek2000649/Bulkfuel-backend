@extends('layouts.app')

@section('content')

<h3>Available Orders</h3>

@if(session('success'))
    <p style="color:green">{{ session('success') }}</p>
@endif

@if($orders->isEmpty())
    <p>No orders available.</p>
@endif

@foreach($orders as $order)
<div class="card">

    <p><b>Order ID:</b> {{ $order->id }}</p>
    <p><b>Customer:</b> {{ $order->user->name }}</p>
    <p><b>Total:</b> ₹{{ $order->total_amount }}</p>

    <h4>Products</h4>
    <ul>
        @foreach($order->items as $item)
            <li>{{ $item->product->name }} × {{ $item->quantity }}</li>
        @endforeach
    </ul>

    {{-- Accept / Reject --}}
    <form method="POST" action="/delivery/accept/{{ $order->delivery->id }}" style="display:inline">
        @csrf
        <button class="btn btn-success">Accept</button>
    </form>

    <form method="POST" action="/delivery/reject/{{ $order->delivery->id }}" style="display:inline">
        @csrf
        <button class="btn btn-danger">Reject</button>
    </form>

</div>
@endforeach

@endsection
