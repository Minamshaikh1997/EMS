<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

include("../config/db.php");
include("admincheck_role.php");
include_once("roles_helper.php");

// Load Departments
$departments = mysqli_query($conn, "SELECT * FROM departments ORDER BY department_name");

// Get Employee ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Load Employee Data
$result = mysqli_query($conn, "SELECT * FROM employees WHERE id='$id'");
$row = mysqli_fetch_assoc($result);

if (!$row) {
    header("Location: employee.php");
    exit();
}

// Get manageable roles
$manageableRoles = getManageableRoles($conn);

// Update Employee
if(isset($_POST['update']))
{
    $employee_id   = $_POST['employee_id'];
    $full_name     = $_POST['full_name'];
    $email         = $_POST['email'];
    $role          = isset($_POST['role']) ? $_POST['role'] : 'Employee';
    $department    = $_POST['department'];
    $designation   = $_POST['designation'];
    $joining_date  = $_POST['joining_date'];
    $shift_name    = !empty($_POST['shift_name']) ? $_POST['shift_name'] : 'Morning';
    $shift_start_time = !empty($_POST['shift_start_time']) ? $_POST['shift_start_time'] : '09:00';
    $shift_end_time   = !empty($_POST['shift_end_time']) ? $_POST['shift_end_time'] : '17:00';
    $annual_leave  = $_POST['annual_leave'];
    $sick_leave    = $_POST['sick_leave'];
    $casual_leave  = $_POST['casual_leave'];
    $status = isset($_POST['status']) ? $_POST['status'] : 'Active';
    $reporting_manager_id = isset($_POST['reporting_manager_id']) ? intval($_POST['reporting_manager_id']) : 0;
    $reporting_supervisor_id = isset($_POST['reporting_supervisor_id']) ? intval($_POST['reporting_supervisor_id']) : 0;
    $reporting_team_lead_id = isset($_POST['reporting_team_lead_id']) ? intval($_POST['reporting_team_lead_id']) : 0;
    $can_view_payroll = isset($_POST['can_view_payroll']) ? 1 : 0;
    
    // Get employee rights
    $rights = [
        'can_view_payroll' => isset($_POST['rights']['can_view_payroll']) ? 1 : 0,
        'can_apply_leave' => isset($_POST['rights']['can_apply_leave']) ? 1 : 0,
        'can_view_attendance' => isset($_POST['rights']['can_view_attendance']) ? 1 : 0,
        'can_submit_adjustment' => isset($_POST['rights']['can_submit_adjustment']) ? 1 : 0,
        'can_edit_profile' => isset($_POST['rights']['can_edit_profile']) ? 1 : 0,
        'can_view_reports' => isset($_POST['rights']['can_view_reports']) ? 1 : 0,
        'can_change_password' => isset($_POST['rights']['can_change_password']) ? 1 : 0
    ];

    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS shift_name VARCHAR(100) DEFAULT 'Morning'");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS shift_start_time TIME DEFAULT '09:00:00'");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS shift_end_time TIME DEFAULT '17:00:00'");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS status ENUM('Active','Inactive','Suspended','Terminated') DEFAULT 'Active'");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS reporting_manager_id INT DEFAULT NULL");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS reporting_supervisor_id INT DEFAULT NULL");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS reporting_team_lead_id INT DEFAULT NULL");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS can_view_payroll TINYINT(1) DEFAULT 1");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS can_apply_leave TINYINT(1) DEFAULT 1");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS can_view_attendance TINYINT(1) DEFAULT 1");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS can_submit_adjustment TINYINT(1) DEFAULT 1");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS can_edit_profile TINYINT(1) DEFAULT 1");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS can_view_reports TINYINT(1) DEFAULT 1");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS can_change_password TINYINT(1) DEFAULT 1");

    $is_active = ($status == 'Active') ? 1 : 0;

    mysqli_query($conn,"UPDATE employees SET
        employee_id='$employee_id',
        full_name='$full_name',
        email='$email',
        role='$role',
        department='$department',
        designation='$designation',
        joining_date='$joining_date',
        shift_name='$shift_name',
        shift_start_time='$shift_start_time',
        shift_end_time='$shift_end_time',
        annual_leave='$annual_leave',
        sick_leave='$sick_leave',
        casual_leave='$casual_leave',
        status='$status',
        is_active='$is_active',
        reporting_manager_id='$reporting_manager_id',
        reporting_supervisor_id='$reporting_supervisor_id',
        reporting_team_lead_id='$reporting_team_lead_id',
        can_view_payroll='$can_view_payroll',
        can_apply_leave='{$rights['can_apply_leave']}',
        can_view_attendance='{$rights['can_view_attendance']}',
        can_submit_adjustment='{$rights['can_submit_adjustment']}',
        can_edit_profile='{$rights['can_edit_profile']}',
        can_view_reports='{$rights['can_view_reports']}',
        can_change_password='{$rights['can_change_password']}'
        WHERE id='$id'
    ");

    echo "<script>
    alert('Employee Updated Successfully');
    window.location='employee.php';
    </script>";

    exit();
}

