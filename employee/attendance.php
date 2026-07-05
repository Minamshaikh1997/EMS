<?php
session_start();

if (!isset($_SESSION['employee_id'])) {
    header("Location: ../index.php");
    exit();
}

include("../config/db.php");

$employee_id = $_SESSION['employee_id'];
$today = date("Y-m-d");
$currentTime = date("H:i:s");

// Today's Attendance
$check = mysqli_query($conn, "
SELECT * FROM attendance
WHERE employee_id='$employee_id'
AND attendance_date='$today'
");

$row = mysqli_fetch_assoc($check);

// =======================
// CHECK IN
// =======================

if(isset($_POST['check_in']))
{

    $already = mysqli_query($conn,"
    SELECT * FROM attendance
    WHERE employee_id='$employee_id'
    AND attendance_date='$today'
    ");

    if(mysqli_num_rows($already)==0)
    {

        mysqli_query($conn,"
        INSERT INTO attendance
        (
            employee_id,
            attendance_date,
            check_in,
            status
        )
        VALUES
        (
            '$employee_id',
            '$today',
            '$currentTime',
            'Present'
        )
        ");

    }

    header("Location: attendance.php");
    exit();

}


// =======================
// CHECK OUT
// =======================

if(isset($_POST['check_out']))
{

    $attendance = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT * FROM attendance
    WHERE employee_id='$employee_id'
    AND attendance_date='$today'
    "));

    if($attendance)
    {

        $checkIn = strtotime($attendance['check_in']);
        $checkOut = strtotime($currentTime);

        $seconds = $checkOut - $checkIn;

        if($seconds < 0)
        {
            $seconds = 0;
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        $workingHours = $hours." Hours ".$minutes." Minutes";

        if($hours >= 8)
        {
            $status = "Present";
        }
        elseif($hours >= 4)
        {
            $status = "Half Day";
        }
        else
        {
            $status = "Late";
        }

        mysqli_query($conn,"
        UPDATE attendance
        SET
            check_out='$currentTime',
            working_hours='$workingHours',
            status='$status'
        WHERE employee_id='$employee_id'
        AND attendance_date='$today'
        ");

    }

    header("Location: attendance.php");
    exit();

}


// Today's Attendance Refresh

$check = mysqli_query($conn,"
SELECT * FROM attendance
WHERE employee_id='$employee_id'
AND attendance_date='$today'
");

$row = mysqli_fetch_assoc($check);

?>	

<!DOCTYPE html>
<html>

<head>

<?php include("../dark_mode.php"); ?>

<body class="bg-light">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
    background:#f4f6f9;
}

.card{
    border:none;
    box-shadow:0 5px 15px rgba(0,0,0,.15);
}

.badge{
    font-size:15px;
}

</style>

</head>

<body>

<div class="container mt-5">

<div class="row justify-content-center">

<div class="col-md-8">

<div class="card">

<div class="card-header bg-success text-white">

<h3>Employee Attendance</h3>

</div>

<div class="card-body">

<h5>
Today's Date :
<b><?php echo date("d M Y"); ?></b>
</h5>

<hr>

<?php

if(!$row)
{

?>

<form method="POST">

<button
type="submit"
name="check_in"
class="btn btn-success">

Check In

</button>

</form>

<?php

}
elseif($row['check_in']!="" && $row['check_out']=="")
{

?>

<div class="alert alert-success">

Checked In Successfully

<br>

Time :
<b><?php echo $row['check_in']; ?></b>

</div>

<form method="POST">

<button
type="submit"
name="check_out"
class="btn btn-danger">

Check Out

</button>

</form>

<?php

}
else
{

?>

<div class="alert alert-primary">

Today's Attendance Completed

</div>

<table class="table table-bordered">

<tr>

<th width="40%">Check In</th>

<td><?php echo $row['check_in']; ?></td>

</tr>

<tr>

<th>Check Out</th>

<td><?php echo $row['check_out']; ?></td>

</tr>

<tr>

<th>Working Hours</th>

<td>

<?php echo $row['working_hours']; ?>

</td>

</tr>

<tr>

<th>Status</th>

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

</table>

<?php

}

?>

<hr>

<a
href="dashboard.php"
class="btn btn-secondary">

Back Dashboard

</a>

</div>

</div>

</div>

</div>

</div>

</body>

</html>