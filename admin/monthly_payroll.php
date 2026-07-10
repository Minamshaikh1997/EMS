<?php
session_start();

include("admincheck_role.php");
include("../config/db.php");

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.html");
    exit();
}

$month = date('F');
$year = date('Y');

if (isset($_GET['month']) && $_GET['month'] != "") {
    $month = $_GET['month'];
}

if (isset($_GET['year']) && $_GET['year'] != "") {
    $year = intval($_GET['year']);
}

$payroll = mysqli_query($conn, "
    SELECT
        p.*,
        e.employee_id,
        e.full_name
    FROM payroll p
    INNER JOIN employees e
    ON p.employee_id = e.id
    WHERE p.payroll_month='$month'
    AND p.payroll_year='$year'
    ORDER BY e.full_name
");

$months = [
    "January", "February", "March", "April", "May", "June",
    "July", "August", "September", "October", "November", "December"
];

$totalGross = 0;
$totalDeduction = 0;
$totalNet = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Monthly Payroll</title>
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
<a href="salary_components.php"><i class="fa fa-list-check"></i> Salary Components</a>
<a href="salary_slips.php"><i class="fa fa-file-invoice-dollar"></i> Salary Slips</a>
<a href="payroll_reports.php"><i class="fa fa-chart-line"></i> Payroll Report</a>
<a href="monthly_payroll.php" class="active"><i class="fa fa-calendar"></i> Monthly Payroll</a>

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
<h3>Monthly Payroll</h3>
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

<div class="card shadow mb-4">
<div class="card-header bg-primary text-white">
<h4 class="mb-0"><i class="fa fa-calendar"></i> Monthly Payroll</h4>
</div>

<div class="card-body">
<form method="GET" class="row g-3 align-items-end">

<div class="col-md-4">
<label class="form-label">Month</label>
<select name="month" class="form-select">
<?php foreach ($months as $m) { ?>
<option value="<?php echo $m; ?>" <?php echo ($month == $m) ? "selected" : ""; ?>>
<?php echo $m; ?>
</option>
<?php } ?>
</select>
</div>

<div class="col-md-3">
<label class="form-label">Year</label>
<input
type="number"
name="year"
class="form-control"
value="<?php echo $year; ?>">
</div>

<div class="col-md-2 d-grid">
<button class="btn btn-primary">
<i class="fa fa-search"></i> Search
</button>
</div>

<div class="col-md-2 d-grid">
<a href="monthly_payroll.php" class="btn btn-secondary">
<i class="fa fa-refresh"></i> Reset
</a>
</div>

</form>
</div>
</div>

<div class="card shadow">
<div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
<h5 class="mb-0">Payroll for <?php echo $month . " " . $year; ?></h5>
<button onclick="window.print()" class="btn btn-light btn-sm">
<i class="fa fa-print"></i> Print
</button>
</div>

<div class="card-body">

<div class="table-responsive">
<table class="table table-bordered table-hover align-middle">

<thead class="table-dark">
<tr>
<th>Employee ID</th>
<th>Name</th>
<th>Gross Salary</th>
<th>Deductions</th>
<th>Net Salary</th>
<th>Status</th>
</tr>
</thead>

<tbody>

<?php while ($row = mysqli_fetch_assoc($payroll)) { ?>

<?php
$grossSalary = isset($row['gross_salary']) ? $row['gross_salary'] : 0;
$deductions = isset($row['deductions']) ? $row['deductions'] : 0;
$netSalary = isset($row['net_salary']) ? $row['net_salary'] : 0;

$totalGross += $grossSalary;
$totalDeduction += $deductions;
$totalNet += $netSalary;
?>

<tr>
<td><?php echo $row['employee_id']; ?></td>
<td><?php echo $row['full_name']; ?></td>
<td><?php echo number_format($grossSalary, 2); ?></td>
<td><?php echo number_format($deductions, 2); ?></td>
<td class="text-primary fw-bold"><?php echo number_format($netSalary, 2); ?></td>
<td>
<span class="badge bg-success">
<?php echo isset($row['status']) ? $row['status'] : "Generated"; ?>
</span>
</td>
</tr>

<?php } ?>

<?php if (mysqli_num_rows($payroll) == 0) { ?>
<tr>
<td colspan="6" class="text-center text-muted py-4">
No payroll records found for <?php echo $month . " " . $year; ?>.
</td>
</tr>
<?php } ?>

</tbody>

<tfoot class="table-secondary fw-bold">
<tr>
<td colspan="2" class="text-end">Total</td>
<td><?php echo number_format($totalGross, 2); ?></td>
<td><?php echo number_format($totalDeduction, 2); ?></td>
<td><?php echo number_format($totalNet, 2); ?></td>
<td></td>
</tr>
</tfoot>

</table>
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
