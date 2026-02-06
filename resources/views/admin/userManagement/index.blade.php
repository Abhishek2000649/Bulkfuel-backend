@extends('layouts.app')

@section('content')

<h2>User Management</h2>

@if(session('success'))
    <p style="color:green">{{ session('success') }}</p>
@endif

@if(session('error'))
    <p style="color:red">{{ session('error') }}</p>
@endif

<a href="{{ route('admin.users.add') }}">➕ Add User</a>

<br><br>

<table border="1" cellpadding="10" cellspacing="0" width="100%">
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Date</th>
        <th>Time</th>
        <th>Action</th>
    </tr>

    @foreach($users as $user)
        <tr>
            <td>{{ $user->id }}</td>
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>{{ $user->created_at->format('d-m-y') }}</td>
            <td>{{ $user->created_at->format('h:i A') }}</td>
            <td>
                <a href="{{ route('admin.users.edit', $user->id) }}" style="color:black">Edit</a> |

                <a href="{{ route('admin.users.delete',$user->id) }}" style="color:black"
                   onclick="return confirm('Are you sure?')">
                   Delete
                </a>
            </td>
        </tr>
    @endforeach
</table>

@endsection
