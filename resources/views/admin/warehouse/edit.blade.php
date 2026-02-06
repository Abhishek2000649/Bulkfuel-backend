@extends('layouts.app')
@section('content')
<h2>Add Warehouse</h2>
<form method="POST" action="{{ route('admin.warehouse.update', $wareHouse->id) }}">
@csrf
<input type="text" name="name" value="{{ $wareHouse->name }}" placeholder="Warehouse Name"><br>
<input type="text" name="address" value="{{ $wareHouse->address }}" placeholder="enter address"><br>
<input type="text" name="city" value="{{ $wareHouse->city }}" placeholder="City"><br>
<input type="text" name="state" value="{{ $wareHouse->state }}" placeholder="State"><br>
<input type="text" name="pincode" value="{{ $wareHouse->pincode }}" placeholder="Pincode"><br>
<button type="submit">Update</button>
</form>
@endsection