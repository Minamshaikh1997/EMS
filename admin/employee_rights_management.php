<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

include("../config/db.php");
include("admincheck_role.php");

$message = '';
$error = '';

// Handle bulk update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_update_rights'])) {
    $employee_id = intval($_POST['employee_id']);
    $rights = [
        'can_view_payroll' => isset($_POST['rights']['can_view_payroll']) ? 1 : 0,
        'can_apply_leave' => isset($_POST['rights']['can_apply_leave']) ? 1 : 0,
        'can_view_attendance' => isset($_POST['rights']['can_view_attendance']) ? 1 : 0,
        'can_submit_adjustment' => isset($_POST['rights']['can_submit_adjustment']) ? 1 : 0,
        'can_edit_profile' => isset($_POST['rights']['can_edit_profile']) ? 1 : 0,
        'can_view_reports' => isset($_POST['rights']['can_view_reports']) ? 1 : 0,
        'can_change_password' => isset($_POST['rights']['can_change_password']) ? 1 : 0
    ];

    // Ensure columns exist
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS can_view_payroll TINYINT(1) DEFAULT 1");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS can_apply_leave TINYINT(1) DEFAULT 1");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS can_view_attendance TINYINT(1) DEFAULT 1");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS can_submit_adjustment TINYINT(1) DEFAULT 1");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS can_edit_profile TINYINT(1) DEFAULT 1");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS can_view_reports TINYINT(1) DEFAULT 1");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS can_change_password TINYINT(1) DEFAULT 1");

    $sql = "UPDATE employees SET 
            can_view_payroll = '{$rights['can_view_payroll']}',
            can_apply_leave = '{$rights['can_apply_leave']}',
            can_view_attendance = '{$rights['can_view_attendance']}',
            can_submit_adjustment = '{$rights['can_submit_adjustment']}',
            can_edit_profile = '{$rights['can_edit_profile']}',
            can_view_reports = '{$rights['can_view_reports']}',
            can_change_password = '{$rights['can_change_password']}'
            WHERE id = '$employee_id'";

    if (mysqli_query($conn, $sql)) {
        $message = "✅ Rights updated successfully for employee ID: $employee_id";
    } else {
        $error = "❌ Error updating rights: " . mysqli_error($conn);
    }
}

// Get all employees with their rights
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$department = isset($_GET['department']) ? mysqli_real_escape_string($conn, $_GET['department']) : '';

$sql = "SELECT * FROM employees WHERE 1=1";

if ($search != '') {
    $sql .= " AND (employee_id LIKE '%$search%' OR full_name LIKE '%$search%' OR email LIKE '%$search%')";
}

if ($department != '') {
    $sql .= " AND department = '$department'";
}

$sql .= " ORDER BY id DESC";

$employees_result = mysqli_query($conn, $sql);

// Get departments for filter
$departments = mysqli_query($conn, "SELECT * FROM departments ORDER BY department_name");

