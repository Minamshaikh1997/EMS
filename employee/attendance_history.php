<?php
session_start();

if(!isset($_SESSION['employee_id']))
{
    header("Location: ../index.php");
    exit();
}

include("../config/db.php");

$employee_id = $_SESSION['employee_id'];

$result = mysqli_query($conn,"
SELECT *
FROM attendance
WHERE employee_id='$employee_id'
ORDER BY attendance_date DESC
");
?>

<!DOCTYPE html>
<html>
<head>

<title>Attendance History</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<?php include("../dark_mode.php"); ?>

<body class="bg-light">

<div class="container mt-5">

<div class="card shadow">

<div class="card-header bg-primary text-white">

<h3>Attendance History</h3>

</div>

<div class="card-body">

<table class="table table-bordered table-striped">

<thead class="table-dark">

<tr>

<th>Date</th>
<th>Check In</th>
<th>Check Out</th>
<th>Working Hours</th>
<th>Status</th>

</tr>

</thead>

<tbody>

<?php while($row=mysqli_fetch_assoc($result)){ ?>

<tr>

<td><?php echo date("d-m-Y",strtotime($row['attendance_date'])); ?></td>

<td><?php echo $row['check_in']; ?></td>

<td><?php echo $row['check_out']; ?></td>

<td><?php echo $row['working_hours']; ?></td>

<td>

<?php

if($row['status']=="Present")
{
    echo "<span class='badge bg-success'>Present</span>";
}
elseif($row['status']=="Half Day")
{
    echo "<span class='badge bg-warning text-dark'>Half Day</span>";
}
else
{
    echo "<span class='badge bg-danger'>Late</span>";
}

?>

</td>

</tr>

<?php } ?>

</tbody>

</table>

<a href="dashboard.php" class="btn btn-secondary">
    Back Dashboard

</a>

</div>

</div>

</div>

</body>

</html>