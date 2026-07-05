<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Leave Management System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body{
            background:#f4f6f9;
        }

        .login-box{
            width:400px;
            margin:100px auto;
            background:#fff;
            padding:30px;
            border-radius:10px;
            box-shadow:0 0 15px rgba(0,0,0,.15);
        }

        h2{
            text-align:center;
            margin-bottom:25px;
        }

        .btn-primary{
            width:100%;
        }
    </style>

</head>
<body>
<?php include("dark_mode.php"); ?>

<div class="login-box">

<h2>Employee Leave System</h2>

<form action="login.php" method="POST">

<div class="mb-3">
<label>Email</label>
<input type="email" name="email" class="form-control" required>
</div>

<div class="mb-3">
<label>Password</label>
<input type="password" name="password" class="form-control" required>
</div>

<button class="btn btn-primary">
Login
</button>

<div class="mt-3 text-center">
    <a href="admin/login.php">Admin Login</a>
</div>

</form>

</div>

</body>
</html>