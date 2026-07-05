<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['employee_id'])) {
    header("Location: ../index.php");
    exit();
}

$employee_id = $_SESSION['employee_id'];
$employee_name = $_SESSION['employee_name'] ?? '';
$employee_role = $_SESSION['employee_role'] ?? 'Employee';

if ($employee_role === 'Admin') {
    header("Location: ../admin/dashboard.php");
    exit();
}

$employee = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM employees WHERE id='$employee_id' LIMIT 1"));
$photo = !empty($employee['photo']) ? $employee['photo'] : 'default.png';

$totalLeaves = 0;
$approvedLeaves = 0;
$pendingLeaves = 0;
$today = date('Y-m-d');
$attendanceStatus = 'Absent';

$leaveCounts = mysqli_query($conn, "SELECT status, COUNT(*) AS cnt FROM leave_requests WHERE employee_id='$employee_id' GROUP BY status");
if ($leaveCounts) {
    while ($row = mysqli_fetch_assoc($leaveCounts)) {
        $totalLeaves += $row['cnt'];
        if ($row['status'] === 'Approved') {
            $approvedLeaves = $row['cnt'];
        } elseif ($row['status'] === 'Pending') {
            $pendingLeaves = $row['cnt'];
        }
    }
}

$todayAttendance = mysqli_query($conn, "SELECT * FROM attendance WHERE employee_id='$employee_id' AND attendance_date='$today' LIMIT 1");
if ($todayAttendance && mysqli_num_rows($todayAttendance) > 0) {
    $attendance = mysqli_fetch_assoc($todayAttendance);
    $attendanceStatus = $attendance['status'] ?? 'Present';
}

$shiftLabel = !empty($employee['shift_name']) ? $employee['shift_name'] : 'Morning';
$shiftStart = !empty($employee['shift_start_time']) ? date('H:i', strtotime($employee['shift_start_time'])) : '09:00';
$shiftEnd = !empty($employee['shift_end_time']) ? date('H:i', strtotime($employee['shift_end_time'])) : '17:00';
?>

<!DOCTYPE html>
<html>

<head>

<title>Employee Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
    background:#f4f6f9;
}

.card{
    border:none;
    border-radius:12px;
    box-shadow:0 5px 15px rgba(0,0,0,.12);
}

.dashboard-header{
    background: #fff;
    border-radius: 18px;
    padding: 1.5rem;
    box-shadow: 0 12px 30px rgba(0,0,0,.05);
}

.feature-card{
    min-height: 170px;
    border: 1px solid rgba(0,0,0,.05);
}

.dashboard-button{
    border-radius: 12px;
    padding: 1rem 1.25rem;
    font-weight: 600;
}

.feature-card .card-header{
    border-bottom: 1px solid rgba(255,255,255,.15);
}

.feature-title{
    font-size: 0.95rem;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    margin-bottom: 0.6rem;
    font-weight: 700;
}

.stat-card .card-body{
    min-height: 150px;
}

.section-card {
    border: 1px solid rgba(0,0,0,.08);
    border-radius: 14px;
    background: #fff;
    overflow: hidden;
}

.section-card .card-header {
    padding: 0.95rem 1.25rem;
    font-weight: 700;
    border-bottom: 1px solid rgba(0,0,0,.08);
    border-radius: 14px 14px 0 0;
}

.section-card .card-body {
    background: #fff;
    padding: 1.25rem;
}

.table-custom {
    margin-bottom: 0;
}

.table-custom th,
.table-custom td {
    vertical-align: middle;
}

.table-custom tbody tr:hover {
    background: rgba(0, 0, 0, 0.03);
}

.dashboard-header h3 {
    font-size: 1.8rem;
}

.dashboard-header p {
    color: #6c757d;
}

.table-responsive {
    overflow-x: auto;
}


.dashboard-header p {
    color: #6c757d;
}

.btn-menu {
    border-radius: 12px;
    padding: 0.9rem 1.25rem;
    font-weight: 600;
    min-height: 56px;
}

</style>

</head>

<body>
<?php include("../dark_mode.php"); ?>