// Get all rights for each employee
$employees_with_rights = [];
if ($employees_result) {
    while ($emp = mysqli_fetch_assoc($employees_result)) {
        $emp['rights'] = [
            'can_view_payroll' => $emp['can_view_payroll'] ?? 1,
            'can_apply_leave' => $emp['can_apply_leave'] ?? 1,
            'can_view_attendance' => $emp['can_view_attendance'] ?? 1,
            'can_submit_adjustment' => $emp['can_submit_adjustment'] ?? 1,
            'can_edit_profile' => $emp['can_edit_profile'] ?? 1,
            'can_view_reports' => $emp['can_view_reports'] ?? 1,
            'can_change_password' => $emp['can_change_password'] ?? 1
        ];
        $employees_with_rights[] = $emp;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Employee Rights Management - EMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="admin_panel.css" rel="stylesheet">
<style>
:root {
    --primary: #2563eb;
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
    background: var(--gray-50);
    display: flex;
    align-items: center;
    justify-content: space-between;
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

/* Employee Card */
.employee-card {
    background: white;
    border-radius: var(--radius);
    border: 1px solid var(--gray-200);
    box-shadow: var(--shadow);
    padding: 20px;
    margin-bottom: 16px;
    transition: all .3s ease;
}

.employee-card:hover {
    box-shadow: var(--shadow-md);
    border-color: var(--primary);
}

.employee-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 2px solid var(--gray-100);
}

.employee-avatar {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    background: linear-gradient(135deg, var(--primary), #7c3aed);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 22px;
    font-weight: 700;
    flex-shrink: 0;
}

.employee-info {
    flex: 1;
}

.employee-name {
    font-size: 16px;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 4px;
}

.employee-meta {
    font-size: 12px;
    color: var(--gray-500);
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.employee-meta span {
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Rights Grid */
.rights-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 12px;
}

.right-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    background: var(--gray-50);
    border-radius: 8px;
    border-left: 3px solid var(--gray-300);
    transition: all .2s ease;
}

.right-item.enabled {
    border-left-color: var(--success);
    background: rgba(16,185,129,.05);
}

.right-item.disabled {
    border-left-color: var(--danger);
    background: rgba(239,68,68,.05);
}

.right-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--gray-700);
    display: flex;
    align-items: center;
    gap: 6px;
}

.right-label i {
    font-size: 14px;
    color: var(--gray-500);
}

/* Toggle Switch */
.form-check-input:checked {
    background-color: var(--success);
    border-color: var(--success);
}

.form-check-input:focus {
    box-shadow: 0 0 0 3px rgba(16,185,129,.1);
    border-color: var(--success);
}

/* Stats */
.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.stat-box {
    background: white;
    border-radius: var(--radius);
    border: 1px solid var(--gray-200);
    box-shadow: var(--shadow);
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
}

.stat-box .stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.stat-box .stat-icon.primary { background: rgba(37,99,235,.12); color: var(--primary); }
.stat-box .stat-icon.success { background: rgba(16,185,129,.12); color: var(--success); }
.stat-box .stat-icon.warning { background: rgba(245,158,11,.12); color: var(--warning); }
.stat-box .stat-icon.danger { background: rgba(239,68,68,.12); color: var(--danger); }

.stat-box .stat-content {
    flex: 1;
}

.stat-box .stat-value {
    font-size: 24px;
    font-weight: 800;
    color: var(--gray-900);
    line-height: 1;
    margin-bottom: 4px;
}

.stat-box .stat-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: .5px;
}

/* Modal */
.modal-content {
    border-radius: var(--radius);
    border: none;
    box-shadow: var(--shadow-md);
}

.modal-header {
    background: var(--gray-50);
    border-bottom: 2px solid var(--gray-200);
    padding: 16px 24px;
}

.modal-header .modal-title {
    font-size: 18px;
    font-weight: 700;
    color: var(--gray-900);
    display: flex;
    align-items: center;
    gap: 8px;
}

.modal-header .modal-title i { color: var(--primary); }

.modal-body {
    padding: 24px;
}

.modal-footer {
    border-top: 2px solid var(--gray-200);
    padding: 16px 24px;
}

