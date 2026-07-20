<?php
session_start();

include("admincheck_role.php");
include("../config/db.php");

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.html");
    exit();
}

$message = "";

if (isset($_POST['generate'])) {
    $employee_id = intval($_POST['employee_id']);
    $month = mysqli_real_escape_string($conn, $_POST['month']);
    $year = intval($_POST['year']);

    $check = mysqli_query($conn, "
        SELECT id FROM payroll
        WHERE employee_id='$employee_id'
        AND payroll_month='$month'
        AND payroll_year='$year'
    ");

    if (mysqli_num_rows($check) > 0) {
        $message = "<div class='alert alert-warning alert-dismissible fade show rounded-pill px-4'><i class='fa fa-exclamation-circle me-2'></i> Payroll already generated for this month.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        $salary = mysqli_query($conn, "
            SELECT id, basic_salary
            FROM salary_structure
            WHERE employee_id='$employee_id'
            LIMIT 1
        ");

        if (mysqli_num_rows($salary) == 0) {
            $message = "<div class='alert alert-danger alert-dismissible fade show rounded-pill px-4'><i class='fa fa-exclamation-circle me-2'></i> Salary Structure not found.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        } else {
            $row = mysqli_fetch_assoc($salary);
            $salary_structure_id = $row['id'];

            $allowanceResult = mysqli_query($conn, "
                SELECT IFNULL(SUM(ssc.amount), 0) AS total_allowance
                FROM salary_structure_components ssc
                INNER JOIN salary_components sc ON ssc.component_id = sc.id
                WHERE ssc.salary_structure_id='$salary_structure_id'
                AND sc.component_type='Allowance'
            ");

            $deductionResult = mysqli_query($conn, "
                SELECT IFNULL(SUM(ssc.amount), 0) AS total_deduction
                FROM salary_structure_components ssc
                INNER JOIN salary_components sc ON ssc.component_id = sc.id
                WHERE ssc.salary_structure_id='$salary_structure_id'
                AND sc.component_type='Deduction'
            ");

            $allowanceRow = mysqli_fetch_assoc($allowanceResult);
            $deductionRow = mysqli_fetch_assoc($deductionResult);

            $basic_salary = $row['basic_salary'];
            $allowances = $allowanceRow['total_allowance'];
            $deductions = $deductionRow['total_deduction'];
            $overtime = 0;
            $bonus = 0;
            $tax = 0;
            $net_salary = ($basic_salary + $allowances + $overtime + $bonus) - ($deductions + $tax);

            mysqli_query($conn, "
                INSERT INTO payroll
                (
                    employee_id,
                    payroll_month,
                    payroll_year,
                    basic_salary,
                    allowances,
                    overtime,
                    bonus,
                    deductions,
                    tax,
                    net_salary,
                    payment_status
                )
                VALUES
                (
                    '$employee_id',
                    '$month',
                    '$year',
                    '$basic_salary',
                    '$allowances',
                    '$overtime',
                    '$bonus',
                    '$deductions',
                    '$tax',
                    '$net_salary',
                    'Pending'
                )
            ");

            $message = "<div class='alert alert-success alert-dismissible fade show rounded-pill px-4'><i class='fa fa-check-circle me-2'></i> Payroll Generated Successfully.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    }
}

$employees = mysqli_query($conn, "
    SELECT id, employee_id, full_name
    FROM employees
    ORDER BY full_name ASC
");

$months = [
    "January","February","March","April","May","June",
    "July","August","September","October","November","December"
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Generate Payroll - EMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="admin_panel.css" rel="stylesheet">
<style>
:root {
    --primary: #2563eb; --primary-dark: #1d4ed8; --success: #10b981; --warning: #f59e0b; --danger: #ef4444; --info: #06b6d4;
    --gray-50: #f8fafc; --gray-100: #f1f5f9; --gray-200: #e2e8f0; --gray-300: #cbd5e1; --gray-400: #94a3b8; --gray-500: #64748b;
    --gray-600: #475569; --gray-700: #334155; --gray-800: #1e293b; --gray-900: #0f172a;
    --radius: 16px; --radius-sm: 10px;
    --shadow: 0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.06);
    --shadow-md: 0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -1px rgba(0,0,0,.06);
}
body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: var(--gray-50); color: var(--gray-800); overflow-x: hidden; }
.card-modern { background: white; border-radius: var(--radius); border: 1px solid var(--gray-200); box-shadow: var(--shadow); overflow: hidden; }
.card-modern .card-header-custom { padding: 16px 24px; border-bottom: 1px solid var(--gray-200); display: flex; align-items: center; justify-content: space-between; background: var(--gray-50); flex-wrap: wrap; gap: 12px; }
.card-modern .card-header-custom h5 { font-size: 16px; font-weight: 700; color: var(--gray-800); margin: 0; display: flex; align-items: center; gap: 8px; }
.card-modern .card-header-custom h5 i { color: var(--primary); }
.card-modern .card-body-custom { padding: 24px; }
.form-label { font-size: 13px; font-weight: 600; color: var(--gray-700); margin-bottom: 4px; }
.form-control, .form-select { border-radius: var(--radius-sm); border: 1px solid var(--gray-200); font-size: 14px; padding: 10px 14px; }
.form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
body.dark-mode { background: #0f172a; color: #e2e8f0; }
.dark-mode .card-modern { background: #1e293b; border-color: rgba(255,255,255,.08); }
.dark-mode .card-modern .card-header-custom { background: rgba(255,255,255,.04); border-color: rgba(255,255,255,.08); }
.dark-mode .card-modern .card-header-custom h5 { color: #e2e8f0; }
.dark-mode .form-label { color: var(--gray-300); }
.dark-mode .form-control, .dark-mode .form-select { background: rgba(255,255,255,.06); border-color: rgba(255,255,255,.1); color: #e2e8f0; }
.dark-mode .form-control:focus, .dark-mode .form-select:focus { background: rgba(255,255,255,.08); color: #e2e8f0; }
@media (max-width: 768px) { .page-content { padding: 16px; } }
</style>
</head>
<body>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="fa-solid fa-building"></i></div>
        <div class="brand-text">EMS <small>Employee Management</small></div>
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
        <a href="dashboard.php" class="sidebar-link"><i class="fa fa-gauge"></i> Dashboard</a>
        <a href="employee.php" class="sidebar-link"><i class="fa fa-users"></i> Employees</a>
        <a href="add_employee.php" class="sidebar-link"><i class="fa fa-user-plus"></i> Add Employee</a>
        <a href="leave_requests.php" class="sidebar-link"><i class="fa fa-calendar-check"></i> Leave Requests</a>
        <a href="supervisor_adjustments.php" class="sidebar-link"><i class="fa fa-user-tie"></i> Supervisor Adjustments</a>
        <a href="admin_adjustments.php" class="sidebar-link"><i class="fa fa-shield-alt"></i> Admin Adjustments</a>
        <a href="manage_shifts.php" class="sidebar-link"><i class="fa fa-clock-rotate-left"></i> Manage Shifts</a>
        <a href="attendance_report.php" class="sidebar-link"><i class="fa fa-clock"></i> Attendance</a>
        <a href="reports.php" class="sidebar-link"><i class="fa fa-chart-column"></i> Reports</a>
        <div class="sidebar-section-title">Payroll</div>
        <a href="payroll_dashboard.php" class="sidebar-link"><i class="fa-solid fa-money-bill-wave"></i> Payroll Dashboard</a>
        <a href="generate_payroll.php" class="sidebar-link active"><i class="fa fa-file-invoice-dollar"></i> Generate Payroll</a>
        <a href="payroll_history.php" class="sidebar-link"><i class="fa fa-clock-rotate-left"></i> Payroll History</a>
        <a href="salary_components.php" class="sidebar-link"><i class="fa-solid fa-wallet"></i> Salary Components</a>
        <a href="salary_slips.php" class="sidebar-link"><i class="fa-solid fa-file-pdf"></i> Salary Slips</a>
        <a href="payroll_reports.php" class="sidebar-link"><i class="fa-solid fa-chart-line"></i> Payroll Reports</a>
        <a href="salary_structure.php" class="sidebar-link"><i class="fa fa-money-bill-wave"></i> Salary Structure</a>
        <a href="monthly_payroll.php" class="sidebar-link"><i class="fa fa-calendar"></i> Monthly Payroll</a>
        <div class="sidebar-section-title">System</div>
        <a href="add_notice.php" class="sidebar-link"><i class="fa fa-bullhorn"></i> Notices</a>
        <a href="add_holiday.php" class="sidebar-link"><i class="fa fa-plane"></i> Holidays</a>
        <a href="change_password.php" class="sidebar-link"><i class="fa fa-key"></i> Change Password</a>
        <a href="logout.php" class="sidebar-link"><i class="fa fa-right-from-bracket"></i> Logout</a>
    </nav>
</aside>

<div class="main-content" id="mainContent">
    <header class="header">
        <div class="header-left">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><i class="fa fa-bars"></i></button>
            <h4>Generate Payroll <span>/ Payroll Processing</span></h4>
        </div>
        <div class="header-right">
            <span class="header-date"><i class="fa-regular fa-calendar"></i> <?=date('d M Y')?></span>
            <span class="header-admin-badge"><i class="fa fa-user-shield"></i> <span><?php echo htmlspecialchars($admin_name); ?></span></span>
            <?php $darkModeInTopbar = true; include("../dark_mode.php"); ?>
            <a href="logout.php" class="btn btn-danger btn-sm rounded-pill px-3"><i class="fa fa-right-from-bracket"></i> <span>Logout</span></a>
        </div>
    </header>

    <div class="page-content">
        <div class="card-modern">
            <div class="card-header-custom">
                <h5><i class="fa fa-money-check-dollar"></i> Generate Payroll</h5>
            </div>
            <div class="card-body-custom">
                <?php echo $message; ?>
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Employee</label>
                            <select name="employee_id" class="form-select" required>
                                <option value="">Select Employee</option>
                                <?php while($emp = mysqli_fetch_assoc($employees)){ ?>
                                <option value="<?php echo $emp['id']; ?>"><?php echo $emp['employee_id']; ?> - <?php echo $emp['full_name']; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Month</label>
                            <select name="month" class="form-select" required>
                                <?php foreach($months as $m){ ?>
                                <option value="<?php echo $m; ?>" <?php echo ($m == date('F')) ? 'selected' : ''; ?>><?php echo $m; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Year</label>
                            <input type="number" name="year" class="form-control" value="<?php echo date('Y'); ?>" required>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" name="generate" class="btn btn-success w-100 rounded-pill"><i class="fa fa-money-check-dollar"></i> Generate Payroll</button>
                        </div>
                    </div>
                </form>
                <div class="mt-4 d-flex gap-2">
                    <a href="payroll_dashboard.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="fa fa-arrow-left"></i> Back to Payroll</a>
                    <a href="dashboard.php" class="btn btn-outline-primary rounded-pill px-4"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebarBackdrop = document.getElementById('sidebarBackdrop');
sidebarToggle.addEventListener('click', function() {
    const isMobile = window.matchMedia('(max-width: 991px)').matches;
    if (isMobile) { sidebar.classList.toggle('open'); sidebarBackdrop.classList.toggle('show', isOpen); }
    else { document.body.classList.toggle('sidebar-collapsed'); }
});
sidebarBackdrop.addEventListener('click', function() { sidebar.classList.remove('open'); sidebarBackdrop.classList.remove('show'); });
</script>
</body>
</html>