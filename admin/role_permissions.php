<?php
session_start();
include("admincheck_role.php");
include("../config/db.php");

// Only Super Admin and Admin can access this
if (!in_array($admin_role, ['Super Admin', 'Admin'])) {
    header("Location: dashboard.php");
    exit();
}

// Save permissions
if (isset($_POST['save_permissions'])) {
    // Delete old permissions
    mysqli_query($conn, "DROP TABLE IF EXISTS role_permissions");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS role_permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        role_name VARCHAR(100) NOT NULL,
        page_key VARCHAR(100) NOT NULL,
        UNIQUE KEY unique_role_page (role_name, page_key)
    )");
    
    if (isset($_POST['perm']) && is_array($_POST['perm'])) {
        foreach ($_POST['perm'] as $role => $pages) {
            foreach ($pages as $page => $val) {
                $role = mysqli_real_escape_string($conn, $role);
                $page = mysqli_real_escape_string($conn, $page);
                mysqli_query($conn, "INSERT INTO role_permissions (role_name, page_key) VALUES ('$role', '$page')");
            }
        }
    }
    $message = "✅ Permissions saved successfully!";
}

// Get all roles from hierarchy
$allRoles = [];
$result = mysqli_query($conn, "SELECT role_name FROM role_hierarchy ORDER BY hierarchy_level ASC");
if (!$result || mysqli_num_rows($result) == 0) {
    // Fallback if no hierarchy table
    $allRoles = ['Super Admin','Admin','Operations Manager','WFM Executive','Finance Manager','Accountant','Supervisor','Team Lead','Employee'];
} else {
    while ($row = mysqli_fetch_assoc($result)) {
        $allRoles[] = $row['role_name'];
    }
}

// Define all pages/modules
$allPages = [
    'dashboard'       => 'Dashboard',
    'employees'       => 'Employees (View)',
    'add_employee'    => 'Add Employee',
    'edit_employee'   => 'Edit/Delete Employee',
    'leave_requests'  => 'Leave Requests',
    'attendance'      => 'Attendance Report',
    'manage_shifts'   => 'Manage Shifts',
    'adjustments'     => 'Attendance Adjustments',
    'reports'         => 'Reports',
    'payroll'         => 'Payroll Dashboard',
    'generate_payroll' => 'Generate Payroll',
    'salary_structure' => 'Salary Structure',
    'salary_slips'    => 'Salary Slips',
    'salary_components' => 'Salary Components',
    'notices'         => 'Notices',
    'holidays'        => 'Holidays',
    'change_password' => 'Change Password',
    'employee_status' => 'Employee Active/Inactive',
];

