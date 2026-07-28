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

<aside class="sidebar" id="adminSidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="fa-solid fa-building"></i></div>
        <div class="brand-text">
            EMS
            <small>Employee Management</small>
        </div>
    </div>
    <div class="sidebar-user">
        <div class="user-avatar"><?php echo strtoupper(substr($admin_name, 0, 1)); ?></div>
        <div class="user-info">
            <div class="user-name"><?php echo htmlspecialchars($admin_name); ?></div>
            <div class="user-role"><?php echo htmlspecialchars($admin_role ?: 'Administrator'); ?></div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="sidebar-section-title">Main</div>
        <div class="sidebar-section-group">
            <a href="dashboard.php" class="sidebar-link"><i class="fa fa-gauge"></i> Dashboard</a>
            <a href="employee.php" class="sidebar-link"><i class="fa fa-users"></i> Employees</a>
            <a href="add_employee.php" class="sidebar-link"><i class="fa fa-user-plus"></i> Add Employee</a>
            <a href="leave_requests.php" class="sidebar-link"><i class="fa fa-calendar-check"></i> Leave Requests</a>
            <a href="attendance_report.php" class="sidebar-link"><i class="fa fa-clock"></i> Attendance</a>
            <a href="reports.php" class="sidebar-link"><i class="fa fa-chart-column"></i> Reports</a>
        </div>

        <div class="sidebar-section-title">Payroll</div>
        <div class="sidebar-section-group">
            <a href="payroll_dashboard.php" class="sidebar-link"><i class="fa-solid fa-money-bill-wave"></i> Payroll Dashboard</a>
            <a href="generate_payroll.php" class="sidebar-link"><i class="fa fa-file-invoice-dollar"></i> Generate Payroll</a>
            <a href="payroll_history.php" class="sidebar-link"><i class="fa fa-clock-rotate-left"></i> Payroll History</a>
            <a href="salary_structure.php" class="sidebar-link"><i class="fa fa-money-bill-wave"></i> Salary Structure</a>
            <a href="salary_components.php" class="sidebar-link"><i class="fa fa-list-check"></i> Salary Components</a>
            <a href="salary_slips.php" class="sidebar-link"><i class="fa fa-file-invoice-dollar"></i> Salary Slips</a>
            <a href="payroll_reports.php" class="sidebar-link"><i class="fa fa-chart-line"></i> Payroll Report</a>
            <a href="monthly_payroll.php" class="sidebar-link active"><i class="fa fa-calendar"></i> Monthly Payroll</a>
        </div>

        <div class="sidebar-section-title">System</div>
        <div class="sidebar-section-group">
            <a href="add_notice.php" class="sidebar-link"><i class="fa fa-bullhorn"></i> Notices</a>
            <a href="add_holiday.php" class="sidebar-link"><i class="fa fa-plane"></i> Holidays</a>
            <a href="send_email.php" class="sidebar-link"><i class="fa fa-envelope"></i> Send Email</a>
            <a href="change_password.php" class="sidebar-link"><i class="fa fa-key"></i> Change Password</a>
            <a href="logout.php" class="sidebar-link"><i class="fa fa-right-from-bracket"></i> Logout</a>
        </div>
    </nav>
</aside>

</div>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<div class="main-content" id="mainContent">

    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                <i class="fa fa-bars"></i>
            </button>
            <h4>Monthly Payroll <span>/ View</span></h4>
        </div>
        <div class="header-right">
            <span class="header-date"><i class="fa-regular fa-calendar"></i> <?=date('d M Y')?></span>
            <span class="header-admin-badge"><i class="fa fa-user-shield"></i> <span><?php echo htmlspecialchars($admin_name); ?></span></span>
            <?php $darkModeInTopbar = true; include("../dark_mode.php"); ?>
            <a href="logout.php" class="btn btn-danger btn-sm rounded-pill px-3">
                <i class="fa fa-right-from-bracket"></i> <span>Logout</span>
            </a>
        </div>
    </header>

<!-- Page Content -->
<div class="page-content">

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
<?php
$payStatus = $row['payment_status'] ?? 'Generated';
$badgeClass = ($payStatus == 'Paid') ? 'bg-success' : 'bg-warning text-dark';
?>
<span class="badge <?php echo $badgeClass; ?>">
<?php echo $payStatus; ?>
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
// Sidebar Toggle
const sidebar = document.getElementById('adminSidebar');
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebarBackdrop = document.getElementById('sidebarBackdrop');

sidebarToggle.addEventListener('click', function() {
    const isMobile = window.matchMedia('(max-width: 991px)').matches;
    if (isMobile) {
        const isOpen = sidebar.classList.toggle('open');
        sidebarBackdrop.classList.toggle('show', isOpen);
    } else {
        document.body.classList.toggle('sidebar-collapsed');
    }
});

sidebarBackdrop.addEventListener('click', function() {
    sidebar.classList.remove('open');
    sidebarBackdrop.classList.remove('show');
});

// Sidebar Category Collapse/Expand
document.querySelectorAll('.sidebar-nav > .sidebar-section-title').forEach(function(title) {
    // Add collapse icon with data-section attribute
    const sectionName = title.childNodes[0].textContent.trim();
    const icon = document.createElement('span');
    icon.className = 'section-collapse-icon';
    icon.textContent = '\u25BC';
    icon.setAttribute('data-section', sectionName);
    title.appendChild(icon);

    // Toggle on click
    title.addEventListener('click', function(e) {
        if (e.target.tagName === 'A') return;
        const group = this.querySelector('+ .sidebar-section-group') || this.nextElementSibling;
        if (!group || !group.classList.contains('sidebar-section-group')) return;

        const isCollapsed = group.classList.toggle('collapsed');
        const ico = this.querySelector('.section-collapse-icon');
        if (ico) ico.classList.toggle('collapsed', isCollapsed);
        localStorage.setItem('sidebar_' + sectionName, isCollapsed ? 'collapsed' : 'expanded');
    });

    // Restore state
    const saved = localStorage.getItem('sidebar_' + sectionName);
    const group = title.nextElementSibling;
    if (saved === 'collapsed' && group && group.classList.contains('sidebar-section-group')) {
        group.classList.add('collapsed');
        const ico = title.querySelector('.section-collapse-icon');
        if (ico) ico.classList.add('collapsed');
    }
});
</script>

</body>
</html>
