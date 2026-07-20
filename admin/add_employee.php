<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

include("../config/db.php");
include("admincheck_role.php");
include_once("roles_helper.php");

$departments = mysqli_query($conn, "SELECT * FROM departments ORDER BY department_name ASC");

$employeeCode = '';
$lastEmployee = mysqli_fetch_assoc(mysqli_query($conn, "SELECT employee_id FROM employees ORDER BY id DESC LIMIT 1"));
if ($lastEmployee && !empty($lastEmployee['employee_id'])) {
    $lastNumber = (int) preg_replace('/\D/', '', $lastEmployee['employee_id']);
    $employeeCode = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
} else {
    $employeeCode = '00001';
}

// Get manageable roles for dropdown
$manageableRoles = getManageableRoles($conn);

if(isset($_POST['save']))
{
    $employee_id = !empty($_POST['employee_id']) ? mysqli_real_escape_string($conn, $_POST['employee_id']) : $employeeCode;
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password_raw = $_POST['password'];
    $password = password_hash($password_raw, PASSWORD_DEFAULT);
    $role = isset($_POST['role']) ? mysqli_real_escape_string($conn, $_POST['role']) : 'Employee';
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $designation = mysqli_real_escape_string($conn, $_POST['designation']);
    $joining_date = mysqli_real_escape_string($conn, $_POST['joining_date']);
    $shift_name = !empty($_POST['shift_name']) ? mysqli_real_escape_string($conn, $_POST['shift_name']) : 'Morning';
    $shift_start_time = !empty($_POST['shift_start_time']) ? mysqli_real_escape_string($conn, $_POST['shift_start_time']) : '09:00';
    $shift_end_time = !empty($_POST['shift_end_time']) ? mysqli_real_escape_string($conn, $_POST['shift_end_time']) : '17:00';
    $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : 'Active';
    $reporting_manager_id = isset($_POST['reporting_manager_id']) ? intval($_POST['reporting_manager_id']) : 0;
    $reporting_supervisor_id = isset($_POST['reporting_supervisor_id']) ? intval($_POST['reporting_supervisor_id']) : 0;
    $reporting_team_lead_id = isset($_POST['reporting_team_lead_id']) ? intval($_POST['reporting_team_lead_id']) : 0;

$photo = "";

if($_FILES['photo']['name']!="")
{
    $photo = time()."_".$_FILES['photo']['name'];

    move_uploaded_file(
        $_FILES['photo']['tmp_name'],
        "../uploads/".$photo
    );
}

    // Ensure columns exist
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS status ENUM('Active','Inactive','Suspended','Terminated') DEFAULT 'Active'");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS reporting_manager_id INT DEFAULT NULL");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS reporting_supervisor_id INT DEFAULT NULL");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS reporting_team_lead_id INT DEFAULT NULL");

    // Check duplicate email
    $exists = mysqli_query($conn, "SELECT id FROM employees WHERE email='$email' LIMIT 1");
    if ($exists && mysqli_num_rows($exists) > 0) {
        $error = "An employee with this email already exists.";
    } else {
        // Ensure unique employee_id
        $eid_check = mysqli_query($conn, "SELECT id FROM employees WHERE employee_id='$employee_id' LIMIT 1");
        if ($eid_check && mysqli_num_rows($eid_check) > 0) {
            $lastEmployee = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM employees ORDER BY id DESC LIMIT 1"));
            $next = $lastEmployee ? $lastEmployee['id'] + 1 : 1;
            $employee_id = str_pad($next,5,'0',STR_PAD_LEFT);
        }

        // Set is_active based on status
        $is_active = ($status == 'Active') ? 1 : 0;

        $sql = "INSERT INTO employees
(employee_id, full_name, email, password, role, photo, department, designation, joining_date, shift_name, shift_start_time, shift_end_time, annual_leave, sick_leave, casual_leave, is_active, status, reporting_manager_id, reporting_supervisor_id, reporting_team_lead_id)
VALUES
('$employee_id','$full_name','$email','$password','$role','$photo','$department','$designation','$joining_date','$shift_name','$shift_start_time','$shift_end_time',7,10,10,$is_active,'$status','$reporting_manager_id','$reporting_supervisor_id','$reporting_team_lead_id')";

        if(mysqli_query($conn,$sql))
        {
            $success = "Employee Added Successfully";
        }
        else
        {
            $error = "Database Error: " . mysqli_error($conn);
        }
    }
}