<div class="container mt-5">

<div class="dashboard-header mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <img src="../uploads/<?php echo $photo; ?>" width="80" height="80" class="rounded-circle border shadow-sm">
            <div>
                <h3 class="mb-1">Welcome, <b><?php echo $employee_name; ?></b></h3>
                <p class="mb-0 text-muted">Role: <?php echo htmlspecialchars($employee_role); ?></p>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="upload_photo.php" class="btn btn-outline-primary dashboard-button">Profile Photo</a>
            <a href="logout.php" class="btn btn-outline-danger dashboard-button">Logout</a>
        </div>
    </div>
</div>

<!-- Statistics -->

<div class="row">

<div class="col-md-3">

<div class="card stat-card bg-primary text-white">

<div class="card-body text-center">

<h5>Total Leaves</h5>

<h2><?php echo $totalLeaves; ?></h2>

</div>

</div>

</div>

<div class="col-md-3">

<div class="card stat-card bg-success text-white">

<div class="card-body text-center">

<h5>Approved</h5>

<h2><?php echo $approvedLeaves; ?></h2>

</div>

</div>

</div>

<div class="col-md-3">

<div class="card stat-card bg-warning">

<div class="card-body text-center">

<h5>Pending</h5>

<h2><?php echo $pendingLeaves; ?></h2>

</div>

</div>

</div>

<div class="col-md-3">

<div class="card stat-card bg-info text-white">

<div class="card-body text-center">

<h5>Today's Attendance</h5>

<h4><?php echo $attendanceStatus; ?></h4>

</div>

</div>

</div>

</div>

<br>

<div class="row mt-3">
    <div class="col-md-6">
        <div class="card feature-card">
            <div class="card-header bg-secondary text-white">
                Shift Schedule
            </div>
            <div class="card-body">
                <p class="feature-title"><?php echo htmlspecialchars($shiftLabel); ?></p>
                <p class="mb-1"><strong>Start:</strong> <?php echo htmlspecialchars($shiftStart); ?></p>
                <p class="mb-0"><strong>End:</strong> <?php echo htmlspecialchars($shiftEnd); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card feature-card">
            <div class="card-header bg-primary text-white">
                Attendance Status
            </div>
            <div class="card-body">
                <p class="feature-title">Today</p>
                <h5 class="mb-0"><?php echo htmlspecialchars($attendanceStatus); ?></h5>
            </div>
        </div>
    </div>
</div>

<!-- Menu -->

<div class="card mt-4 section-card">

<div class="card-header bg-dark text-white">

Employee Menu

</div>

<div class="card-body">

<div class="row g-3">

<div class="col-md-4">
<a href="attendance.php" class="btn btn-menu btn-success w-100">
Attendance
</a>
</div>

<div class="col-md-4">
<a href="attendance_history.php" class="btn btn-menu btn-primary w-100">
Attendance History
</a>
</div>

<div class="col-md-4">
<a href="apply_leave.php" class="btn btn-menu btn-warning w-100">
Apply Leave
</a>
</div>

<div class="col-md-4">
<a href="leave_history.php" class="btn btn-menu btn-info w-100">
My Leave History
</a>
</div>

<div class="col-md-4">
<a href="change_password.php" class="btn btn-menu btn-dark w-100">
Change Password
</a>
</div>

<div class="col-md-4">
<a href="leave_balance.php" class="btn btn-menu btn-primary w-100">
Leave Balance
</a>
</div>

</div>

</div>

</div>

</div>
<hr>

<div class="card mt-4 section-card">

<div class="card-header bg-primary text-white">

<h5>Recent Leave Requests</h5>

</div>

<div class="card-body p-0">

<?php

