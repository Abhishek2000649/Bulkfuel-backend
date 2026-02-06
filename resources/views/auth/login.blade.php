<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
</head>
<body style="
    margin:0;
    padding:0;
    height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    background:linear-gradient(135deg, #667eea, #764ba2);
    font-family:Arial, Helvetica, sans-serif;
">

    <div style="
        width:360px;
        background:#ffffff;
        padding:30px;
        border-radius:10px;
        box-shadow:0 10px 25px rgba(0,0,0,0.2);
        text-align:center;
    ">

        <h2 style="margin-bottom:20px;color:#333;">Welcome Back</h2>
        <p style="margin-bottom:25px;color:#777;font-size:14px;">
            Please login to your account
        </p>

        @if(session('error'))
            <p style="
                background:#ffe6e6;
                color:#cc0000;
                padding:10px;
                border-radius:5px;
                margin-bottom:15px;
                font-size:14px;
            ">
                {{ session('error') }}
            </p>
        @endif

        <form method="POST" action="/login">
            @csrf

            <input type="email" name="email" placeholder="Email address" required
                style="
                    width:100%;
                    padding:12px;
                    margin-bottom:15px;
                    border:1px solid #ccc;
                    border-radius:5px;
                    font-size:14px;
                ">

            <input type="password" name="password" placeholder="Password" required
                style="
                    width:100%;
                    padding:12px;
                    margin-bottom:20px;
                    border:1px solid #ccc;
                    border-radius:5px;
                    font-size:14px;
                ">

            <button type="submit"
                style="
                    width:100%;
                    padding:12px;
                    background:#667eea;
                    border:none;
                    border-radius:5px;
                    color:#fff;
                    font-size:15px;
                    cursor:pointer;
                ">
                Login
            </button>
        </form>

        <p style="margin-top:20px;font-size:14px;color:#555;">
            Don’t have an account?
            <a href="/signup" style="color:#667eea;text-decoration:none;font-weight:bold;">
                Sign up
            </a>
        </p>

    </div>

</body>
</html>
