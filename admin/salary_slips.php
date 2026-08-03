<?php
session_start();

include("admincheck_role.php");
include("../config/db.php");
include_once("../config/audit.php");

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.html");
    exit();
}

if (!in_array($admin_role, ['Super Admin','Admin','Finance Manager','Accountant'], true)) { http_response_code(403); exit('Access denied.'); }

// Delete Salary Slip
if (isset($_POST['delete'])) {
    ems_verify_csrf();
    $id = intval($_POST['delete']);
    $stmt=$conn->prepare('DELETE FROM salary_slips WHERE id=?'); $stmt->bind_param('i',$id); $stmt->execute(); $stmt->close();
    ems_audit($conn,'salary_slip.deleted','salary_slip',$id);

    header("Location: salary_slips.php");
    exit();
}

// Save Salary Slip
if (isset($_POST['save_slip'])) {
    ems_verify_csrf();
    $employee_id = intval($_POST['employee_id']);
    $salary_month = trim((string)($_POST['salary_month'] ?? ''));
    if ($employee_id < 1 || !preg_match('/^\d{4}-\d{2}$/',$salary_month)) { http_response_code(422); exit('Invalid salary slip details.'); }

    $salary = mysqli_query($conn, "
        SELECT id, basic_salary
        FROM salary_structure
        WHERE employee_id='$employee_id'
    ");

    if (mysqli_num_rows($salary) > 0) {
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

        $allowance = $allowanceRow['total_allowance'];
        $deduction = $deductionRow['total_deduction'];

        $gross_salary = $row['basic_salary'] + $allowance;
        $net_salary = $gross_salary - $deduction;

        $check = mysqli_query($conn, "
            SELECT id FROM salary_slips
            WHERE employee_id='$employee_id'
            AND salary_month='$salary_month'
        ");

        if (mysqli_num_rows($check) > 0) {
            $message = "<div class='alert alert-warning'>Salary Slip already exists for this month.</div>";
        } else {
            $basic=(float)$row['basic_salary'];
            $stmt=$conn->prepare('INSERT INTO salary_slips (employee_id,salary_month,basic_salary,allowance,deduction,gross_salary,net_salary) VALUES (?,?,?,?,?,?,?)');
            $stmt->bind_param('isddddd',$employee_id,$salary_month,$basic,$allowance,$deduction,$gross_salary,$net_salary); $stmt->execute(); $slipId=(int)$conn->insert_id; $stmt->close();
            ems_audit($conn,'salary_slip.generated','salary_slip',$slipId,['employee_id'=>$employee_id,'month'=>$salary_month]);

            $message = "<div class='alert alert-success'>Salary Slip Generated Successfully.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Salary Structure not found for selected employee.</div>";
    }
}

// Employee List
$employees = mysqli_query($conn, "
    SELECT id, employee_id, full_name
    FROM employees
    ORDER BY full_name ASC
");

// Salary Slip List
$salarySlips = mysqli_query($conn, "
    SELECT
        ss.*,
        e.employee_id AS emp_code,
        e.full_name
    FROM salary_slips ss
    INNER JOIN employees e
    ON ss.employee_id = e.id
    ORDER BY ss.id DESC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Salary Slips</title>

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
        <a href="employee_rights_management.php" class="sidebar-link"><i class="fa fa-user-shield"></i> Employee Rights</a>
        <a href="leave_requests.php" class="sidebar-link"><i class="fa fa-calendar-check"></i> Leave Requests</a>
        <a href="supervisor_adjustments.php" class="sidebar-link"><i class="fa fa-user-tie"></i> Supervisor Adjustments</a>
        <a href="admin_adjustments.php" class="sidebar-link"><i class="fa fa-shield-alt"></i> Admin Adjustments</a>
        <a href="manage_shifts.php" class="sidebar-link"><i class="fa fa-clock-rotate-left"></i> Manage Shifts</a>
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
            <a href="salary_slips.php" class="sidebar-link active"><i class="fa fa-file-invoice-dollar"></i> Salary Slips</a>
            <a href="payroll_reports.php" class="sidebar-link"><i class="fa fa-chart-line"></i> Payroll Report</a>
            <a href="monthly_payroll.php" class="sidebar-link"><i class="fa fa-calendar"></i> Monthly Payroll</a>
        </div>

        <div class="sidebar-section-title">System</div>
        <div class="sidebar-section-group">
            <a href="add_notice.php" class="sidebar-link"><i class="fa fa-bullhorn"></i> Notices</a>
            <a href="add_holiday.php" class="sidebar-link"><i class="fa fa-plane"></i> Holidays</a>
        <a href="send_email.php" class="sidebar-link"><i class="fa fa-envelope"></i> Send Email</a>
        <?php if (in_array($admin_role, ['Super Admin', 'Admin'], true)): ?><a href="security_audit.php" class="sidebar-link"><i class="fa fa-shield-halved"></i> Security Audit</a><?php endif; ?>
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
            <h4>Salary Slips <span>/ Generate</span></h4>
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

        <?php if (isset($message)) { echo $message; } ?>

        <div class="card shadow mb-4">
            <div class="card-header bg-success text-white">
                <h4><i class="fa fa-file-invoice-dollar"></i> Generate Salary Slip</h4>
            </div>

            <div class="card-body">
<form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(ems_csrf_token()) ?>">
                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label class="form-label">Employee</label>
                            <select name="employee_id" class="form-select" required>
                                <option value="">Select Employee</option>
                                <?php while($emp = mysqli_fetch_assoc($employees)){ ?>
                                    <option value="<?php echo $emp['id']; ?>">
                                        <?php echo $emp['employee_id']; ?> - <?php echo $emp['full_name']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Salary Month</label>
                            <input type="month" name="salary_month" class="form-control" required>
                        </div>

                        <div class="col-md-3 d-grid">
                            <label>&nbsp;</label>
                            <button type="submit" name="save_slip" class="btn btn-success">
                                <i class="fa fa-save"></i> Generate Slip
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5><i class="fa fa-list"></i> Salary Slip List</h5>
            </div>

            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input
                            type="text"
                            id="searchEmployee"
                            class="form-control"
                            placeholder="Search Employee...">
                    </div>
                </div>

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
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($salarySlips)){ ?>
                                <tr>
                                    <td><?php echo $row['emp_code']; ?></td>
                                    <td><?php echo $row['full_name']; ?></td>
                                    <td><?php echo date('F Y', strtotime($row['salary_month'] . '-01')); ?></td>
                                    <td><?php echo number_format($row['basic_salary'], 2); ?></td>
                                    <td><?php echo number_format($row['allowance'], 2); ?></td>
                                    <td><?php echo number_format($row['deduction'], 2); ?></td>
                                    <td class="text-success fw-bold"><?php echo number_format($row['gross_salary'], 2); ?></td>
                                    <td class="text-primary fw-bold"><?php echo number_format($row['net_salary'], 2); ?></td>
                                    <td>
                                        <a href="print_salary_slip.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">
                                            <i class="fa fa-print"></i>
                                        </a>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this salary slip?');"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars(ems_csrf_token())?>"><button name="delete" value="<?=(int)$row['id']?>" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></button></form>
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
document.getElementById("searchEmployee").addEventListener("keyup", function(){
    var value = this.value.toLowerCase();
    document.querySelectorAll("tbody tr").forEach(function(row){
        row.style.display = row.innerText.toLowerCase().includes(value) ? "" : "none";
    });
});
</script>

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
    const sectionName = title.childNodes[0].textContent.trim();
    const icon = document.createElement('span');
    icon.className = 'section-collapse-icon';
    icon.textContent = '\u25BC';
    icon.setAttribute('data-section', sectionName);
    title.appendChild(icon);

    title.addEventListener('click', function(e) {
        if (e.target.tagName === 'A') return;
        const group = this.nextElementSibling;
        if (!group || !group.classList.contains('sidebar-section-group')) return;

        const isCollapsed = group.classList.toggle('collapsed');
        const ico = this.querySelector('.section-collapse-icon');
        if (ico) ico.classList.toggle('collapsed', isCollapsed);
    });

    const saved = null;
    const group = title.nextElementSibling;
    if (saved === 'collapsed' && group && group.classList.contains('sidebar-section-group')) {
        group.classList.add('collapsed');
        const ico = title.querySelector('.section-collapse-icon');
        if (ico) ico.classList.add('collapsed');
    }
});
</script>

<?php include __DIR__ . '/../config/back_dashboard.php'; ?>
</body>
</html>
