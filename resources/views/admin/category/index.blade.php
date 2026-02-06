@extends('layouts.app')
@section('content')
<h2>Manage Categories</h2>

@if(session('success'))
    <p style="color:green">{{ session('success') }}</p>
@endif

<a href="{{ route('admin.category.create') }}" style="color:black;">Add category</a>
<table border="1" cellpadding="10" cellSpacing="0" width="100%">
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Action</th>
</tr>

@foreach($categories as $c)
<tr>
    <td>{{ $c->id }}</td>
    <td>{{ $c->name }}</td>
    <td>
        <a href="{{ route('admin.category.edit',$c->id) }}" style="color:black;">edit</a>
        <form method="POST" action="{{ url('admin/categories/'.$c->id) }}" style="display:inline">
            @csrf
            @method('DELETE')
            <button>Delete</button>
        </form>
    </td>
</tr>
@endforeach
</table>
@endsection