@extends('layouts.app')

@section('content')

<h2>Update Address</h2>

@if(session('success'))
<p style="color:green">{{ session('success') }}</p>
@endif

<form method="POST" action="/profile">
@csrf

<textarea name="address" placeholder="Full Address" required>
{{ auth()->user()->address->address ?? '' }}
</textarea>
<br><br>

<input type="text" name="city" placeholder="City"
value="{{ auth()->user()->address->city ?? '' }}" required>
<br><br>

<input type="text" name="state" placeholder="State"
value="{{ auth()->user()->address->state ?? '' }}" required>
<br><br>

<input type="text" name="pincode" placeholder="Pincode"
value="{{ auth()->user()->address->pincode ?? '' }}" required>
<br><br>

<button type="submit">Save Address</button>
</form>

@endsection
