@extends('layouts.app')

@section('content')

<h2>Add New User</h2>

@if($errors->any())
    <ul style="color:red">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif

<form action="{{ route('admin.users.store') }}" method="POST">
    @csrf

    <table>
        <tr>
            <td>Name</td>
            <td>
                <input type="text" name="name" value="{{ old('name') }}">
            </td>
        </tr>

        <tr>
            <td>Email</td>
            <td>
                <input type="email" name="email" value="{{ old('email') }}">
            </td>
        </tr>

        <tr>
            <td>Password</td>
            <td>
                <input type="password" name="password">
            </td>
        </tr>

        <tr>
            <td colspan="2">
                <button type="submit">Save</button>
                <a href="{{ url('/admin/userManagement') }}">Back</a>
            </td>
        </tr>
    </table>
</form>

@endsection
