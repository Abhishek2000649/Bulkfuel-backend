@extends('layouts.app')
@section('content')
<h2>Warehouses</h2>
<a href="{{ route('admin.warehouse.create') }}" style="color:black;">Add Warehouse</a>
<table border="1" cellpadding="10" cellspacing="0" width="100%">
    <tr>
        <th>warehouse Name</th>
        <th>Warehouse Address</th>
        <th>City</th>
        <th>State</th>
        <th>Pincode</th>
        <th>Date</th>
        <th>Time</th>
        <th>Action</th>
    </tr>
    
@foreach($warehouses as $w)
<tr>
    <td>{{ $w->name }}</td>
    <td>{{ $w->address }}</td>
    <td>{{ $w->city }}</td>
    <td>{{ $w->state }}</td>
    <td>{{ $w->pincode }}</td>
    <td>{{ $w->created_at->format('d-m-y') }}</td>
    <td>{{ $w->created_at->format('h:m A') }}</td>
<td>
    <a href="{{ route('admin.warehouse.edit',$w->id) }}" style="color:black">Edit</a>
    <a href="{{ route('admin.warehouse.delete',$w->id) }}" style="color:black">Delete</a>
</td>
</tr> 
@endforeach
</table>
@endsection