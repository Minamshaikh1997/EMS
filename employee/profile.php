<?php
session_start();

if (!isset($_SESSION['employee_id'])) {
    header("Location: login.php");
    exit();
}

include("../config/db.php");

$employee_id = $_SESSION['employee_id'];

$result = mysqli_query($conn, "SELECT * FROM employees WHERE id='$employee_id'");
$row = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Profile</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<?php include("../dark_mode.php"); ?>

<body class="bg-light">

<div class="container mt-5">

<div class="card shadow">

<div class="card-header bg-primary text-white">
    <h3>My Profile</h3>
</div>

<div class="card-body">

<table class="table table-bordered">

<tr>
    <th>Employee ID</th>
    <td><?php echo $row['employee_id']; ?></td>
</tr>

<tr>
    <th>Full Name</th>
    <td><?php echo $row['full_name']; ?></td>
</tr>

<tr>
    <th>Email</th>
    <td><?php echo $row['email']; ?></td>
</tr>

<tr>
    <th>Department</th>
    <td><?php echo $row['department']; ?></td>
</tr>

<tr>
    <th>Shift</th>
    <td>
        <?php echo !empty($row['shift_name']) ? $row['shift_name'] : 'Morning'; ?>
        (<?php echo !empty($row['shift_start_time']) ? date('H:i', strtotime($row['shift_start_time'])) : '09:00'; ?> - <?php echo !empty($row['shift_end_time']) ? date('H:i', strtotime($row['shift_end_time'])) : '17:00'; ?>)
    </td>
</tr>

<tr>
    <th>Designation</th>
    <td><?php echo $row['designation']; ?></td>
</tr>

<tr>
    <th>Joining Date</th>
    <td><?php echo $row['joining_date']; ?></td>
</tr>

<tr>
    <th>Annual Leave</th>
    <td><?php echo $row['annual_leave']; ?></td>
</tr>

<tr>
    <th>Sick Leave</th>
    <td><?php echo $row['sick_leave']; ?></td>
</tr>

<tr>
    <th>Casual Leave</th>
    <td><?php echo $row['casual_leave']; ?></td>
</tr>

</table>

<a href="dashboard.php" class="btn btn-primary">
    Back Dashboard
</a>


</div>

</div>

</div>

</body>
</html>