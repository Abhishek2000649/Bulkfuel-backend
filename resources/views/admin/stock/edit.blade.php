@extends('layouts.app')

@section('content')
<h2>Edit Stock</h2>

<form method="POST" action="{{ route('admin.stock.update', $wareHouseProduct->id) }}">
@csrf
@method('PUT')

<select name="warehouse_id" required>
@foreach($warehouses as $w)
    <option value="{{ $w->id }}"
        {{ $w->id == $wareHouseProduct->warehouse_id ? 'selected' : '' }}>
        {{ $w->name }}
    </option>
@endforeach
</select>

<select name="product_id" required>
@foreach($products as $p)
    <option value="{{ $p->id }}"
        {{ $p->id == $wareHouseProduct->product_id ? 'selected' : '' }}>
        {{ $p->name }}
    </option>
@endforeach
</select>

<input type="number" name="stock_quantity"
       value="{{ $wareHouseProduct->stock_quantity }}" required>

<button type="submit">Update</button>
</form>
@endsection
