<?php
session_start();

if (!isset($_SESSION['employee_id'])) {
    header("Location: login.php");
    exit();
}

include("../config/db.php");

$employee_id = $_SESSION['employee_id'];

$status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : "";
$leave_type = isset($_GET['leave_type']) ? mysqli_real_escape_string($conn, $_GET['leave_type']) : "";

$where = "employee_id='$employee_id'";

if($status != "")
{
    $where .= " AND status='$status'";
}

if($leave_type != "")
{
    $where .= " AND leave_type='$leave_type'";
}

$result = mysqli_query($conn,"SELECT * FROM leave_requests WHERE $where ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>

<head>

<title>My Leaves</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<?php include("../dark_mode.php"); ?>

<body class="bg-light">

<div class="container mt-5">

<div class="card shadow">

<div class="card-header bg-success text-white">
<h3>My Leave Requests</h3>
</div>

<div class="card-body">

<a href="dashboard.php" class="btn btn-primary"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>

<form method="GET" class="row mb-3">

<div class="col-md-3">

<select name="status" class="form-control">

<option value="">All Status</option>

<option value="Pending" <?php if($status=="Pending") echo "selected"; ?>>Pending</option>

<option value="Approved" <?php if($status=="Approved") echo "selected"; ?>>Approved</option>

<option value="Rejected" <?php if($status=="Rejected") echo "selected"; ?>>Rejected</option>

</select>

</div>

<div class="col-md-3">

<select name="leave_type" class="form-control">

<option value="">All Leave Types</option>

<option value="Annual" <?php if($leave_type=="Annual") echo "selected"; ?>>Annual</option>

<option value="Sick" <?php if($leave_type=="Sick") echo "selected"; ?>>Sick</option>

<option value="Casual" <?php if($leave_type=="Casual") echo "selected"; ?>>Casual</option>

</select>

</div>

<div class="col-md-2">

<button class="btn btn-success w-100">
Filter
</button>

</div>

<div class="col-md-2">

<a href="my_leaves.php" class="btn btn-secondary w-100">
Reset
</a>

</div>

</form>

<table class="table table-bordered table-striped">

<thead class="table-dark">

<tr>

<th>ID</th>
<th>Leave Type</th>
<th>Start Date</th>
<th>End Date</th>
<th>Total Days</th>
<th>Reason</th>
<th>Status</th>

</tr>

</thead>

<tbody>

<?php while($row = mysqli_fetch_assoc($result)){ ?>

<tr>

<td><?php echo $row['id']; ?></td>

<td><?php echo $row['leave_type']; ?></td>

<td><?php echo $row['start_date']; ?></td>

<td><?php echo $row['end_date']; ?></td>

<td><?php echo $row['total_days']; ?></td>

<td><?php echo $row['reason']; ?></td>

<td>

<?php

if($row['status']=="Pending")
{
    echo "<span class='badge bg-warning text-dark'>Pending</span>";
}
elseif($row['status']=="Approved")
{
    echo "<span class='badge bg-success'>Approved</span>";
}
else
{
    echo "<span class='badge bg-danger'>Rejected</span>";
}

?>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

</div>

<div class="text-center mt-3 mb-4">
    <a href="dashboard.php" class="btn btn-primary"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
</div>

</body>

</html>
