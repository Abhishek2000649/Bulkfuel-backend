@extends('layouts.app')
@section('content')
<h2>Add Warehouse</h2>
<form method="POST" action="{{ route('admin.warehouse.store') }}">
@csrf
<input type="text" name="name" placeholder="Warehouse Name"><br>
<input type="text" name="address" placeholder="enter address"><br>
<input type="text" name="city" placeholder="City"><br>
<input type="text" name="state" placeholder="State"><br>
<input type="text" name="pincode" placeholder="Pincode"><br>
<button type="submit">Add</button>
</form>
@endsection