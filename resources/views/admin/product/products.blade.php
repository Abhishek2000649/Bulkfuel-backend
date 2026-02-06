@extends('layouts.app')

@section('content')

<h2>Add New Product</h2>

<a href="{{  route('admin.products.add')}}" style="color: black">Add product</a>

<h2>All Products</h2>

<table border="1" cellpadding="10" cellSpacing="0" width="100%">
<tr>
    <th>Name</th>
    <th>Price</th>
    <th>Date</th>
    <th>Time</th>
    <th>Stock</th>
    <th>category</th>
    <th>Action</th>
</tr>

@foreach($products as $p)
<tr>
    <td>{{ $p->name }}</td>
    <td>₹{{ $p->price }}</td>
    <td>{{ $p->created_at->format('d-m-Y') }}</td>
    <td>{{ $p->created_at->format('h:i A') }}</td>
    <td>{{ $p->stock }}</td>
    <td>{{ $p->category->name }}</td>
    <td>
        <a href="{{route('admin.products.edit', $p->id) }}" style="color: black">Edit</a> |
        <form method="POST" action="{{ route('admin.products.delete', $p->id) }}" style="display:inline">
    @csrf
    @method('DELETE')
    <button type="submit">Delete</button>
</form>

    </td>
</tr>
@endforeach
</table>

@endsection
