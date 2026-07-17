<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

include("../config/db.php");

$search = "";

if(isset($_GET['search']))
{
    $search = mysqli_real_escape_string($conn,$_GET['search']);

    $sql = "SELECT leave_requests.*, employees.employee_id, employees.full_name
            FROM leave_requests
            INNER JOIN employees
            ON leave_requests.employee_id = employees.id
            WHERE employees.employee_id LIKE '%$search%'
            OR employees.full_name LIKE '%$search%'
            ORDER BY leave_requests.id DESC";
}
else
{
    $sql = "SELECT leave_requests.*, employees.employee_id, employees.full_name
            FROM leave_requests
            INNER JOIN employees
            ON leave_requests.employee_id = employees.id
            ORDER BY leave_requests.id DESC";
}

$result = mysqli_query($conn,$sql);

if(!$result)
{
    die(mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html>
<head>

<title>Leave Reports</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<?php include("../dark_mode.php"); ?>

<body class="bg-light">

<div class="container mt-5">

<div class="card shadow">

<div class="card-header bg-success text-white">

<h3>Leave Reports</h3>

</div>

<div class="card-body">

<form method="GET" class="row mb-3">

<div class="col-md-6">

<input
type="text"
name="search"
class="form-control"
placeholder="Search Employee ID or Name"
value="<?php echo $search; ?>">

</div>

<div class="col-md-2">

<button class="btn btn-primary w-100">
Search
</button>

</div>

<div class="col-md-2">

<a href="reports.php" class="btn btn-secondary w-100">
Reset

</a>
<a href="export_excel.php" class="btn btn-success">
    Export to Excel
</a>

</div>

</form>

<table class="table table-bordered table-striped">

<thead class="table-dark">

<tr>

<th>ID</th>
<th>Employee ID</th>
<th>Employee Name</th>
<th>Leave Type</th>
<th>Start Date</th>
<th>End Date</th>
<th>Total Days</th>
<th>Status</th>

</tr>

</thead>

<tbody>

<?php while($row=mysqli_fetch_assoc($result)){ ?>

<tr>

<td><?php echo $row['id']; ?></td>

<td><?php echo $row['employee_id']; ?></td>

<td><?php echo $row['full_name']; ?></td>

<td><?php echo $row['leave_type']; ?></td>

<td><?php echo $row['start_date']; ?></td>

<td><?php echo $row['end_date']; ?></td>

<td><?php echo $row['total_days']; ?></td>

<td>

<?php

if($row['status']=="Approved")
{
    echo "<span class='badge bg-success'>Approved</span>";
}
elseif($row['status']=="Rejected")
{
    echo "<span class='badge bg-danger'>Rejected</span>";
}
else
{
    echo "<span class='badge bg-warning text-dark'>Pending</span>";
}

?>

</td>

</tr>

<?php } ?>

</tbody>

</table>

<a href="dashboard.php" class="btn btn-primary"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>

</div>

</div>

</div>

</body>

</html>