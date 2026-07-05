<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

include("../config/db.php");

$adminEmail = $_SESSION['admin'];

if (isset($_POST['change_password'])) {

    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $query = mysqli_query($conn, "SELECT * FROM admin WHERE email='$adminEmail'");
    $admin = mysqli_fetch_assoc($query);

    if (!password_verify($current_password, $admin['password'])) {

        $message = "<div class='alert alert-danger'>Current Password is incorrect.</div>";

    } elseif ($new_password != $confirm_password) {

        $message = "<div class='alert alert-danger'>New Password and Confirm Password do not match.</div>";

    } else {

        $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);

        mysqli_query($conn, "UPDATE admin SET password='$hashedPassword' WHERE email='$adminEmail'");

        $message = "<div class='alert alert-success'>Password changed successfully.</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>

<title>Change Password</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<?php include("../dark_mode.php"); ?>

<body class="bg-light">

<div class="container mt-5">

<div class="row justify-content-center">

<div class="col-md-6">

<div class="card shadow">

<div class="card-header bg-primary text-white">

<h4>Admin Change Password</h4>

</div>

<div class="card-body">

<?php
if(isset($message)){
    echo $message;
}
?>

<form method="POST">

<div class="mb-3">
<label>Current Password</label>
<input type="password" name="current_password" class="form-control" required>
</div>

<div class="mb-3">
<label>New Password</label>
<input type="password" name="new_password" class="form-control" required>
</div>

<div class="mb-3">
<label>Confirm Password</label>
<input type="password" name="confirm_password" class="form-control" required>
</div>

<button type="submit" name="change_password" class="btn btn-success">
Change Password
</button>

<a href="dashboard.php" class="btn btn-secondary">
Back
</a>

</form>

</div>

</div>

</div>

</div>

</div>

</body>
</html>