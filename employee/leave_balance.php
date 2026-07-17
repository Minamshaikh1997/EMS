<?php
session_start();

if(!isset($_SESSION['employee_id']))
{
    header("Location: ../index.php");
    exit();
}

include("../config/db.php");

$employee_id = $_SESSION['employee_id'];

$query = mysqli_query($conn,"
SELECT *
FROM leave_balance
WHERE employee_id='$employee_id'
LIMIT 1
");

if(mysqli_num_rows($query)==0)
{
    mysqli_query($conn,"
    INSERT INTO leave_balance
    (
        employee_id,
        casual_leave,
        sick_leave,
        annual_leave
    )
    VALUES
    (
        '$employee_id',
        12,
        10,
        20
    )
    ");

    $query=mysqli_query($conn,"
    SELECT *
    FROM leave_balance
    WHERE employee_id='$employee_id'
    LIMIT 1
    ");
}

$balance=mysqli_fetch_assoc($query);
?>

<!DOCTYPE html>

<html>

<head>

<title>Leave Balance</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<?php include("../dark_mode.php"); ?>

<body class="bg-light">

<div class="container mt-5">

<h2 class="mb-4">

Leave Balance

</h2>

<div class="row">

<div class="col-md-4">

<div class="card bg-success text-white">

<div class="card-body text-center">

<h4>Casual Leave</h4>

<h1>

<?php echo $balance['casual_leave']; ?>

</h1>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card bg-warning">

<div class="card-body text-center">

<h4>Sick Leave</h4>

<h1>

<?php echo $balance['sick_leave']; ?>

</h1>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card bg-primary text-white">

<div class="card-body text-center">

<h4>Annual Leave</h4>

<h1>

<?php echo $balance['annual_leave']; ?>

</h1>

</div>

</div>

</div>

</div>

<br>

<a href="dashboard.php" class="btn btn-primary"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>

</div>

</body>

</html>