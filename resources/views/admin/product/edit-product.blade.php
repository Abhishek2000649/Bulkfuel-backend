@extends('layouts.app')

@section('content')

<h2>Edit Product</h2>

<form method="POST" action="{{route('admin.products.update', $product->id )}}">
    @method('put')
@csrf
<input name="name" value="{{ $product->name }}"><br><br>
<input name="price" value="{{ $product->price }}"><br><br>
<input type="number" name="stock" value="{{ $product->stock }}"><br><br>
<select name="category_id" required>
@foreach($categories as $cat)
    <option value="{{ $cat->id }}"
        {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>
        {{ $cat->name }}
    </option>
@endforeach
</select><br><br>
<textarea name="description">{{ $product->description }}</textarea><br><br>
<button>Update</button>
</form>

@endsection
