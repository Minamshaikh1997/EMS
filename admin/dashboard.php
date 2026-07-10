<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("admincheck_role.php");
include("../config/db.php");

// Dashboard Statistics
$totalEmployees = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM employees"))['total'];
$totalLeaves = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM leave_requests"))['total'];
$approvedLeaves = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM leave_requests WHERE status='Approved'"))['total'];
$pendingLeaves = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM leave_requests WHERE status='Pending'"))['total'];
$rejectedLeaves = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM leave_requests WHERE status='Rejected'"))['total'];
$todayAttendance = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM attendance WHERE attendance_date=CURDATE()"))['total'];

/* Latest Employees */
$latestEmployees = mysqli_query($conn, "
SELECT employee_id, full_name, department, joining_date
FROM employees
ORDER BY id DESC
LIMIT 5
");

/* Latest Leave Requests */
$latestLeaves = mysqli_query($conn, "
SELECT e.full_name, lr.leave_type, lr.status
FROM leave_requests lr
INNER JOIN employees e ON lr.employee_id = e.id
ORDER BY lr.id DESC
LIMIT 5
");

/* Latest Notices */
$latestNotices = mysqli_query($conn, "
SELECT *
FROM notices
ORDER BY id DESC
LIMIT 5
");

/* Upcoming Holidays */
$upcomingHolidays = mysqli_query($conn, "
SELECT *
FROM holidays
WHERE holiday_date >= CURDATE()
ORDER BY holiday_date ASC
LIMIT 5
");
?>

<!DOCTYPE html>

<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<style>
body{background:#f4f7fc;font-family:Arial,sans-serif}
<div id="sidebar" class="sidebar">
    <!-- Sidebar Menu -->
</div>

<div id="main-content">
    <!-- Main Content -->
</div>

<button id="toggle-btn">☰</button>
.sidebar{position:fixed;left:0;top:0;width:260px;height:100vh;background:#1f2937;color:#fff}
.sidebar h3{padding:20px;text-align:center;border-bottom:1px solid #374151}
.sidebar a{display:block;padding:14px 20px;color:#fff;text-decoration:none}
.sidebar a:hover{background:#2563eb}
.main{margin-left:260px}
.topbar{background:#fff;padding:15px 25px;box-shadow:0 2px 10px rgba(0,0,0,.08)}
.card-box{border:none;border-radius:15px;color:#fff}
.sidebar{
    position: fixed;
    left: 0;
    top: 0;
    width: 260px;
    height: 100vh;
    background: #1f2937;
    color: #fff;
    overflow-y: auto;
    overflow-x: hidden;
}

</style>
<link href="admin_panel.css" rel="stylesheet">
</head>
<body>
<div class="sidebar" id="adminSidebar">
<h3><i class="fa-solid fa-user-shield"></i> <?php echo htmlspecialchars($admin_name); ?></h3>

<div class="sidebar-section-title">Main</div>
<a href="dashboard.php" class="active"><i class="fa fa-gauge"></i> Dashboard</a>
<a href="employee.php"><i class="fa fa-users"></i> Employees</a>
<a href="add_employee.php"><i class="fa fa-user-plus"></i> Add Employee</a>
<a href="leave_requests.php"><i class="fa fa-calendar-check"></i> Leave Requests</a>
<a href="attendance_report.php"><i class="fa fa-clock"></i> Attendance</a>
<a href="reports.php"><i class="fa fa-chart-column"></i> Reports</a>

<div class="sidebar-section-title">Payroll</div>

<a href="payroll_dashboard.php">
    <i class="fa-solid fa-money-bill-wave"></i> Payroll Dashboard
</a>

<a href="generate_payroll.php">
    <i class="fa fa-file-invoice-dollar"></i>
    Generate Payroll
</a>

<a href="payroll_history.php">
    <i class="fa fa-clock-rotate-left"></i>
    Payroll History
</a>

<a href="salary_components.php">
    <i class="fa-solid fa-wallet"></i> Salary Components
</a>

<a href="salary_slips.php">
    <i class="fa-solid fa-file-pdf"></i> Salary Slips
</a>

<a href="payroll_reports.php">
    <i class="fa-solid fa-chart-line"></i> Payroll Reports
</a>
<a href="salary_structure.php">
    <i class="fa fa-money-bill-wave"></i> Salary Structure
</a>

<a href="monthly_payroll.php">
<i class="fa fa-calendar"></i>
Monthly Payroll
</a>

<div class="sidebar-section-title">System</div>
<a href="add_notice.php"><i class="fa fa-bullhorn"></i> Notices</a>
<a href="add_holiday.php"><i class="fa fa-plane"></i> Holidays</a>
<a href="change_password.php"><i class="fa fa-key"></i> Change Password</a>
<a href="logout.php"><i class="fa fa-right-from-bracket"></i> Logout</a>
</div>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="main">
<div class="topbar">
<div class="topbar-left">
<button type="button" class="btn btn-outline-primary sidebar-toggle-btn" id="sidebarToggle" aria-label="Toggle sidebar" aria-expanded="true">
<i class="fa fa-bars"></i>
</button>
<h3>Welcome <?php echo htmlspecialchars($admin_name); ?></h3>
</div>
<div class="topbar-actions">
<span class="topbar-date"><?=date('d M Y')?></span>
<span class="admin-pill"><i class="fa fa-user-shield"></i> <?php echo htmlspecialchars($admin_name); ?></span>
<?php $darkModeInTopbar = true; include("../dark_mode.php"); ?>
<a href="logout.php" class="btn btn-danger btn-sm">
<i class="fa fa-right-from-bracket"></i> Logout
</a>
</div>
</div>
<div class="container-fluid mt-4">
<div class="row g-3">
<?php
$cards=[
['Employees',$totalEmployees,'primary','users'],
['Leaves',$totalLeaves,'success','file'],
['Attendance',$todayAttendance,'info','clock'],
['Approved',$approvedLeaves,'success','circle-check'],
['Pending',$pendingLeaves,'warning','hourglass-half'],
['Rejected',$rejectedLeaves,'danger','circle-xmark']
];
foreach($cards as $c){
echo '<div class="col-md-4"><div class="card card-box bg-'.$c[2].'"><div class="card-body"><h5>'.$c[0].'</h5><h2>'.$c[1].'</h2><i class="fa-solid fa-'.$c[3].' fa-2x float-end"></i></div></div></div>';
}
?>
</div>

<?php
$deptChart=mysqli_query($conn,"SELECT department,COUNT(*) total FROM employees GROUP BY department");
$labels=[];$values=[];
while($r=mysqli_fetch_assoc($deptChart)){ $labels[]=$r['department']?:'Unassigned'; $values[]=(int)$r['total'];}
?>
<div class='row mt-4'>
<div class='col-lg-6'><div class='card'><div class='card-header bg-primary text-white'>Department Wise Employees</div><div class='card-body'><canvas id='deptChart'></canvas></div></div></div>
<div class='col-lg-6'><div class='card'><div class='card-header bg-success text-white'>Leave Statistics</div><div class='card-body'><canvas id='leaveChart'></canvas></div></div></div>
</div>
<script src='https://cdn.jsdelivr.net/npm/chart.js'></script>
<script>
new Chart(document.getElementById('deptChart'),{type:'bar',data:{labels:<?=json_encode($labels)?>,datasets:[{label:'Employees',data:<?=json_encode($values)?>}]}});

new Chart(document.getElementById('leaveChart'),{type:'doughnut',data:{labels:['Approved','Pending','Rejected'],datasets:[{data:[<?=$approvedLeaves?>,<?=$pendingLeaves?>,<?=$rejectedLeaves?>]}]}});
</script>

<div class="row mt-4">
<div class="col-lg-6">
<div class="card">
<div class="card-header bg-primary text-white">Latest Employees</div>
<div class="card-body table-responsive">
<table class="table table-hover">
<thead><tr><th>ID</th><th>Name</th><th>Department</th><th>Joining</th></tr></thead>
<tbody>
<?php while($emp=mysqli_fetch_assoc($latestEmployees)){ ?>
<tr>
<td><?=htmlspecialchars($emp['employee_id'])?></td>
<td><?=htmlspecialchars($emp['full_name'])?></td>
<td><?=htmlspecialchars($emp['department'])?></td>
<td><?=date('d-m-Y',strtotime($emp['joining_date']))?></td>
</tr>
<?php } ?>
</tbody>
</table>
</div>
</div>
</div>

<div class="col-lg-6">
<div class="card">
<div class="card-header bg-success text-white">Latest Leave Requests</div>
<div class="card-body table-responsive">
<table class="table table-striped">
<thead><tr><th>Employee</th><th>Type</th><th>Status</th></tr></thead>
<tbody>
<?php while($leave=mysqli_fetch_assoc($latestLeaves)){ ?>
<tr>
<td><?=htmlspecialchars($leave['full_name'])?></td>
<td><?=htmlspecialchars($leave['leave_type'])?></td>
<td>
<?php
if($leave['status']=='Approved') echo "<span class='badge bg-success'>Approved</span>";
elseif($leave['status']=='Pending') echo "<span class='badge bg-warning text-dark'>Pending</span>";
else echo "<span class='badge bg-danger'>Rejected</span>";
?>
</td>
</tr>
<?php } ?>
</tbody>
</table>
</div></div></div>
</div>

<div class="row mt-4">
<div class="col-lg-6">
<div class="card">
<div class="card-header bg-dark text-white">Latest Notices</div>
<div class="card-body">
<?php while($notice=mysqli_fetch_assoc($latestNotices)){ ?>
<div class="border-bottom mb-3 pb-2">
<h6><?=htmlspecialchars($notice['title'])?></h6>
<p><?=htmlspecialchars($notice['notice'])?></p>
<small><?=date('d-m-Y',strtotime($notice['created_at']))?></small>
</div>
<?php } ?>

</div>

</div>

</div>

<div class="col-lg-6">
<div class="card">
<div class="card-header bg-info text-white">Upcoming Holidays</div>
<div class="card-body">
<?php while($holiday=mysqli_fetch_assoc($upcomingHolidays)){ ?>
<div class="border-bottom mb-3 pb-2">
<h6><?=htmlspecialchars($holiday['holiday_name'])?></h6>
<p><?=htmlspecialchars($holiday['description'])?></p>
<small><?=date('d-m-Y',strtotime($holiday['holiday_date']))?></small>
</div>
<?php } ?>

</div>

</div>

</div>

</div>

<div class="card mt-4">
<div class="card-header bg-primary text-white">Quick Actions</div>
<div class="card-body">
<a href="add_employee.php" class="btn btn-primary">Add Employee</a>
<a href="leave_requests.php" class="btn btn-success">Approve Leaves</a>
<a href="attendance_report.php" class="btn btn-warning">Attendance</a>
<a href="reports.php" class="btn btn-dark">Reports</a>
</div>

</div>

<footer class="text-center mt-5 mb-3 text-muted">
Employee Management System © 2026 | Developed by Minam Shaikh

</footer>

</div>

<script>
const sidebar = document.getElementById("adminSidebar");
const sidebarToggle = document.getElementById("sidebarToggle");
const sidebarBackdrop = document.getElementById("sidebarBackdrop");

if (sidebarToggle) {
    sidebarToggle.addEventListener("click", function(){
        const isMobile = window.matchMedia("(max-width: 991px)").matches;

        if (isMobile) {
            const isOpen = sidebar.classList.toggle("open");
            sidebarBackdrop.classList.toggle("show", isOpen);
            sidebarToggle.setAttribute("aria-expanded", String(isOpen));
            return;
        }

        const isCollapsed = sidebar.classList.toggle("sidebar-collapsed");
        sidebar.style.transform = isCollapsed ? "translateX(-100%)" : "translateX(0)";
        document.querySelector(".main").style.marginLeft = isCollapsed ? "0" : "260px";
        sidebarToggle.setAttribute("aria-expanded", String(!isCollapsed));
    });
}

if (sidebarBackdrop) {
    sidebarBackdrop.addEventListener("click", function(){
        sidebar.classList.remove("open");
        sidebarBackdrop.classList.remove("show");
        sidebarToggle.setAttribute("aria-expanded", "false");
    });
}
</script>

</body>

</html>
