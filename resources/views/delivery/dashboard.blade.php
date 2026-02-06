@extends('layouts.app')

@section('content')

<h3>My Assigned Deliveries</h3>

@if(session('success'))
    <p style="color:green">{{ session('success') }}</p>
@endif

@if($assigned->isEmpty())
    <p>No active deliveries.</p>
@endif

@foreach($assigned as $delivery)
<div class="card">
    

    <p><b>Order ID:</b> {{ $delivery->order->id }}</p>
    <p><b>Customer:</b> {{ $delivery->order->user->name }}</p>
    <p><b>Status:</b> {{ $delivery->order_status }}</p>
    <p><b>Payment Mode:</b> {{ $delivery->order->payment_method }}</p>

    <p><b>Address:</b><br>
        {{ $delivery->order->address }},
        {{ $delivery->order->city }},
        {{ $delivery->order->state }} -
        {{ $delivery->order->pincode }}
    </p>

    <h4>Products</h4>
    <ul>
        @foreach($delivery->order->items as $item)
            <li>{{ $item->product->name }} × {{ $item->quantity }}</li>
        @endforeach
    </ul>

    {{-- Actions --}}
    @if($delivery->order_status == 'OUT_FOR_DELIVERY')

        <form method="POST" action="/delivery/delivered/{{ $delivery->id }}">
            @csrf
            <button class="btn btn-success">Mark Delivered</button>
        </form>

        <br>

        <form method="POST" action="/delivery/cancel/{{ $delivery->id }}">
            @csrf
            <input type="text" name="cancel_reason" placeholder="Cancel reason" required>
            <button class="btn btn-danger">Cancel</button>
        </form>

    @endif

</div>
@endforeach

@endsection
