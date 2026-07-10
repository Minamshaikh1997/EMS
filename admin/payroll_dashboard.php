<?php
session_start();

include("admincheck_role.php");
include("../config/db.php");

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.html");
    exit();
}

$currentMonth = date('F');
$currentYear = date('Y');

$totalEmployees = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM employees
"))['total'];

$salaryStructures = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM salary_structure
"))['total'];

$salarySlips = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM salary_slips
"))['total'];

$currentPayroll = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        COUNT(*) AS total_records,
        IFNULL(SUM(basic_salary + allowances + overtime + bonus), 0) AS total_gross,
        IFNULL(SUM(deductions + tax), 0) AS total_deductions,
        IFNULL(SUM(net_salary), 0) AS total_net
    FROM payroll
    WHERE payroll_month='$currentMonth'
    AND payroll_year='$currentYear'
"));

$pendingPayroll = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM payroll
    WHERE payment_status='Pending'
"))['total'];

$paidPayroll = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM payroll
    WHERE payment_status='Paid'
"))['total'];

$latestPayroll = mysqli_query($conn, "
    SELECT
        p.*,
        e.employee_id,
        e.full_name
    FROM payroll p
    INNER JOIN employees e ON p.employee_id = e.id
    ORDER BY p.id DESC
    LIMIT 8
");

$componentSummary = mysqli_query($conn, "
    SELECT
        sc.component_name,
        sc.component_type,
        COUNT(ssc.id) AS used_count,
        IFNULL(SUM(ssc.amount), 0) AS total_amount
    FROM salary_components sc
    LEFT JOIN salary_structure_components ssc ON sc.id = ssc.component_id
    GROUP BY sc.id, sc.component_name, sc.component_type
    ORDER BY sc.component_type ASC, sc.component_name ASC
    LIMIT 8
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payroll Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="admin_panel.css" rel="stylesheet">
<style>
.metric-card {
    border: none;
    border-radius: 15px;
    color: white;
}

.metric-card .card-body {
    min-height: 120px;
}

.metric-card i {
    opacity: .85;
}
</style>
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
<a href="payroll_dashboard.php" class="active"><i class="fa-solid fa-money-bill-wave"></i> Payroll Dashboard</a>
<a href="generate_payroll.php"><i class="fa fa-file-invoice-dollar"></i> Generate Payroll</a>
<a href="payroll_history.php"><i class="fa fa-clock-rotate-left"></i> Payroll History</a>
<a href="salary_structure.php"><i class="fa fa-money-bill-wave"></i> Salary Structure</a>
<a href="salary_components.php"><i class="fa fa-list-check"></i> Salary Components</a>
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
<h3>Payroll Dashboard</h3>
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

<div class="row g-3">

<div class="col-md-6 col-xl-3">
<div class="card metric-card bg-primary shadow">
<div class="card-body">
<div class="d-flex justify-content-between">
<div>
<h6>Total Employees</h6>
<h2><?php echo $totalEmployees; ?></h2>
</div>
<i class="fa fa-users fa-2x"></i>
</div>
</div>
</div>
</div>

<div class="col-md-6 col-xl-3">
<div class="card metric-card bg-success shadow">
<div class="card-body">
<div class="d-flex justify-content-between">
<div>
<h6>Salary Structures</h6>
<h2><?php echo $salaryStructures; ?></h2>
</div>
<i class="fa fa-money-bill-wave fa-2x"></i>
</div>
</div>
</div>
</div>

<div class="col-md-6 col-xl-3">
<div class="card metric-card bg-warning shadow">
<div class="card-body">
<div class="d-flex justify-content-between">
<div>
<h6>Pending Payroll</h6>
<h2><?php echo $pendingPayroll; ?></h2>
</div>
<i class="fa fa-hourglass-half fa-2x"></i>
</div>
</div>
</div>
</div>

<div class="col-md-6 col-xl-3">
<div class="card metric-card bg-info shadow">
<div class="card-body">
<div class="d-flex justify-content-between">
<div>
<h6>Salary Slips</h6>
<h2><?php echo $salarySlips; ?></h2>
</div>
<i class="fa fa-file-invoice-dollar fa-2x"></i>
</div>
</div>
</div>
</div>

</div>

<div class="row g-3 mt-1">

<div class="col-lg-8">
<div class="card shadow h-100">
<div class="card-header bg-primary text-white">
<h5 class="mb-0">Current Month Summary - <?php echo $currentMonth . " " . $currentYear; ?></h5>
</div>
<div class="card-body">

<div class="row g-3">
<div class="col-md-3">
<div class="border rounded p-3">
<small class="text-muted">Records</small>
<h4><?php echo $currentPayroll['total_records']; ?></h4>
</div>
</div>

<div class="col-md-3">
<div class="border rounded p-3">
<small class="text-muted">Gross</small>
<h4><?php echo number_format($currentPayroll['total_gross'], 2); ?></h4>
</div>
</div>

<div class="col-md-3">
<div class="border rounded p-3">
<small class="text-muted">Deductions</small>
<h4><?php echo number_format($currentPayroll['total_deductions'], 2); ?></h4>
</div>
</div>

<div class="col-md-3">
<div class="border rounded p-3">
<small class="text-muted">Net Pay</small>
<h4 class="text-primary"><?php echo number_format($currentPayroll['total_net'], 2); ?></h4>
</div>
</div>
</div>

</div>
</div>
</div>

<div class="col-lg-4">
<div class="card shadow h-100">
<div class="card-header bg-success text-white">
<h5 class="mb-0">Quick Actions</h5>
</div>
<div class="card-body d-grid gap-2">
<a href="generate_payroll.php" class="btn btn-success">
<i class="fa fa-file-invoice-dollar"></i> Generate Payroll
</a>
<a href="salary_structure.php" class="btn btn-primary">
<i class="fa fa-money-bill-wave"></i> Salary Structure
</a>
<a href="salary_slips.php" class="btn btn-info text-white">
<i class="fa fa-file-invoice"></i> Salary Slips
</a>
<a href="payroll_reports.php" class="btn btn-dark">
<i class="fa fa-chart-line"></i> Payroll Reports
</a>
</div>
</div>
</div>

</div>

<div class="row g-3 mt-1">

<div class="col-lg-8">
<div class="card shadow">
<div class="card-header bg-dark text-white">
<h5 class="mb-0">Latest Payroll Records</h5>
</div>
<div class="card-body table-responsive">
<table class="table table-bordered table-hover align-middle">
<thead class="table-dark">
<tr>
<th>Employee</th>
<th>Month</th>
<th>Gross</th>
<th>Net</th>
<th>Status</th>
</tr>
</thead>
<tbody>
<?php while($row = mysqli_fetch_assoc($latestPayroll)){ ?>
<tr>
<td><?php echo $row['employee_id'] . " - " . $row['full_name']; ?></td>
<td><?php echo $row['payroll_month'] . " " . $row['payroll_year']; ?></td>
<td><?php echo number_format($row['basic_salary'] + $row['allowances'] + $row['overtime'] + $row['bonus'], 2); ?></td>
<td class="text-primary fw-bold"><?php echo number_format($row['net_salary'], 2); ?></td>
<td>
<?php if ($row['payment_status'] == "Paid") { ?>
<span class="badge bg-success">Paid</span>
<?php } else { ?>
<span class="badge bg-warning text-dark">Pending</span>
<?php } ?>
</td>
</tr>
<?php } ?>
</tbody>
</table>
</div>
</div>
</div>

<div class="col-lg-4">
<div class="card shadow">
<div class="card-header bg-secondary text-white">
<h5 class="mb-0">Salary Components</h5>
</div>
<div class="card-body table-responsive">
<table class="table table-sm table-bordered align-middle">
<thead>
<tr>
<th>Component</th>
<th>Type</th>
<th>Total</th>
</tr>
</thead>
<tbody>
<?php while($component = mysqli_fetch_assoc($componentSummary)){ ?>
<tr>
<td><?php echo $component['component_name']; ?></td>
<td><?php echo $component['component_type']; ?></td>
<td><?php echo number_format($component['total_amount'], 2); ?></td>
</tr>
<?php } ?>
</tbody>
</table>
</div>
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