// Get ALL active employees for reporting dropdowns
$allEmployees = mysqli_query($conn, "SELECT id, employee_id, full_name, role FROM employees WHERE status='Active' ORDER BY full_name ASC");
$managers = $allEmployees;
$supervisors = $allEmployees;
$teamLeads = $allEmployees;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Employee - EMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="admin_panel.css" rel="stylesheet">
<style>
/* Page-specific styles only */
.form-section-title { font-size: 14px; font-weight: 700; color: var(--primary); margin-bottom: 16px; padding-bottom: 8px; border-bottom: 2px solid var(--gray-100); display: flex; align-items: center; gap: 8px; }
.form-label { font-size: 13px; font-weight: 600; color: var(--gray-700); margin-bottom: 4px; }
.form-control, .form-select { border-radius: var(--radius-sm); border: 1px solid var(--gray-200); font-size: 14px; padding: 10px 14px; }
.form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
.dark-mode .form-label { color: var(--gray-300); }
.dark-mode .form-control, .dark-mode .form-select { background: rgba(255,255,255,.06); border-color: rgba(255,255,255,.1); color: #e2e8f0; }
.dark-mode .form-control:focus, .dark-mode .form-select:focus { background: rgba(255,255,255,.08); color: #e2e8f0; }
.dark-mode .form-section-title { border-color: rgba(255,255,255,.08); }
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
        <a href="add_employee.php" class="sidebar-link active"><i class="fa fa-user-plus"></i> Add Employee</a>
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
            <h4>Add Employee <span>/ New Employee</span></h4>
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
                <h5><i class="fa fa-user-plus"></i> Add New Employee</h5>
                <div>
                    <a href="employee.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3"><i class="fa fa-arrow-left"></i> Back to Employees</a>
                </div>
            </div>
            <div class="card-body-custom">

                <?php if(isset($error)){ ?>
                    <div class="alert alert-danger alert-dismissible fade show rounded-pill px-4"><?php echo htmlspecialchars($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php } elseif(isset($success)) { ?>
                    <div class="alert alert-success alert-dismissible fade show rounded-pill px-4"><?php echo htmlspecialchars($success); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php } ?>

                <form method="POST" enctype="multipart/form-data">

                    <div class="form-section-title"><i class="fa fa-info-circle"></i> Basic Information</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Employee Code</label>
                            <input type="text" name="employee_id" class="form-control" value="<?php echo htmlspecialchars($employeeCode); ?>" readonly required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Department <span class="text-danger">*</span></label>
                            <select name="department" class="form-select" required>
                                <option value="">Select Department</option>
                                <?php while($dept = mysqli_fetch_assoc($departments)) { ?>
                                <option value="<?php echo $dept['department_name']; ?>"><?php echo $dept['department_name']; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Designation</label>
                            <input type="text" name="designation" class="form-control" placeholder="e.g. Senior Developer">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                <option value="">Select Role</option>
                                <?php
                                $allRoles = ['Operations Manager','Supervisor','Team Lead','WFM Executive','Finance Manager','Accountant','Employee'];
                                foreach ($allRoles as $r) {
                                    if (empty($manageableRoles) || in_array($r, $manageableRoles)) {
                                        echo "<option value=\"$r\">$r</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Joining Date</label>
                            <input type="date" name="joining_date" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                                <option value="Suspended">Suspended</option>
                                <option value="Terminated">Terminated</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-section-title mt-4"><i class="fa fa-clock"></i> Shift Information</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Shift</label>
                            <select name="shift_name" class="form-select" required>
                                <option value="Morning">Morning</option>
                                <option value="Evening">Evening</option>
                                <option value="Night">Night</option>
                                <option value="Flexible">Flexible</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Shift Start</label>
                            <input type="time" name="shift_start_time" class="form-control" value="09:00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Shift End</label>
                            <input type="time" name="shift_end_time" class="form-control" value="17:00" required>
                        </div>
                    </div>

                    <div class="form-section-title mt-4"><i class="fa fa-sitemap"></i> Reporting Structure</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Reporting Manager</label>
                            <select name="reporting_manager_id" class="form-select">
                                <option value="">Select Manager</option>
                                <?php while($m = mysqli_fetch_assoc($managers)) { ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo $m['employee_id']; ?> - <?php echo $m['full_name']; ?> (<?php echo $m['role']; ?>)</option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Reporting Supervisor</label>
                            <select name="reporting_supervisor_id" class="form-select">
                                <option value="">Select Supervisor</option>
                                <?php while($s = mysqli_fetch_assoc($supervisors)) { ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo $s['employee_id']; ?> - <?php echo $s['full_name']; ?> (<?php echo $s['role']; ?>)</option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Reporting Team Lead</label>
                            <select name="reporting_team_lead_id" class="form-select">
                                <option value="">Select Team Lead</option>
                                <?php while($t = mysqli_fetch_assoc($teamLeads)) { ?>
                                <option value="<?php echo $t['id']; ?>"><?php echo $t['employee_id']; ?> - <?php echo $t['full_name']; ?> (<?php echo $t['role']; ?>)</option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-section-title mt-4"><i class="fa fa-image"></i> Photo</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Employee Photo</label>
                            <input type="file" name="photo" class="form-control" accept="image/*">
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" name="save" class="btn btn-success rounded-pill px-4">
                            <i class="fa fa-save"></i> Save Employee
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
    const sectionName = title.childNodes[0].textContent.trim();
    const icon = document.createElement('span');
    icon.className = 'section-collapse-icon';
    icon.textContent = '\u25BC';
    title.appendChild(icon);

    title.addEventListener('click', function(e) {
        if (e.target.tagName === 'A') return;
        const group = this.nextElementSibling;
        if (!group || !group.classList.contains('sidebar-section-group')) return;
        const isCollapsed = group.classList.toggle('collapsed');
        const ico = this.querySelector('.section-collapse-icon');
        if (ico) ico.classList.toggle('collapsed', isCollapsed);
        localStorage.setItem('sidebar_' + sectionName, isCollapsed ? 'collapsed' : 'expanded');
    });

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