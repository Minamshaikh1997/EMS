<?php
session_start();

include("admincheck_role.php");
include("../config/db.php");

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.html");
    exit();
}

$month_filter = "";

if (isset($_GET['salary_month']) && $_GET['salary_month'] != "") {
    $month_filter = $_GET['salary_month'];
}

$where = "";

if ($month_filter != "") {
    $where = "WHERE ss.salary_month='$month_filter'";
}

$payrollReport = mysqli_query($conn, "
    SELECT
        ss.*,
        e.employee_id AS emp_code,
        e.full_name
    FROM salary_slips ss
    INNER JOIN employees e
    ON ss.employee_id = e.id
    $where
    ORDER BY ss.salary_month DESC, e.full_name ASC
");

$total_basic = 0;
$total_allowance = 0;
$total_deduction = 0;
$total_gross = 0;
$total_net = 0;

if (isset($_GET['export']) && $_GET['export'] == "csv") {
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=payroll_report.csv");

    $output = fopen("php://output", "w");

    fputcsv($output, [
        "Employee ID",
        "Name",
        "Month",
        "Basic",
        "Allowance",
        "Deduction",
        "Gross Salary",
        "Net Salary"
    ]);

    while ($row = mysqli_fetch_assoc($payrollReport)) {
        fputcsv($output, [
            $row['emp_code'],
            $row['full_name'],
            date('F Y', strtotime($row['salary_month'] . '-01')),
            $row['basic_salary'],
            $row['allowance'],
            $row['deduction'],
            $row['gross_salary'],
            $row['net_salary']
        ]);
    }

    fclose($output);
    exit();
}

$componentWhere = "";

if ($month_filter != "") {
    $componentWhere = "WHERE ss.salary_month='$month_filter'";
}

$componentReport = mysqli_query($conn, "
    SELECT
        sc.component_name,
        sc.component_type,
        SUM(ssc.amount) AS total_amount
    FROM salary_slips ss
    INNER JOIN salary_structure s ON ss.employee_id = s.employee_id
    INNER JOIN salary_structure_components ssc ON s.id = ssc.salary_structure_id
    INNER JOIN salary_components sc ON ssc.component_id = sc.id
    $componentWhere
    GROUP BY sc.id, sc.component_name, sc.component_type
    ORDER BY sc.component_type ASC, sc.component_name ASC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Payroll Report</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

<style>
body{
    background:#f4f7fc;
}

.sidebar{
    position:fixed;
    left:0;
    top:0;
    width:260px;
    height:100vh;
    background:#1f2937;
    overflow-y:auto;
}

.sidebar h3{
    color:white;
    text-align:center;
    padding:20px;
}

.sidebar a{
    display:block;
    color:white;
    text-decoration:none;
    padding:14px 20px;
}

.sidebar a:hover{
    background:#2563eb;
}

.main{
    margin-left:260px;
}

.topbar{
    background:white;
    padding:15px 25px;
    box-shadow:0 2px 8px rgba(0,0,0,.1);
}

.card{
    border:none;
    border-radius:15px;
}

@media print{
    .sidebar,
    .topbar,
    .filter-box,
    .print-btn,
    .export-btn{
        display:none;
    }

    .main{
        margin-left:0;
    }

    body{
        background:white;
    }

    .card{
        box-shadow:none !important;
        border:none;
    }
}
</style>
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
<a href="payroll_reports.php" class="active">
    <i class="fa fa-chart-line"></i> Payroll Report
</a>
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
<h3>Payroll Report</h3>
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

<div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">

<h4 class="mb-0"><i class="fa fa-chart-line"></i> Payroll Report</h4>

<div>
    <a
    href="payroll_reports.php?salary_month=<?php echo $month_filter; ?>&export=csv"
    class="btn btn-success btn-sm export-btn">
        <i class="fa fa-file-excel"></i> Export CSV
    </a>

    <button onclick="window.print()" class="btn btn-light btn-sm print-btn">
        <i class="fa fa-print"></i> Print
    </button>
</div>

</div>

<div class="card-body">

<div class="filter-box mb-4">

<form method="GET" class="row">

<div class="col-md-4">
<label class="form-label">Filter by Month</label>
<input
type="month"
name="salary_month"
class="form-control"
value="<?php echo $month_filter; ?>">
</div>

<div class="col-md-3 d-grid">
<label>&nbsp;</label>
<button type="submit" class="btn btn-primary">
    <i class="fa fa-search"></i> Filter
</button>
</div>

<div class="col-md-3 d-grid">
<label>&nbsp;</label>
<a href="payroll_reports.php" class="btn btn-secondary">
    <i class="fa fa-refresh"></i> Reset
</a>
</div>

</form>

</div>

<?php if ($month_filter != "") { ?>

<h5 class="mb-3">
Report Month: <?php echo date('F Y', strtotime($month_filter . '-01')); ?>
</h5>

<?php } else { ?>

<h5 class="mb-3">All Payroll Records</h5>

<?php } ?>

<div class="table-responsive">

<table class="table table-bordered table-hover">

<thead class="table-dark">

<tr>
<th>Employee ID</th>
<th>Name</th>
<th>Month</th>
<th>Basic</th>
<th>Allowance</th>
<th>Deduction</th>
<th>Gross Salary</th>
<th>Net Salary</th>
</tr>

</thead>

<tbody>

<?php while($row = mysqli_fetch_assoc($payrollReport)){ ?>

<?php
$total_basic += $row['basic_salary'];
$total_allowance += $row['allowance'];
$total_deduction += $row['deduction'];
$total_gross += $row['gross_salary'];
$total_net += $row['net_salary'];
?>

<tr>

<td><?php echo $row['emp_code']; ?></td>

<td><?php echo $row['full_name']; ?></td>

<td><?php echo date('F Y', strtotime($row['salary_month'] . '-01')); ?></td>

<td><?php echo number_format($row['basic_salary'], 2); ?></td>

<td><?php echo number_format($row['allowance'], 2); ?></td>

<td><?php echo number_format($row['deduction'], 2); ?></td>

<td class="text-success fw-bold">
<?php echo number_format($row['gross_salary'], 2); ?>
</td>

<td class="text-primary fw-bold">
<?php echo number_format($row['net_salary'], 2); ?>
</td>

</tr>

<?php } ?>

</tbody>

<tfoot class="table-secondary fw-bold">

<tr>
<td colspan="3" class="text-end">Total</td>
<td><?php echo number_format($total_basic, 2); ?></td>
<td><?php echo number_format($total_allowance, 2); ?></td>
<td><?php echo number_format($total_deduction, 2); ?></td>
<td><?php echo number_format($total_gross, 2); ?></td>
<td><?php echo number_format($total_net, 2); ?></td>
</tr>

</tfoot>

</table>

</div>

<hr>

<h5 class="mb-3">Component Wise Summary</h5>

<div class="table-responsive">

<table class="table table-bordered table-hover">

<thead class="table-dark">
<tr>
<th>Component Name</th>
<th>Type</th>
<th>Total Amount</th>
</tr>
</thead>

<tbody>

<?php while($component = mysqli_fetch_assoc($componentReport)){ ?>

<tr>
<td><?php echo $component['component_name']; ?></td>
<td><?php echo $component['component_type']; ?></td>
<td class="fw-bold">
<?php echo number_format($component['total_amount'], 2); ?>
</td>
</tr>

<?php } ?>

</tbody>

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
