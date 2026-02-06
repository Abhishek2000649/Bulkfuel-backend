<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Signup</title>
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
        width:380px;
        background:#ffffff;
        padding:30px;
        border-radius:10px;
        box-shadow:0 10px 25px rgba(0,0,0,0.2);
        text-align:center;
    ">

        <h2 style="margin-bottom:15px;color:#333;">Create Account</h2>
        <p style="margin-bottom:25px;color:#777;font-size:14px;">
            Join us and get started
        </p>

        <form method="POST" action="/register">
            @csrf

            <input type="text" name="name" placeholder="Full Name" required
                style="
                    width:100%;
                    padding:12px;
                    margin-bottom:15px;
                    border:1px solid #ccc;
                    border-radius:5px;
                    font-size:14px;
                ">

            <input type="email" name="email" placeholder="Email Address" required
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
                Register
            </button>
        </form>

        <p style="margin-top:20px;font-size:14px;color:#555;">
            Already have an account?
            <a href="/login" style="color:#667eea;text-decoration:none;font-weight:bold;">
                Login
            </a>
        </p>

    </div>

</body>
</html>
