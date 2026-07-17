<?php
session_start();

if (!isset($_SESSION['employee_id'])) {
    header("Location: login.php");
    exit();
}

include("../config/db.php");

$employee_id = $_SESSION['employee_id'];

if(isset($_POST['change']))
{
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $result = mysqli_query($conn,"SELECT * FROM employees WHERE id='$employee_id'");
    $row = mysqli_fetch_assoc($result);

    if($current_password != $row['password'])
    {
        $msg = "<div class='alert alert-danger'>Current Password is incorrect.</div>";
    }
    elseif($new_password != $confirm_password)
    {
        $msg = "<div class='alert alert-danger'>New Password and Confirm Password do not match.</div>";
    }
    else
    {
        mysqli_query($conn,"UPDATE employees SET password='$new_password' WHERE id='$employee_id'");

        $msg = "<div class='alert alert-success'>Password changed successfully.</div>";
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

<div class="card shadow">

<div class="card-header bg-primary text-white">
<h3>Change Password</h3>
</div>

<div class="card-body">

<?php
if(isset($msg))
{
    echo $msg;
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

<button type="submit" name="change" class="btn btn-success">
Change Password
</button>

<a href="dashboard.php" class="btn btn-primary"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>

</form>

</div>

</div>

</div>

</body>
</html>