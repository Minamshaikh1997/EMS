<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

include("../config/db.php");

$sql = "SELECT
leave_requests.*,
employees.full_name,
employees.photo,
employees.department
FROM leave_requests
INNER JOIN employees
ON leave_requests.employee_id = employees.id
ORDER BY leave_requests.id DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
<title>Leave Requests</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<?php include("../dark_mode.php"); ?>

<body class="bg-light">

<div class="container mt-5">

<div class="card shadow">

<div class="card-header bg-primary text-white">
<h3>Leave Requests</h3>
</div>

<div class="card-body">

<table class="table table-bordered table-striped">

<thead class="table-dark">

<tr>
<th>ID</th>
<th>Employee</th>
<th>Photo</th>
<th>Department</th>
<th>Leave Type</th>
<th>Start</th>
<th>End</th>
<th>Days</th>
<th>Status</th>
<th>Action</th>

</tr>

</thead>

<tbody>

<?php while($row=mysqli_fetch_assoc($result)){ ?>

<tr>

<td><?= $row['id']; ?></td>
<td><?= $row['full_name']; ?></td>
<td>

<?php
if(!empty($row['photo'])){
?>

<img src="../uploads/<?php echo $row['photo']; ?>"
width="50"
height="50"
style="border-radius:50%;object-fit:cover;">

<?php
}else{
echo "No Photo";
}
?>

</td>

<td><?php echo $row['department']; ?></td>
<td><?= $row['leave_type']; ?></td>
<td><?= $row['start_date']; ?></td>
<td><?= $row['end_date']; ?></td>
<td><?= $row['total_days']; ?></td>
<td>

<?php

if($row['status']=="Pending")
{
    echo "<span class='badge bg-warning'>Pending</span>";
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

<td>

<?php if($row['status']=="Pending"){ ?>

<a href="approve_leave.php?id=<?= $row['id']; ?>" class="btn btn-success btn-sm">
Approve
</a>

<a href="reject_leave.php?id=<?= $row['id']; ?>" class="btn btn-danger btn-sm">
Reject
</a>

<?php } ?>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

<div class="mb-3">
    <a href="dashboard.php" class="btn btn-primary"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
</div>	

</div>

</div>

</body>
</html>