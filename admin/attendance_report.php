<?php
session_start();

if(!isset($_SESSION['admin']))
{
    header("Location: ../index.php");
    exit();
}

include("../config/db.php");

$search = "";
$from = "";
$to = "";

$sql = "SELECT
attendance.*,
employees.full_name,
employees.department
FROM attendance
LEFT JOIN employees
ON attendance.employee_id=employees.id
WHERE 1=1";

if(isset($_GET['filter']))
{

    $search = mysqli_real_escape_string($conn,$_GET['search']);
    $from = $_GET['from'];
    $to = $_GET['to'];

    if($search!="")
    {
        $sql .= " AND (
        employees.full_name LIKE '%$search%'
        OR employees.employee_id LIKE '%$search%'
        )";
    }

    if($from!="" && $to!="")
    {
        $sql .= " AND attendance_date BETWEEN '$from' AND '$to'";
    }

}

$sql .= " ORDER BY attendance_date DESC";

$result = mysqli_query($conn,$sql);
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

</style>

</head>

<body>

<div class="container mt-5">

<h2 class="mb-4">Attendance Report</h2>

<!-- Filter -->

<div class="card mb-4">

<div class="card-body">

<form method="GET" class="row">

<div class="col-md-4">

<label>Employee Name / ID</label>

<input
type="text"
name="search"
class="form-control"
value="<?php echo $search; ?>">

</div>

<div class="col-md-3">

<label>From Date</label>

<input
type="date"
name="from"
class="form-control"
value="<?php echo $from; ?>">

</div>

<div class="col-md-3">

<label>To Date</label>

<input
type="date"
name="to"
class="form-control"
value="<?php echo $to; ?>">

</div>

<div class="col-md-2 d-grid">

<label>&nbsp;</label>

<button
type="submit"
name="filter"
class="btn btn-primary">

Filter

</button>

</div>

</form>

</div>

</div>

<!-- Attendance Table -->

<div class="card">

<div class="card-header bg-dark text-white">

Attendance Records

</div>

<div class="card-body">

<div class="table-responsive">

<table class="table table-bordered table-striped">

<thead class="table-dark">

<tr>

<th>Employee ID</th>
<th>Employee Name</th>
<th>Department</th>
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

<td><?php echo $row['employee_id']; ?></td>

<td><?php echo $row['full_name']; ?></td>

<td><?php echo $row['department']; ?></td>

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
elseif($row['status']=="Late")
{
    echo "<span class='badge bg-danger'>Late</span>";
}
else
{
    echo "<span class='badge bg-secondary'>".$row['status']."</span>";
}

?>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

<div class="mt-3">

<a href="dashboard.php" class="btn btn-secondary">

Back Dashboard

</a>

<a href="export_attendance_excel.php" class="btn btn-success">
    Export Excel
</a>

Export Excel

</a>

</div>

</div>

</div>

</div>

</body>

</html>