// Get saved permissions
$savedPerms = [];
$permResult = mysqli_query($conn, "SELECT * FROM role_permissions");
if ($permResult) {
    while ($row = mysqli_fetch_assoc($permResult)) {
        $savedPerms[$row['role_name']][$row['page_key']] = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Role Permissions</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<style>
body { background: #f4f7fc; font-family: 'Segoe UI', Tahoma, sans-serif; }
.container-fluid { padding: 30px; }
.page-header {
    background: linear-gradient(135deg, #1e293b, #334155);
    color: white;
    padding: 20px 30px;
    border-radius: 16px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.page-header h2 { margin: 0; font-weight: 700; }
.page-header h2 i { margin-right: 12px; color: #60a5fa; }
.card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,.08);
    overflow: hidden;
}
.card-header {
    background: linear-gradient(135deg, #2563eb, #3b82f6);
    color: white;
    padding: 15px 25px;
    font-weight: 600;
    font-size: 16px;
}
.card-header i { margin-right: 10px; }
.table th {
    background: #f8fafc;
    color: #475569;
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    padding: 15px 12px;
    border-bottom: 2px solid #e2e8f0;
    vertical-align: middle;
    text-align: center;
}
.table td {
    padding: 10px 12px;
    vertical-align: middle;
    text-align: center;
    border-bottom: 1px solid #f1f5f9;
    font-size: 14px;
}
.table td:first-child { 
    text-align: left; 
    font-weight: 600;
    color: #1e293b;
}
.table tbody tr:hover { background: #f8fafc; }
.role-header {
    font-weight: 700;
    font-size: 14px;
}
.form-check-input {
    width: 20px;
    height: 20px;
    cursor: pointer;
    border-radius: 4px;
    border: 2px solid #cbd5e1;
}
.form-check-input:checked {
    background-color: #2563eb;
    border-color: #2563eb;
}
.form-check-input:focus {
    box-shadow: none;
    border-color: #2563eb;
}
.level-badge {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}
.level-1 { background: #fee2e2; color: #dc2626; }
.level-2 { background: #fef3c7; color: #d97706; }
.level-3 { background: #dbeafe; color: #2563eb; }
.level-4 { background: #e0e7ff; color: #4f46e5; }
.level-5 { background: #f3e8ff; color: #7c3aed; }
.level-6 { background: #f1f5f9; color: #64748b; }
.btn-save {
    background: linear-gradient(135deg, #10b981, #059669);
    border: none;
    padding: 12px 40px;
    font-weight: 600;
    border-radius: 12px;
    font-size: 16px;
}
.btn-save:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(16,185,129,.4); }
.module-group-header {
    background: #eef2ff !important;
    font-weight: 700;
    color: #4338ca;
}
.sticky-col {
    position: sticky;
    left: 0;
    background: white;
    z-index: 2;
}
</style>
</head>
<body>

<div class="container-fluid">

    <!-- Header -->
    <div class="page-header">
        <div>
            <h2><i class="fa fa-shield-halved"></i> Role Permissions Manager</h2>
            <p class="mb-0" style="color: #94a3b8;">
                <i class="fa fa-user-shield me-1"></i> 
                <strong><?= htmlspecialchars($admin_name) ?></strong> 
                <span class="badge bg-primary ms-1"><?= htmlspecialchars($admin_role) ?></span>
                <span class="badge bg-secondary ms-1">ID: <?= intval($_SESSION['admin_id'] ?? 0) ?></span>
                — Simply tick (✓) which roles can access which features
            </p>
        </div>
        <a href="dashboard.php" class="btn btn-light rounded-pill px-4">
            <i class="fa fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <?php if (isset($message)): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-pill px-4">
            <i class="fa fa-check-circle me-2"></i> <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Permissions Card -->
    <div class="card">
        <div class="card-header">
            <i class="fa fa-check-double"></i> Tick the boxes to grant access
        </div>
        <div class="card-body p-0">
            <form method="POST">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead>
                            <tr>
                                <th style="min-width: 200px; text-align: left;">Module / Feature</th>
                                <?php foreach ($allRoles as $role): 
                                    $level = '';
                                    foreach ([1=>'Super Admin',2=>'Admin',3=>'Operations Manager',3=>'WFM Executive',3=>'Finance Manager',4=>'Accountant',4=>'Supervisor',5=>'Team Lead',6=>'Employee'] as $l=>$r) {
                                        if ($r == $role) { $level = $l; break; }
                                    }
                                ?>
                                    <th>
                                        <div class="role-header"><?= htmlspecialchars($role) ?></div>
                                        <?php if ($level): ?>
                                            <span class="level-badge level-<?= $level ?>">Lv <?= $level ?></span>
                                        <?php endif; ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $modules = [
                                'General' => ['dashboard', 'change_password'],
                                'Employees' => ['employees', 'add_employee', 'edit_employee', 'employee_status'],
                                'Attendance' => ['attendance', 'manage_shifts', 'adjustments'],
                                'Leaves' => ['leave_requests'],
                                'Reports' => ['reports'],
                                'Payroll' => ['payroll', 'generate_payroll', 'salary_structure', 'salary_slips', 'salary_components'],
                                'System' => ['notices', 'holidays'],
                            ];
                            
                            $pageModules = [];
                            foreach ($modules as $modName => $modPages) {
                                echo "<tr class='module-group-header'><td colspan='" . (count($allRoles) + 1) . "'><i class='fa fa-folder-open me-2'></i> $modName</td></tr>";
                                foreach ($modPages as $pageKey):
                                    if (!isset($allPages[$pageKey])) continue;
                            ?>
                                <tr>
                                    <td style="text-align: left; padding-left: 30px;">
                                        <i class="fa fa-angle-right text-muted me-2"></i> <?= $allPages[$pageKey] ?>
                                    </td>
                                    <?php foreach ($allRoles as $role): 
                                        $checked = isset($savedPerms[$role][$pageKey]) ? 'checked' : '';
                                    ?>
                                        <td>
                                            <input class="form-check-input" type="checkbox" 
                                                   name="perm[<?= htmlspecialchars($role) ?>][<?= $pageKey ?>]" 
                                                   value="1" <?= $checked ?>>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; 
                            } ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="p-4 text-center">
                    <button type="submit" name="save_permissions" class="btn btn-save btn-lg text-white px-5">
                        <i class="fa fa-floppy-disk me-2"></i> Save Permissions
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="text-center mt-4 text-muted" style="font-size: 13px;">
        <i class="fa fa-info-circle me-1"></i> Only <strong>Super Admin</strong> and <strong>Admin</strong> can manage permissions.
        Just tick the boxes and click Save!
    </div>
</div>

</body>
</html>