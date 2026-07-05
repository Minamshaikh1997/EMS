<?php
session_start();
?>

<!DOCTYPE html>
<html>
<head>

<title>Employee Login</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<?php include("../dark_mode.php"); ?>

<body class="bg-light">
<div class="container mt-5">

<div class="row justify-content-center">

<div class="col-md-5">

<div class="card shadow">

<div class="card-header bg-success text-white">
<h3>Employee Login</h3>
</div>

<div class="card-body">

<form method="POST" action="../login.php">

<div class="mb-3">
<label>Email</label>
<input type="email" name="email" class="form-control" required>
</div>

<div class="mb-3">
<label>Password</label>
<input type="password" name="password" class="form-control" required>
</div>

<button type="submit" name="login" class="btn btn-success w-100">
Login
</button>

</form>

</div>

</div>

</div>

</div>

</div>

</body>
</html>