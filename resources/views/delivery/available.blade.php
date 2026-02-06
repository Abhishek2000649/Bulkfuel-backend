@extends('layouts.app')

@section('content')

<h3>Available Orders for Delivery</h3>
<hr>

{{-- Success / Error Messages --}}
@if(session('success'))
    <p style="color:green">{{ session('success') }}</p>
@endif

@if(session('error'))
    <p style="color:red">{{ session('error') }}</p>
@endif

{{-- No Orders --}}
@if($orders->isEmpty())
    <p>No available orders right now.</p>
@endif

{{-- Orders List --}}
@foreach($orders as $delivery)
<div style="border:1px solid #ccc;padding:15px;margin-bottom:15px;border-radius:6px">

    <p><b>Order ID:</b> {{ $delivery->order->id }}</p>
    <p><b>Customer:</b> {{ $delivery->order->user->name }}</p>
    <p><b>Phone:</b> {{ $delivery->order->user->phone ?? 'N/A' }}</p>
    <p><b>Payment Mode:</b>{{ $delivery->order->payment_method }}</p>
    <b>Pickup From:</b>
{{ $delivery->order->warehouse->name }},
{{ $delivery->order->warehouse->city }}

    <p><b>Delivery Address:</b><br>
        {{ $delivery->order->address }},
        {{ $delivery->order->city }},
        {{ $delivery->order->state }} - {{ $delivery->order->pincode }}
    </p>

    <h4>Items</h4>
    <ul>
        @foreach($delivery->order->items as $item)
            <li>{{ $item->product->name }} × {{ $item->quantity }}</li>
        @endforeach
    </ul>

    {{-- Accept / Reject --}}
    <div style="margin-top:10px">

        <form method="POST" action="{{ url('/delivery/accept/'.$delivery->id) }}" style="display:inline">
            @csrf
            <button style="background:green;color:#fff;padding:6px 12px;border:none">
                Accept
            </button>
        </form>

        {{-- <form method="POST" action="{{ url('/delivery/reject/'.$delivery->id) }}" style="display:inline">
            @csrf
            <button style="background:red;color:#fff;padding:6px 12px;border:none">
                Reject
            </button>
        </form> --}}

    </div>

</div>
@endforeach

@endsection
