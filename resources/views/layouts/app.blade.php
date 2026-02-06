<!DOCTYPE html>
<html>
<head>
    <title>Simple E-Commerce</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>

<header>
    <b>My Shop</b>

    {{-- Search box (optional UI) --}}
    <input type="text" placeholder="Search product">

    <span style="float:right">

        {{-- ================= GUEST ================= --}}
        @guest
            <a href="/login" style="color: white">Login</a> 
            <a href="/signup" style="color: white">Signup</a>
        @endguest

        {{-- ================= LOGGED IN ================= --}}
        @auth
            {{ auth()->user()->name }}

            {{-- show address only for USER --}}
            @if(auth()->user()->role === 'USER')
                | {{ auth()->user()->address->city ?? 'No Address' }}
            @endif

            |

            {{-- ================= USER MENU ================= --}}
            @if(auth()->user()->role === 'USER')
                <a href="{{ route('user.dashboard') }}">Home</a> 
                <a href="{{ route('user.my-orders') }}">My Orders</a> 
                <a href="{{ route('user.profile') }}">Profile</a> 
                <a href="{{ route('user.cart') }}">My cart</a> 
            @endif

            {{-- ================= ADMIN MENU ================= --}}
            @if(auth()->user()->role === 'ADMIN')
                <a href="/admin/products">Manage Products</a> 
                <a href="{{ route('admin.orders') }}">All Orders</a> 
                <a href="{{  route('admin.users')}}">UserManagement</a>
                <a href="{{ route('admin.warehouse.index') }}">WareHouse</a>
                <a href="{{ route('admin.stock.index') }}">Stock</a>
                <a href="{{ route('admin.categories') }}">Category</a>
            @endif
            @if(auth()->user()->role === 'delivery_agent')
                
                <a href="{{ route('delivery.dashboard') }}">Dashboard</a> 
                <a href="{{  route('delivery.available')}}">Available</a>
            @endif

            <a href="/logout">Logout</a>
        @endauth

    </span>
</header>

<div class="container">
    @yield('content')
</div>

<footer>
    © 2025 Simple E-Commerce Project
</footer>

</body>
</html>
