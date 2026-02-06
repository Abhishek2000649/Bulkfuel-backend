@extends('layouts.app')
@section('content')

<form action="{{ route('admin.category.update', $category->id) }}" method="POST">
@csrf
<input type="text" name="name" value="{{ $category->name }}" placeholder="Category name">
<button>Add</button>
</form>


@endsection