// Get ALL employees for reporting dropdowns - fetch into array to reuse
$allEmployeesResult = mysqli_query($conn, "SELECT id, employee_id, full_name, role FROM employees ORDER BY full_name ASC");
$allEmployees = [];
while ($emp = mysqli_fetch_assoc($allEmployeesResult)) {
    $allEmployees[] = $emp;
}
$managers = $allEmployees;
$supervisors = $allEmployees;
$teamLeads = $allEmployees;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Employee - EMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="admin_panel.css" rel="stylesheet">
<style>
:root {
    --primary: #2563eb;
    --primary-dark: #1d4ed8;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --info: #06b6d4;
    --gray-50: #f8fafc;
    --gray-100: #f1f5f9;
    --gray-200: #e2e8f0;
    --gray-300: #cbd5e1;
    --gray-400: #94a3b8;
    --gray-500: #64748b;
    --gray-600: #475569;
    --gray-700: #334155;
    --gray-800: #1e293b;
    --gray-900: #0f172a;
    --radius: 16px;
    --radius-sm: 10px;
    --shadow: 0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.06);
    --shadow-md: 0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -1px rgba(0,0,0,.06);
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--gray-50);
    color: var(--gray-800);
    overflow-x: hidden;
}

.card-modern {
    background: white;
    border-radius: var(--radius);
    border: 1px solid var(--gray-200);
    box-shadow: var(--shadow);
    overflow: hidden;
}
.card-modern .card-header-custom {
    padding: 16px 24px;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--gray-50);
    flex-wrap: wrap;
    gap: 12px;
}
.card-modern .card-header-custom h5 {
    font-size: 16px; font-weight: 700;
    color: var(--gray-800); margin: 0;
    display: flex; align-items: center; gap: 8px;
}
.card-modern .card-header-custom h5 i { color: var(--primary); }
.card-modern .card-body-custom { padding: 24px; }

.form-section-title {
    font-size: 14px; font-weight: 700;
    color: var(--primary);
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 2px solid var(--gray-100);
    display: flex; align-items: center; gap: 8px;
}

.form-label { font-size: 13px; font-weight: 600; color: var(--gray-700); margin-bottom: 4px; }
.form-control, .form-select { border-radius: var(--radius-sm); border: 1px solid var(--gray-200); font-size: 14px; padding: 10px 14px; }
.form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37,99,235,.1); }

