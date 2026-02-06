@extends('layouts.app')

@section('content')
<h2>Manage Stock</h2>

<form method="POST" action="{{ route('admin.stock.store') }}">
@csrf

<select name="warehouse_id">
@foreach($warehouses as $w)
<option value="{{ $w->id }}">{{ $w->name }}</option>
@endforeach
</select>

<select name="product_id">
@foreach($products as $p)
<option value="{{ $p->id }}">{{ $p->name }}</option>
@endforeach
</select>

<input type="number" name="stock_quantity" placeholder="Stock">

<button>Save</button>
</form>
@endsection