/* Responsive */
@media (max-width: 768px) {
    .rights-grid {
        grid-template-columns: 1fr;
    }
    
    .employee-header {
        flex-wrap: wrap;
    }
    
    .stats-row {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 480px) {
    .stats-row {
        grid-template-columns: 1fr;
    }
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
        </div>

        <div class="sidebar-section-title">Rights Management</div>
        <div class="sidebar-section-group">
        <a href="employee_rights_management.php" class="sidebar-link active"><i class="fa fa-user-shield"></i> Employee Rights</a>
        </div>

        <div class="sidebar-section-title">Payroll</div>
        <div class="sidebar-section-group">
        <a href="payroll_dashboard.php" class="sidebar-link"><i class="fa-solid fa-money-bill-wave"></i> Payroll Dashboard</a>
        <a href="generate_payroll.php" class="sidebar-link"><i class="fa fa-file-invoice-dollar"></i> Generate Payroll</a>
        <a href="payroll_history.php" class="sidebar-link"><i class="fa fa-clock-rotate-left"></i> Payroll History</a>
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
            <h4>Employee Rights Management <span>/ Control Access</span></h4>
        </div>
        <div class="header-right">
            <span class="header-date"><i class="fa-regular fa-calendar"></i> <?=date('d M Y')?></span>
            <span class="header-admin-badge"><i class="fa fa-user-shield"></i> <span><?php echo htmlspecialchars($admin_name); ?></span></span>
            <?php $darkModeInTopbar = true; include("../dark_mode.php"); ?>
            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="fa fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </header>

    <!-- Page Content -->
    <div class="page-content">

        <?php if (isset($message)): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-3 px-4" role="alert">
                <i class="fa fa-check-circle me-2"></i> <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-3 px-4" role="alert">
                <i class="fa fa-exclamation-circle me-2"></i> <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Info Box -->
        <div class="alert alert-info border-0 rounded-3 px-4 mb-4">
            <i class="fa-solid fa-circle-info me-2"></i>
            <strong>Instructions:</strong> Click on any employee card to manage their feature access rights. 
            You can enable or disable specific features for each employee individually. Disabled features will be hidden from their dashboard.
        </div>

        <!-- Filters -->
        <div class="card-modern mb-4">
            <div class="card-body-custom">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by ID, Name or Email..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="department" class="form-select form-select-sm">
                            <option value="">All Departments</option>
                            <?php while($dept = mysqli_fetch_assoc($departments)){ ?>
                            <option value="<?php echo $dept['department_name']; ?>" <?php if($department==$dept['department_name']) echo "selected"; ?>>
                                <?php echo $dept['department_name']; ?>
                            </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">
                            <i class="fa fa-search"></i> Search
                        </button>
                        <a href="employee_rights_management.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                            <i class="fa fa-refresh"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-row">
            <div class="stat-box">
                <div class="stat-icon primary"><i class="fas fa-users"></i></div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo count($employees_with_rights); ?></div>
                    <div class="stat-label">Total Employees</div>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
                <div class="stat-content">
                    <div class="stat-value">
                        <?php 
                        $enabled_count = 0;
                        foreach ($employees_with_rights as $emp) {
                            if ($emp['rights']['can_view_payroll']) $enabled_count++;
                        }
                        echo $enabled_count;
                        ?>
                    </div>
                    <div class="stat-label">Payroll Access Enabled</div>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-icon warning"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-content">
                    <div class="stat-value">
                        <?php 
                        $disabled_count = count($employees_with_rights) - $enabled_count;
                        echo $disabled_count;
                        ?>
                    </div>
                    <div class="stat-label">Payroll Access Disabled</div>
                </div>
            </div>
        </div>

        <!-- Employees List -->
        <div class="card-modern">
            <div class="card-header-custom">
                <h5><i class="fa fa-users"></i> Employee Rights Overview</h5>
                <small class="text-muted">Click on any employee card to manage their rights</small>
            </div>
            <div class="card-body-custom">
                <?php if (count($employees_with_rights) > 0): ?>
                    <?php foreach ($employees_with_rights as $emp): ?>
                    <div class="employee-card">
                        <div class="employee-header">
                            <div class="employee-avatar">
                                <?php echo strtoupper(substr($emp['full_name'], 0, 1)); ?>
                            </div>
                            <div class="employee-info">
                                <div class="employee-name">
                                    <?php echo htmlspecialchars($emp['full_name']); ?>
                                    <span class="badge bg-<?php echo ($emp['status'] == 'Active') ? 'success' : 'secondary'; ?> ms-2" style="font-size: 10px;">
                                        <?php echo $emp['status']; ?>
                                    </span>
                                </div>
                                <div class="employee-meta">
                                    <span><i class="fa fa-id-badge"></i> <?php echo htmlspecialchars($emp['employee_id']); ?></span>
                                    <span><i class="fa fa-envelope"></i> <?php echo htmlspecialchars($emp['email']); ?></span>
                                    <span><i class="fa fa-building"></i> <?php echo htmlspecialchars($emp['department']); ?></span>
                                    <span><i class="fa fa-user-tag"></i> <?php echo htmlspecialchars($emp['role']); ?></span>
                                </div>
                            </div>
                            <button class="btn btn-primary btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#rightsModal<?php echo $emp['id']; ?>">
                                <i class="fa fa-user-shield"></i> Manage Rights
                            </button>
                        </div>

                        <!-- Quick Rights Overview -->
                        <div class="rights-grid">
                            <?php 
                            $rights_icons = [
                                'can_view_payroll' => 'fa-money-bill-wave',
                                'can_apply_leave' => 'fa-calendar-plus',
                                'can_view_attendance' => 'fa-clock',
                                'can_submit_adjustment' => 'fa-pen-alt',
                                'can_edit_profile' => 'fa-user-edit',
                                'can_view_reports' => 'fa-chart-bar',
                                'can_change_password' => 'fa-key'
                            ];
                            $rights_labels = [
                                'can_view_payroll' => 'Payroll',
                                'can_apply_leave' => 'Leave',
                                'can_view_attendance' => 'Attendance',
                                'can_submit_adjustment' => 'Adjustments',
                                'can_edit_profile' => 'Profile',
                                'can_view_reports' => 'Reports',
                                'can_change_password' => 'Password'
                            ];
                            foreach ($emp['rights'] as $right_key => $right_value): 
                            ?>
                            <div class="right-item <?php echo $right_value ? 'enabled' : 'disabled'; ?>">
                                <div class="right-label">
                                    <i class="fa-solid <?php echo $rights_icons[$right_key]; ?>"></i>
                                    <?php echo $rights_labels[$right_key]; ?>
                                </div>
                                <span class="badge bg-<?php echo $right_value ? 'success' : 'danger'; ?>">
                                    <?php echo $right_value ? 'ON' : 'OFF'; ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Rights Management Modal -->
                        <div class="modal fade" id="rightsModal<?php echo $emp['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <div class="modal-title">
                                            <i class="fa fa-user-shield"></i>
                                            Manage Rights: <?php echo htmlspecialchars($emp['full_name']); ?>
                                        </div>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="employee_id" value="<?php echo $emp['id']; ?>">
                                            <input type="hidden" name="bulk_update_rights" value="1">
                                            
                                            <div class="alert alert-info border-0">
                                                <i class="fa fa-info-circle me-2"></i>
                                                <strong>Employee:</strong> <?php echo htmlspecialchars($emp['full_name']); ?> (<?php echo htmlspecialchars($emp['employee_id']); ?>)
                                                <br><strong>Department:</strong> <?php echo htmlspecialchars($emp['department']); ?> | 
                                                <strong>Role:</strong> <?php echo htmlspecialchars($emp['role']); ?>
                                            </div>

                                            <h6 class="mt-3 mb-3"><i class="fa fa-toggle-on me-2"></i>Feature Access Rights</h6>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="rights[can_view_payroll]" id="payroll_<?php echo $emp['id']; ?>" value="1" <?php echo $emp['rights']['can_view_payroll'] ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="payroll_<?php echo $emp['id']; ?>">
                                                            <strong>View Payroll/Salary</strong>
                                                            <small class="d-block text-muted">Can view salary slips and payroll information</small>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="rights[can_apply_leave]" id="leave_<?php echo $emp['id']; ?>" value="1" <?php echo $emp['rights']['can_apply_leave'] ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="leave_<?php echo $emp['id']; ?>">
                                                            <strong>Apply for Leave</strong>
                                                            <small class="d-block text-muted">Can submit new leave applications</small>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="rights[can_view_attendance]" id="attendance_<?php echo $emp['id']; ?>" value="1" <?php echo $emp['rights']['can_view_attendance'] ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="attendance_<?php echo $emp['id']; ?>">
                                                            <strong>View Attendance</strong>
                                                            <small class="d-block text-muted">Can view attendance history and mark attendance</small>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="rights[can_submit_adjustment]" id="adjustment_<?php echo $emp['id']; ?>" value="1" <?php echo $emp['rights']['can_submit_adjustment'] ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="adjustment_<?php echo $emp['id']; ?>">
                                                            <strong>Submit Adjustments</strong>
                                                            <small class="d-block text-muted">Can request attendance adjustments</small>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="rights[can_edit_profile]" id="profile_<?php echo $emp['id']; ?>" value="1" <?php echo $emp['rights']['can_edit_profile'] ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="profile_<?php echo $emp['id']; ?>">
                                                            <strong>Edit Profile</strong>
                                                            <small class="d-block text-muted">Can update their profile information</small>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="rights[can_view_reports]" id="reports_<?php echo $emp['id']; ?>" value="1" <?php echo $emp['rights']['can_view_reports'] ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="reports_<?php echo $emp['id']; ?>">
                                                            <strong>View Reports</strong>
                                                            <small class="d-block text-muted">Can access reports and analytics</small>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="rights[can_change_password]" id="password_<?php echo $emp['id']; ?>" value="1" <?php echo $emp['rights']['can_change_password'] ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="password_<?php echo $emp['id']; ?>">
                                                            <strong>Change Password</strong>
                                                            <small class="d-block text-muted">Can change their account password</small>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                                                <i class="fa fa-times"></i> Cancel
                                            </button>
                                            <button type="submit" class="btn btn-primary rounded-pill px-4">
                                                <i class="fa fa-save"></i> Save Rights
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa fa-users"></i>
                        <h5>No Employees Found</h5>
                        <p>Try adjusting your search criteria</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
<?php
mysqli_close($conn);
?>