/* ===== DARK MODE ===== */
body.dark-mode { background: #0f172a; color: #e2e8f0; }
.dark-mode .card-modern { background: #1e293b; border-color: rgba(255,255,255,.08); }
.dark-mode .card-modern .card-header-custom { background: rgba(255,255,255,.04); border-color: rgba(255,255,255,.08); }
.dark-mode .card-modern .card-header-custom h5 { color: #e2e8f0; }
.dark-mode .form-label { color: var(--gray-300); }
.dark-mode .form-control, .dark-mode .form-select { background: rgba(255,255,255,.06); border-color: rgba(255,255,255,.1); color: #e2e8f0; }
.dark-mode .form-control:focus, .dark-mode .form-select:focus { background: rgba(255,255,255,.08); color: #e2e8f0; }
.dark-mode .form-section-title { border-color: rgba(255,255,255,.08); }

@media (max-width: 768px) {
    .page-content { padding: 16px; }
}
</style>
</head>
<body>

<!-- Sidebar Backdrop -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
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
        <a href="salary_components.php" class="sidebar-link"><i class="fa-solid fa-wallet"></i> Salary Components</a>
        <a href="salary_slips.php" class="sidebar-link"><i class="fa-solid fa-file-pdf"></i> Salary Slips</a>
        <a href="payroll_reports.php" class="sidebar-link"><i class="fa-solid fa-chart-line"></i> Payroll Reports</a>
        <a href="salary_structure.php" class="sidebar-link"><i class="fa fa-money-bill-wave"></i> Salary Structure</a>
        <a href="monthly_payroll.php" class="sidebar-link"><i class="fa fa-calendar"></i> Monthly Payroll</a>
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

<!-- Main Content -->
<div class="main-content" id="mainContent">

    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                <i class="fa fa-bars"></i>
            </button>
            <h4>Edit Employee <span>/ <?php echo htmlspecialchars($row['full_name']); ?></span></h4>
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

        <div class="card-modern">
            <div class="card-header-custom">
                <h5><i class="fa fa-edit"></i> Edit Employee: <?php echo htmlspecialchars($row['full_name']); ?></h5>
                <div>
                    <a href="employee.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3"><i class="fa fa-arrow-left"></i> Back to Employees</a>
                </div>
            </div>
            <div class="card-body-custom">

                <form method="POST">

                    <div class="form-section-title"><i class="fa fa-info-circle"></i> Basic Information</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Employee ID</label>
                            <input type="text" name="employee_id" class="form-control" value="<?php echo htmlspecialchars($row['employee_id']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($row['full_name']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($row['email']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select" required>
                                <?php
                                $allRoles = ['Super Admin','Admin','Operations Manager','Supervisor','Team Lead','WFM Executive','Finance Manager','Accountant','Employee'];
                                $currentRole = $row['role'] ?? 'Employee';
                                if (!in_array($currentRole, $allRoles)) {
                                    $allRoles[] = $currentRole;
                                }
                                foreach ($allRoles as $r) {
                                    $sel = ($currentRole == $r) ? 'selected' : '';
                                    if (empty($manageableRoles) || in_array($r, $manageableRoles) || $currentRole == $r) {
                                        echo "<option value=\"$r\" $sel>$r</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Department <span class="text-danger">*</span></label>
                            <select name="department" class="form-select" required>
                                <?php while($dept = mysqli_fetch_assoc($departments)){ ?>
                                <option value="<?php echo $dept['department_name']; ?>" <?php if($row['department']==$dept['department_name']) echo "selected"; ?>>
                                    <?php echo $dept['department_name']; ?>
                                </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="Active" <?php echo ($row['status'] ?? 'Active') == 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo ($row['status'] ?? '') == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="Suspended" <?php echo ($row['status'] ?? '') == 'Suspended' ? 'selected' : ''; ?>>Suspended</option>
                                <option value="Terminated" <?php echo ($row['status'] ?? '') == 'Terminated' ? 'selected' : ''; ?>>Terminated</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Designation</label>
                            <input type="text" name="designation" class="form-control" value="<?php echo htmlspecialchars($row['designation']); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Joining Date</label>
                            <input type="date" name="joining_date" class="form-control" value="<?php echo $row['joining_date']; ?>">
                        </div>
                    </div>

                    <div class="form-section-title mt-4"><i class="fa fa-clock"></i> Shift Information</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Shift</label>
                            <select name="shift_name" class="form-select" required>
                                <option value="Morning" <?php if(($row['shift_name'] ?? '')=='Morning') echo 'selected'; ?>>Morning</option>
                                <option value="Evening" <?php if(($row['shift_name'] ?? '')=='Evening') echo 'selected'; ?>>Evening</option>
                                <option value="Night" <?php if(($row['shift_name'] ?? '')=='Night') echo 'selected'; ?>>Night</option>
                                <option value="Flexible" <?php if(($row['shift_name'] ?? '')=='Flexible') echo 'selected'; ?>>Flexible</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Shift Start</label>
                            <input type="time" name="shift_start_time" class="form-control" value="<?php echo isset($row['shift_start_time']) ? $row['shift_start_time'] : '09:00'; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Shift End</label>
                            <input type="time" name="shift_end_time" class="form-control" value="<?php echo isset($row['shift_end_time']) ? $row['shift_end_time'] : '17:00'; ?>" required>
                        </div>
                    </div>

                    <div class="form-section-title mt-4"><i class="fa fa-umbrella-beach"></i> Leave Allocation</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Annual Leave</label>
                            <input type="number" name="annual_leave" class="form-control" value="<?php echo $row['annual_leave']; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sick Leave</label>
                            <input type="number" name="sick_leave" class="form-control" value="<?php echo $row['sick_leave']; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Casual Leave</label>
                            <input type="number" name="casual_leave" class="form-control" value="<?php echo $row['casual_leave']; ?>">
                        </div>
                    </div>

                    <div class="form-section-title mt-4"><i class="fa fa-sitemap"></i> Reporting Structure</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Reporting Manager</label>
                            <select name="reporting_manager_id" class="form-select">
                                <option value="">Select Manager</option>
                                <?php foreach ($managers as $m) {
                                    $sel = ($m['id'] == ($row['reporting_manager_id'] ?? 0)) ? 'selected' : '';
                                ?>
                                <option value="<?php echo $m['id']; ?>" <?php echo $sel; ?>><?php echo $m['employee_id']; ?> - <?php echo $m['full_name']; ?> (<?php echo $m['role']; ?>)</option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Reporting Supervisor</label>
                            <select name="reporting_supervisor_id" class="form-select">
                                <option value="">Select Supervisor</option>
                                <?php foreach ($supervisors as $s) {
                                    $sel = ($s['id'] == ($row['reporting_supervisor_id'] ?? 0)) ? 'selected' : '';
                                ?>
                                <option value="<?php echo $s['id']; ?>" <?php echo $sel; ?>><?php echo $s['employee_id']; ?> - <?php echo $s['full_name']; ?> (<?php echo $s['role']; ?>)</option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Reporting Team Lead</label>
                            <select name="reporting_team_lead_id" class="form-select">
                                <option value="">Select Team Lead</option>
                                <?php foreach ($teamLeads as $t) {
                                    $sel = ($t['id'] == ($row['reporting_team_lead_id'] ?? 0)) ? 'selected' : '';
                                ?>
                                <option value="<?php echo $t['id']; ?>" <?php echo $sel; ?>><?php echo $t['employee_id']; ?> - <?php echo $t['full_name']; ?> (<?php echo $t['role']; ?>)</option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-section-title mt-4"><i class="fa fa-user-shield"></i> Employee Rights & Permissions</div>
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle me-2"></i>
                        <strong>Control Feature Access:</strong> Enable or disable specific features for this employee. 
                        Disabled features will be hidden from the employee's dashboard and access will be restricted.
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="rights[can_view_payroll]" id="right_can_view_payroll" value="1" <?php echo ($row['can_view_payroll'] ?? 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="right_can_view_payroll">
                                    <strong>View Payroll/Salary</strong>
                                    <small class="d-block text-muted">Can view salary slips and payroll information</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="rights[can_apply_leave]" id="right_can_apply_leave" value="1" <?php echo ($row['can_apply_leave'] ?? 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="right_can_apply_leave">
                                    <strong>Apply for Leave</strong>
                                    <small class="d-block text-muted">Can submit new leave applications</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="rights[can_view_attendance]" id="right_can_view_attendance" value="1" <?php echo ($row['can_view_attendance'] ?? 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="right_can_view_attendance">
                                    <strong>View Attendance</strong>
                                    <small class="d-block text-muted">Can view attendance history and mark attendance</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="rights[can_submit_adjustment]" id="right_can_submit_adjustment" value="1" <?php echo ($row['can_submit_adjustment'] ?? 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="right_can_submit_adjustment">
                                    <strong>Submit Adjustments</strong>
                                    <small class="d-block text-muted">Can request attendance adjustments</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="rights[can_edit_profile]" id="right_can_edit_profile" value="1" <?php echo ($row['can_edit_profile'] ?? 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="right_can_edit_profile">
                                    <strong>Edit Profile</strong>
                                    <small class="d-block text-muted">Can update their profile information</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="rights[can_view_reports]" id="right_can_view_reports" value="1" <?php echo ($row['can_view_reports'] ?? 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="right_can_view_reports">
                                    <strong>View Reports</strong>
                                    <small class="d-block text-muted">Can access reports and analytics</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="rights[can_change_password]" id="right_can_change_password" value="1" <?php echo ($row['can_change_password'] ?? 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="right_can_change_password">
                                    <strong>Change Password</strong>
                                    <small class="d-block text-muted">Can change their account password</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" name="update" class="btn btn-primary rounded-pill px-4">
                            <i class="fa fa-save"></i> Update Employee
                        </button>
                        <a href="employee.php" class="btn btn-outline-secondary rounded-pill px-4">
                            <i class="fa fa-arrow-left"></i> Cancel
                        </a>
                    </div>

                </form>

            </div>
        </div>

    </div>
</div>

<script>
// Sidebar Toggle
const sidebar = document.getElementById('sidebar');
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