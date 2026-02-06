@extends('layouts.app')

@section('content')

<h2>Edit User</h2>

@if($errors->any())
    <ul style="color:red">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif

<form action="{{ route('admin.users.update', $user->id) }}" method="POST">
    @csrf

    <table>
        <tr>
            <td>Name</td>
            <td>
                <input type="text" name="name" value="{{ $user->name }}">
            </td>
        </tr>

        <tr>
            <td>Email</td>
            <td>
                <input type="email" name="email" value="{{ $user->email }}">
            </td>
        </tr>
        <tr>
            <td>Role</td>
            <td>
                <input type="text" name="role" value="{{ $user->role }}">
            </td>
        </tr>

        <tr>
            <td colspan="2">
                <button type="submit">Update</button>
                <a href="{{ url('/admin/userManagement') }}">Back</a>
            </td>
        </tr>
    </table>
</form>

@endsection