$recent = mysqli_query($conn,"
SELECT leave_type,start_date,end_date,status
FROM leave_requests
WHERE employee_id='$employee_id'
ORDER BY id DESC
LIMIT 5
");

?>

<table class="table table-bordered table-hover table-custom">

<thead class="table-dark">

<tr>

<th>Leave Type</th>
<th>Start Date</th>
<th>End Date</th>
<th>Status</th>

</tr>

</thead>

<tbody>

<?php

if(mysqli_num_rows($recent)>0)
{

while($leave=mysqli_fetch_assoc($recent))
{

?>

<tr>

<td><?php echo $leave['leave_type']; ?></td>

<td><?php echo date("d-m-Y",strtotime($leave['start_date'])); ?></td>

<td><?php echo date("d-m-Y",strtotime($leave['end_date'])); ?></td>

<td>

<?php

if($leave['status']=="Approved")
{
    echo "<span class='badge bg-success'>Approved</span>";
}
elseif($leave['status']=="Pending")
{
    echo "<span class='badge bg-warning text-dark'>Pending</span>";
}
else
{
    echo "<span class='badge bg-danger'>Rejected</span>";
}

?>

</td>

</tr>

<?php

}

}
else
{

?>

<tr>

<td colspan="4" class="text-center">

No Leave Records Found

</td>

</tr>

<?php

}

?>

</tbody>

</table>

</div>

</div>

<br>

<div class="card mt-4 section-card">

<div class="card-header bg-success text-white">

<h5>Today's Attendance</h5>

</div>

<div class="card-body p-0">

<?php

$todayAttendance = mysqli_query($conn,"
SELECT *
FROM attendance
WHERE employee_id='$employee_id'
AND attendance_date='$today'
LIMIT 1
");

if(mysqli_num_rows($todayAttendance)>0)
{

$a=mysqli_fetch_assoc($todayAttendance);

?>

<div class="table-responsive">
<table class="table table-bordered table-custom mb-0">

<tr>

<th>Check In</th>

<td><?php echo $a['check_in']; ?></td>

</tr>

<tr>

<th>Check Out</th>

<td><?php echo $a['check_out']; ?></td>

</tr>

<tr>

<th>Working Hours</th>

<td><?php echo $a['working_hours']; ?></td>

</tr>

<tr>

<th>Status</th>

<td><?php echo $a['status']; ?></td>

</tr>

</table>
</div>

<?php

}
else
{

echo "<div class='alert alert-warning m-0'>
Attendance not marked today.
</div>";

}

?>

</div>

<!-- Latest Notices -->

<!-- Upcoming Holidays -->

<div class="card mt-4 section-card">

<div class="card-header bg-success text-white">

<h5>Upcoming Holidays</h5>

</div>

<div class="card-body">

<?php

$holidayResult = mysqli_query($conn, "SELECT * FROM holidays WHERE holiday_date >= CURDATE() ORDER BY holiday_date ASC");

if ($holidayResult && mysqli_num_rows($holidayResult) > 0)
{

while($holiday=mysqli_fetch_assoc($holidayResult))
{

?>

<div class="border-bottom mb-3 pb-2">

<h6 class="text-success">

<?php echo $holiday['holiday_name']; ?>

</h6>

<p class="mb-1">

<?php echo $holiday['description']; ?>

</p>

<small class="text-muted">

<?php echo date("d-m-Y",strtotime($holiday['holiday_date'])); ?>

</small>

</div>

<?php

}

}
else
{

echo "<div class='alert alert-info'>
No Upcoming Holidays
</div>";

}

?>

</div>

</div>

<div class="card mt-4 section-card">

<div class="card-header bg-warning">

<h5>Latest Notices</h5>

</div>

<div class="card-body">

<?php

$noticeResult = mysqli_query($conn, "SELECT * FROM notices ORDER BY id DESC LIMIT 5");

if ($noticeResult && mysqli_num_rows($noticeResult) > 0)
{

while($notice=mysqli_fetch_assoc($noticeResult))
{

?>

<div class="border-bottom mb-3 pb-2">

<h6 class="text-primary">

<?php echo $notice['title']; ?>

</h6>

<p class="mb-1">

<?php echo $notice['notice']; ?>

</p>

<small class="text-muted">

<?php echo date("d-m-Y",strtotime($notice['created_at'])); ?>

</small>

</div>

<?php

}

}
else
{

echo "<div class='alert alert-info'>
No Notices Available
</div>";

}

?>

</div>

</div>

</div>

</body>

</html>