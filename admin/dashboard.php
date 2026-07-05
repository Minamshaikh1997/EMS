<?php

include("admincheck_role.php");

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}


include("../config/db.php");

/* ===========================
   DASHBOARD STATISTICS
=========================== */

// Total Employees
$totalEmployees = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM employees")
)['total'];

// Total Leave Requests
$totalLeaves = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM leave_requests")
)['total'];

// Approved Leaves
$approvedLeaves = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM leave_requests WHERE status='Approved'")
)['total'];

// Pending Leaves
$pendingLeaves = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM leave_requests WHERE status='Pending'")
)['total'];

// Rejected Leaves
$rejectedLeaves = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM leave_requests WHERE status='Rejected'")
)['total'];

// Today's Attendance
$todayAttendance = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM attendance WHERE attendance_date = CURDATE()")
)['total'];

/* ===========================
   LATEST EMPLOYEES
=========================== */

$latestEmployees = mysqli_query($conn,"
SELECT employee_id, full_name, department, joining_date
FROM employees
ORDER BY id DESC
LIMIT 5
");

/* ===========================
   LATEST LEAVE REQUESTS
=========================== */

$latestLeaves = mysqli_query($conn,"
SELECT
employees.full_name,
leave_requests.leave_type,
leave_requests.status
FROM leave_requests
INNER JOIN employees
ON leave_requests.employee_id = employees.id
ORDER BY leave_requests.id DESC
LIMIT 5
");

/* ===========================
   LATEST NOTICES
=========================== */

$latestNotices = mysqli_query($conn,"
SELECT *
FROM notices
ORDER BY id DESC
LIMIT 5
");

/* ===========================
   UPCOMING HOLIDAYS
=========================== */

$upcomingHolidays = mysqli_query($conn,"
SELECT *
FROM holidays
WHERE holiday_date >= CURDATE()
ORDER BY holiday_date ASC
LIMIT 5
");

/* ===========================
    DEPARTMENT COUNTS
=========================== */

// only count active employees
$deptCounts = mysqli_query($conn, "SELECT COALESCE(NULLIF(department,''),'Unassigned') AS department_name, COUNT(*) AS total FROM employees WHERE is_active='1' GROUP BY COALESCE(NULLIF(department,''),'Unassigned') ORDER BY total DESC");

/* ===========================
    DEPARTMENT -> ROLE COUNTS
=========================== */

// department + role counts for active employees
$deptRoleCounts = mysqli_query($conn, "SELECT COALESCE(NULLIF(department,''),'Unassigned') AS department, COALESCE(NULLIF(role,''),'Employee') AS role, COUNT(*) AS total FROM employees WHERE is_active='1' GROUP BY COALESCE(NULLIF(department,''),'Unassigned'), COALESCE(NULLIF(role,''),'Employee') ORDER BY department, total DESC");
?>

<!DOCTYPE html>
<html>

<head>

<title>Admin Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>

body{
    background:#f4f6f9;
}

.sidebar{
    width:240px;
    height:100vh;
    background:#212529;
    position:fixed;
    left:0;
    top:0;
    overflow:auto;
}

.sidebar h3{
    color:#fff;
    text-align:center;
    padding:20px 0;

<div class="card mt-4">
<div class="card-header bg-dark text-white">
    Department x Role Breakdown
</div>
<div class="card-body">
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Role</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $currentDept = null;
            if($deptRoleCounts && mysqli_num_rows($deptRoleCounts)>0){
                while($row = mysqli_fetch_assoc($deptRoleCounts)){
                    if($currentDept !== $row['department']){
                        $currentDept = $row['department'];
                        echo "<tr class='table-secondary'><td colspan='3'><strong><a href='employee.php?department=".urlencode($currentDept)."'>".htmlspecialchars($currentDept)."</a></strong></td></tr>";
                    }
                    echo "<tr>";
                    echo "<td></td>"; // department cell left empty for sub-rows
                    echo "<td><a href='employee.php?department=".urlencode($row['department'])."&role=".urlencode($row['role'])."'>".htmlspecialchars($row['role'])."</a></td>";
                    echo "<td><a href='employee.php?department=".urlencode($row['department'])."&role=".urlencode($row['role'])."'>".intval($row['total'])."</a></td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='3'>No data available</td></tr>";
            }
            ?>
            </tbody>
        </table>
    </div>
</div>
</div>
    border-bottom:1px solid #444;
}

.sidebar a{
    display:block;
    color:#fff;
    text-decoration:none;
    padding:12px 20px;
}

.sidebar a:hover{
    background:#0d6efd;
}

.content{
    margin-left:250px;
    padding:30px;
}

.card{
    border:none;
    box-shadow:0 4px 12px rgba(0,0,0,.15);
}

</style>

</head>

<?php include("../dark_mode.php"); ?>

<body>

<div class="sidebar">

<h3>Admin Panel</h3>

<a href="dashboard.php">Dashboard</a>
<a href="employee.php">Employees</a>
<a href="add_employee.php">Add Employee</a>
<a href="leave_requests.php">Leave Requests</a>
<a href="attendance_report.php">Attendance Report</a>
<a href="reports.php">Reports</a>
<a href="add_holiday.php">Holiday Management</a>
<a href="add_notice.php">Notice Board</a>
<a href="change_password.php">Change Password</a>
<a href="../logout.php">Logout</a>

</div>

<div class="content">

<h2 class="mb-4">

Welcome Admin

</h2>

<div class="row g-3">

<div class="col-md-4">

<div class="card bg-primary text-white">

<div class="card-body text-center">

<h5>Total Employees</h5>

<h2><?php echo $totalEmployees; ?></h2>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card bg-success text-white">

<div class="card-body text-center">

<h5>Total Leave Requests</h5>

<h2><?php echo $totalLeaves; ?></h2>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card bg-info text-white">

<div class="card-body text-center">

<h5>Today's Attendance</h5>

<h2><?php echo $todayAttendance; ?></h2>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card bg-success text-white">

<div class="card-body text-center">

<h5>Approved Leaves</h5>

<h2><?php echo $approvedLeaves; ?></h2>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card bg-warning">

<div class="card-body text-center">

<h5>Pending Leaves</h5>

<h2><?php echo $pendingLeaves; ?></h2>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card bg-danger text-white">

<div class="card-body text-center">

<h5>Rejected Leaves</h5>

<h2><?php echo $rejectedLeaves; ?></h2>

</div>

</div>

</div>

</div>

<div class="card mt-4">

<div class="card mt-4">
<div class="card-header bg-secondary text-white">
    Department-wise Employee Count
</div>
<div class="card-body">
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Employees</th>
                </tr>
            </thead>
            <tbody>
            <?php if($deptCounts && mysqli_num_rows($deptCounts)>0){ while($d = mysqli_fetch_assoc($deptCounts)){ ?>
                <tr>
                    <td><a href="employee.php?department=<?php echo urlencode($d['department_name']); ?>"><?php echo htmlspecialchars($d['department_name']); ?></a></td>
                    <td><a href="employee.php?department=<?php echo urlencode($d['department_name']); ?>"><?php echo intval($d['total']); ?></a></td>
                </tr>
            <?php } } else { ?>
                <tr><td colspan="2">No departments found</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
</div>





</div>

<div class="row mt-4">

<!-- Latest Employees -->

<div class="col-md-6">

<div class="card">

<div class="card-header bg-primary text-white">

Latest Employees

</div>

<div class="card-body">

<table class="table table-bordered table-striped">

<thead>

<tr>

<th>ID</th>
<th>Name</th>
<th>Department</th>
<th>Joining</th>

</tr>

</thead>

<tbody>

<?php while($emp=mysqli_fetch_assoc($latestEmployees)){ ?>

<tr>

<td><?php echo $emp['employee_id']; ?></td>

<td><?php echo $emp['full_name']; ?></td>

<td><?php echo $emp['department']; ?></td>

<td><?php echo date("d-m-Y",strtotime($emp['joining_date'])); ?></td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

</div>

<!-- Latest Leave Requests -->

<div class="col-md-6">

<div class="card">

<div class="card-header bg-success text-white">

Latest Leave Requests

</div>

<div class="card-body">

<table class="table table-bordered table-striped">

<thead>

<tr>

<th>Employee</th>
<th>Leave Type</th>
<th>Status</th>

</tr>

</thead>

<tbody>

<?php while($leave=mysqli_fetch_assoc($latestLeaves)){ ?>

<tr>

<td><?php echo $leave['full_name']; ?></td>

<td><?php echo $leave['leave_type']; ?></td>

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

<?php } ?>

</tbody>

</table>

</div>

</div>

</div>

</div>

<!-- Notices & Holidays -->

<div class="row mt-4">

<div class="col-md-6">

<div class="card">

<div class="card-header bg-primary">

Latest Notices

</div>

<div class="card-body">

<?php while($notice=mysqli_fetch_assoc($latestNotices)){ ?>

<div class="border-bottom mb-3 pb-2">

<h6><?php echo $notice['title']; ?></h6>

<p><?php echo $notice['notice']; ?></p>

<small class="text-muted">

<?php echo date("d-m-Y",strtotime($notice['created_at'])); ?>

</small>

</div>

<?php } ?>

</div>

</div>

</div>

<div class="col-md-6">

<div class="card">

<div class="card-header bg-info text-white">

Upcoming Holidays

</div>

<div class="card-body">

<?php while($holiday=mysqli_fetch_assoc($upcomingHolidays)){ ?>

<div class="border-bottom mb-3 pb-2">

<h6><?php echo $holiday['holiday_name']; ?></h6>

<p><?php echo $holiday['description']; ?></p>

<small class="text-muted">

<?php echo date("d-m-Y",strtotime($holiday['holiday_date'])); ?>

</small>

</div>

<?php } ?>

</div>

</div>

</div>

</div>

<script>

const ctx = document.getElementById('leaveChart');

new Chart(ctx,{

type:'bar',

data:{

labels:[
'Employees',
'Leaves',
'Approved',
'Pending',
'Rejected',
'Attendance'
],



},

options:{

responsive:true,

plugins:{

legend:{
display:true
}

}

}

});

</script>

</div>

</body>

</html>