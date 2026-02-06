@extends('layouts.app')
@section('content')
<a href="{{ route('admin.stock.create') }}" style="color:black">Add Stock</a>
<br>
@if (session('error'))
<p style="color: red;">{{ session('error') }}</p>
    
@endif
<table border="1" cellpadding="10" cellSpacing="0" width="100%">
    <tr>
        <th>Warehouse</th>
        <th>Product</th>
        <th>Stock</th>
        <th>Action</th>
    </tr>

    @foreach($warehouseProducts as $item)
    @if ($item->stock_quantity != 0)
        
    
    <tr>
        <td>{{ $item->warehouse->name }}</td>
        <td>{{ $item->product->name }}</td>
        <td>{{ $item->stock_quantity }}</td>
        <td>
            <a href="{{ route('admin.stock.edit', $item->id) }}" style="color:black;">edit</a>
            <form action="{{ route('admin.stock.delete',$item->id) }}" style="display: inline-block " method="POST">
                @csrf
                <button type="submit" >Delete</button>
            </form>
        </td>
    </tr>
    @endif
    @endforeach
</table>


@endsection