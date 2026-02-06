@extends('layouts.app')

@section('content')

<h2>Add New Product</h2>

<form method="POST" action="{{ route('admin.products.store') }}">
@csrf
<input name="name" placeholder="Product Name"><br><br>
<input name="price" placeholder="Price"><br><br>
<input type="number" name="stock" placeholder="Stock"><br><br>
<select name="category_id" required>
@foreach($categories as $cat)
    <option value="{{ $cat->id }}">
        {{ $cat->name }}
    </option>
@endforeach
</select><br><br>
<textarea name="description" placeholder="Description"></textarea><br><br>
<button>Add Product</button>
</form>
@endsection
