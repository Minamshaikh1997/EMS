<?php
session_start();

include("admincheck_role.php");
include("../config/db.php");

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Salary Components</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="admin_panel.css" rel="stylesheet">
</head>
<body>

<div class="sidebar" id="adminSidebar">

<h3><i class="fa-solid fa-user-shield"></i> <?php echo htmlspecialchars($admin_name); ?></h3>

<div class="sidebar-section-title">Main</div>
<a href="dashboard.php"><i class="fa fa-gauge"></i> Dashboard</a>
<a href="employee.php"><i class="fa fa-users"></i> Employees</a>
<a href="add_employee.php"><i class="fa fa-user-plus"></i> Add Employee</a>
<a href="leave_requests.php"><i class="fa fa-calendar-check"></i> Leave Requests</a>
<a href="attendance_report.php"><i class="fa fa-clock"></i> Attendance</a>
<a href="reports.php"><i class="fa fa-chart-column"></i> Reports</a>

<div class="sidebar-section-title">Payroll</div>
<a href="payroll_dashboard.php"><i class="fa-solid fa-money-bill-wave"></i> Payroll Dashboard</a>
<a href="generate_payroll.php"><i class="fa fa-file-invoice-dollar"></i> Generate Payroll</a>
<a href="payroll_history.php"><i class="fa fa-clock-rotate-left"></i> Payroll History</a>
<a href="salary_structure.php"><i class="fa fa-money-bill-wave"></i> Salary Structure</a>
<a href="salary_components.php" class="active"><i class="fa fa-list-check"></i> Salary Components</a>
<a href="salary_slips.php"><i class="fa fa-file-invoice-dollar"></i> Salary Slips</a>
<a href="payroll_reports.php"><i class="fa fa-chart-line"></i> Payroll Report</a>
<a href="monthly_payroll.php"><i class="fa fa-calendar"></i> Monthly Payroll</a>

<div class="sidebar-section-title">System</div>
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
<h3>Salary Components</h3>
</div>

<div class="topbar-actions">
<span class="topbar-date"><?php echo date('d M Y'); ?></span>
<span class="admin-pill"><i class="fa fa-user-shield"></i> <?php echo htmlspecialchars($admin_name); ?></span>
<a href="logout.php" class="btn btn-danger btn-sm">
<i class="fa fa-right-from-bracket"></i> Logout
</a>
</div>
</div>

<div class="container-fluid mt-4">

<div class="card shadow">
<div class="card-header bg-primary text-white">
<h4 class="mb-0"><i class="fa fa-list-check"></i> Salary Components</h4>
</div>

<div class="card-body">
<div class="alert alert-info mb-0">
Salary components are connected with Salary Structure. Add or manage components from your database until the full management form is added here.
</div>
</div>
</div>

</div>
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
            return;
        }
        const isCollapsed = sidebar.classList.toggle("sidebar-collapsed");
        sidebar.style.transform = isCollapsed ? "translateX(-100%)" : "translateX(0)";
        document.querySelector(".main").style.marginLeft = isCollapsed ? "0" : "260px";
    });
}

if (sidebarBackdrop) {
    sidebarBackdrop.addEventListener("click", function(){
        sidebar.classList.remove("open");
        sidebarBackdrop.classList.remove("show");
    });
}
</script>

</body>
</html>
