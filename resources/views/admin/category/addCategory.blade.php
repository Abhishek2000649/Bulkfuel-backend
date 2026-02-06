@extends('layouts.app')
@section('content')

<form action="{{ route('admin.category.store') }}" method="POST">
@csrf
<input type="text" name="name" placeholder="Category name">
<button>Add</button>
</form>


@endsection