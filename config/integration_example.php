<?php
/**
 * Integration Example - How to use the Permission System
 * 
 * This file demonstrates how to integrate the permission system
 * into your existing EMS pages. Copy relevant examples to your pages.
 * 
 * @package EMS
 * @version 1.0
 */

// ============================================
// EXAMPLE 1: Basic Page Protection
// ============================================
// Add this at the TOP of any protected page

/*
// File: admin/dashboard.php
include("admincheck_role.php");
include("../config/db.php");
include("../config/permissions.php");

// Require 'view' permission for dashboard module
requirePermission($conn, 'dashboard', 'view');

// If user reaches here, they have permission
// Rest of your page code...
*/

// ============================================
// EXAMPLE 2: Employee Management Page
// ============================================
// File: admin/employee.php

/*
<?php
session_start();
include("admincheck_role.php");
include("../config/db.php");
include("../config/permissions.php");

// Check view permission
requirePermission($conn, 'employee_management', 'view');

// Get user's permissions for this module
$user_perms = getUserPermissions($conn, 'employee_management');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Employee Management</title>
</head>
<body>
    <h1>Employee Management</h1>
    
    <!-- Show Add button only if user has 'create' permission -->
    <?php if (hasPermission($conn, 'employee_management', 'create')): ?>
        <a href="add_employee.php" class="btn btn-success">
            <i class="fa fa-plus"></i> Add Employee
        </a>
    <?php endif; ?>
    
    <!-- Show Export button only if user has 'export' permission -->
    <?php if (hasPermission($conn, 'employee_management', 'export')): ?>
        <a href="export_employees.php" class="btn btn-info">
            <i class="fa fa-download"></i> Export to Excel
        </a>
    <?php endif; ?>
    
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Department</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $employees = mysqli_query($conn, "SELECT * FROM employees");
            while ($emp = mysqli_fetch_assoc($employees)):
            ?>
                <tr>
                    <td><?= $emp['employee_id'] ?></td>
                    <td><?= htmlspecialchars($emp['full_name']) ?></td>
                    <td><?= htmlspecialchars($emp['department']) ?></td>
                    <td>
                        <!-- Edit button - only if has 'edit' permission -->
                        <?php if (hasPermission($conn, 'employee_management', 'edit')): ?>
                            <a href="edit_employee.php?id=<?= $emp['id'] ?>" 
                               class="btn btn-primary btn-sm">
                                <i class="fa fa-edit"></i> Edit
                            </a>
                        <?php endif; ?>
                        
                        <!-- Delete button - only if has 'delete' permission -->
                        <?php if (hasPermission($conn, 'employee_management', 'delete')): ?>
                            <a href="delete_employee.php?id=<?= $emp['id'] ?>" 
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Are you sure?')">
                                <i class="fa fa-trash"></i> Delete
                            </a>
                        <?php endif; ?>
                        
                        <!-- No permissions message -->
                        <?php if (!hasAnyPermission($conn, 'employee_management', ['edit', 'delete'])): ?>
                            <span class="text-muted">No actions available</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
*/

// ============================================
// EXAMPLE 3: Add Employee Page (Create Permission)
// ============================================
// File: admin/add_employee.php

/*
<?php
session_start();
include("admincheck_role.php");
include("../config/db.php");
include("../config/permissions.php");

// Require 'create' permission
requirePermission($conn, 'employee_management', 'create');

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $department = $_POST['department'];
    
    // Insert employee
    $stmt = $conn->prepare("INSERT INTO employees (full_name, email, department) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $department);
    
    if ($stmt->execute()) {
        $message = "Employee added successfully!";
        logPermissionAccess($conn, 'employee_management', 'create', true);
    } else {
        $message = "Error adding employee!";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Employee</title>
</head>
<body>
    <h1>Add New Employee</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Department</label>
            <input type="text" name="department" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success">
            <i class="fa fa-save"></i> Save Employee
        </button>
    </form>
</body>
</html>
*/

// ============================================
// EXAMPLE 4: Leave Requests with Approve Permission
// ============================================
// File: admin/leave_requests.php

/*
<?php
session_start();
include("admincheck_role.php");
include("../config/db.php");
include("../config/permissions.php");

// Require view permission
requirePermission($conn, 'leave_management', 'view');

// Get all pending leave requests
$leaves = mysqli_query($conn, "
    SELECT lr.*, e.full_name, e.employee_id 
    FROM leave_requests lr
    JOIN employees e ON lr.employee_id = e.id
    WHERE lr.status = 'Pending'
    ORDER BY lr.created_at DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Leave Requests</title>
</head>
<body>
    <h1>Leave Requests</h1>
    
    <!-- Show approve button only if user has 'approve' permission -->
    <?php if (hasPermission($conn, 'leave_management', 'approve')): ?>
        <div class="alert alert-info">
            <i class="fa fa-info-circle"></i>
            You can approve/reject leave requests below.
        </div>
    <?php endif; ?>
    
    <table class="table">
        <thead>
            <tr>
                <th>Employee</th>
                <th>Leave Type</th>
                <th>Dates</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($leave = mysqli_fetch_assoc($leaves)): ?>
                <tr>
                    <td><?= htmlspecialchars($leave['full_name']) ?></td>
                    <td><?= htmlspecialchars($leave['leave_type']) ?></td>
                    <td>
                        <?= date('d-m-Y', strtotime($leave['start_date'])) ?>
                        to 
                        <?= date('d-m-Y', strtotime($leave['end_date'])) ?>
                    </td>
                    <td>
                        <span class="badge badge-warning"><?= $leave['status'] ?></span>
                    </td>
                    <td>
                        <!-- Approve button -->
                        <?php if (hasPermission($conn, 'leave_management', 'approve')): ?>
                            <a href="approve_leave.php?id=<?= $leave['id'] ?>" 
                               class="btn btn-success btn-sm"
                               onclick="return confirm('Approve this leave?')">
                                <i class="fa fa-check"></i> Approve
                            </a>
                            <a href="reject_leave.php?id=<?= $leave['id'] ?>" 
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Reject this leave?')">
                                <i class="fa fa-times"></i> Reject
                            </a>
                        <?php else: ?>
                            <span class="text-muted">No approval rights</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
*/

// ============================================
// EXAMPLE 5: Reports with Export Permission
// ============================================
// File: admin/reports.php

/*
<?php
session_start();
include("admincheck_role.php");
include("../config/db.php");
include("../config/permissions.php");

// Require view permission
requirePermission($conn, 'reports', 'view');

// Check if user can export
$can_export = hasPermission($conn, 'reports', 'export');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reports</title>
</head>
<body>
    <h1>Reports Dashboard</h1>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5>Employee Report</h5>
                    <p>View all employee details</p>
                    <a href="view_report.php?type=employees" class="btn btn-primary">
                        <i class="fa fa-eye"></i> View
                    </a>
                    
                    <?php if ($can_export): ?>
                        <a href="export_report.php?type=employees" class="btn btn-success">
                            <i class="fa fa-download"></i> Export Excel
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5>Attendance Report</h5>
                    <p>View attendance records</p>
                    <a href="view_report.php?type=attendance" class="btn btn-primary">
                        <i class="fa fa-eye"></i> View
                    </a>
                    
                    <?php if ($can_export): ?>
                        <a href="export_report.php?type=attendance" class="btn btn-success">
                            <i class="fa fa-download"></i> Export Excel
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5>Leave Report</h5>
                    <p>View leave statistics</p>
                    <a href="view_report.php?type=leaves" class="btn btn-primary">
                        <i class="fa fa-eye"></i> View
                    </a>
                    
                    <?php if ($can_export): ?>
                        <a href="export_report.php?type=leaves" class="btn btn-success">
                            <i class="fa fa-download"></i> Export Excel
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
*/

// ============================================
// EXAMPLE 6: Conditional Menu Items
// ============================================
// Show/hide sidebar menu items based on permissions

/*
<?php
// In your sidebar or navigation
$menu_items = [];

// Dashboard - everyone can view
if (hasPermission($conn, 'dashboard', 'view')) {
    $menu_items[] = [
        'icon' => 'fa-gauge-high',
        'text' => 'Dashboard',
        'url' => 'dashboard.php'
    ];
}

// Employee Management
if (hasPermission($conn, 'employee_management', 'view')) {
    $menu_items[] = [
        'icon' => 'fa-users',
        'text' => 'Employees',
        'url' => 'employee.php'
    ];
}

// Add Employee
if (hasPermission($conn, 'employee_management', 'create')) {
    $menu_items[] = [
        'icon' => 'fa-user-plus',
        'text' => 'Add Employee',
        'url' => 'add_employee.php'
    ];
}

// Leave Management
if (hasPermission($conn, 'leave_management', 'view')) {
    $menu_items[] = [
        'icon' => 'fa-calendar-check',
        'text' => 'Leave Requests',
        'url' => 'leave_requests.php'
    ];
}

// Attendance
if (hasPermission($conn, 'attendance', 'view')) {
    $menu_items[] = [
        'icon' => 'fa-clock',
        'text' => 'Attendance',
        'url' => 'attendance_report.php'
    ];
}

// Payroll
if (hasPermission($conn, 'payroll', 'view')) {
    $menu_items[] = [
        'icon' => 'fa-money-bill-wave',
        'text' => 'Payroll',
        'url' => 'payroll_dashboard.php'
    ];
}

// Reports
if (hasPermission($conn, 'reports', 'view')) {
    $menu_items[] = [
        'icon' => 'fa-chart-column',
        'text' => 'Reports',
        'url' => 'reports.php'
    ];
}

// Role & Permission Management - Super Admin only
if (hasPermission($conn, 'role_permission', 'view')) {
    $menu_items[] = [
        'icon' => 'fa-key',
        'text' => 'Role & Permissions',
        'url' => 'role_permissions.php'
    ];
}

// Display menu
foreach ($menu_items as $item):
?>
    <a href="<?= $item['url'] ?>" class="sidebar-link">
        <i class="fa <?= $item['icon'] ?>"></i> <?= $item['text'] ?>
    </a>
<?php
endforeach;
*/

// ============================================
// EXAMPLE 7: Multiple Permission Check
// ============================================
// Check multiple permissions at once

/*
<?php
// Check if user can manage employees (view + edit + delete)
if (hasAllPermissions($conn, 'employee_management', ['view', 'edit', 'delete'])) {
    echo "<div class='alert alert-success'>";
    echo "You have full employee management access!";
    echo "</div>";
}

// Check if user can do ANY of these actions
if (hasAnyPermission($conn, 'employee_management', ['create', 'edit', 'delete'])) {
    echo "<div class='alert alert-info'>";
    echo "You can modify employee data.";
    echo "</div>";
}

// Check specific combination
if (hasPermission($conn, 'employee_management', 'view') && 
    hasPermission($conn, 'employee_management', 'export')) {
    echo "<a href='export_employees.php' class='btn btn-info'>Export Report</a>";
}
*/

// ============================================
// EXAMPLE 8: Role-Based Content
// ============================================
// Show different content based on user role

/*
<?php
$user_role = getCurrentUserRole($conn);

if ($user_role === 'Super Admin') {
    // Show Super Admin specific content
    echo "<div class='alert alert-danger'>";
    echo "<i class='fa fa-crown'></i> <strong>Super Admin Access</strong>";
    echo "<p>You have full system access including user management.</p>";
    echo "</div>";
    
    // Show user management link
    echo '<a href="user_management.php" class="btn btn-danger">Manage Users</a>';
}

if ($user_role === 'Admin') {
    // Show Admin specific content
    echo "<div class='alert alert-warning'>";
    echo "<i class='fa fa-user-shield'></i> <strong>Admin Access</strong>";
    echo "<p>You can manage employees and departments.</p>";
    echo "</div>";
}

if (in_array($user_role, ['Super Admin', 'Admin', 'Operations Manager'])) {
    // Show content for high-level roles
    echo '<a href="manage_departments.php" class="btn btn-primary">Manage Departments</a>';
}
*/

// ============================================
// EXAMPLE 9: Attendance Page with Approve Permission
// ============================================
// File: admin/attendance_report.php

/*
<?php
session_start();
include("admincheck_role.php");
include("../config/db.php");
include("../config/permissions.php");

// Require view permission
requirePermission($conn, 'attendance', 'view');

// Check if user can edit attendance
$can_edit = hasPermission($conn, 'attendance', 'edit');

// Check if user can approve attendance
$can_approve = hasPermission($conn, 'attendance', 'approve');

// Get attendance data
$attendance = mysqli_query($conn, "
    SELECT a.*, e.full_name, e.employee_id
    FROM attendance a
    JOIN employees e ON a.employee_id = e.id
    WHERE a.attendance_date = CURDATE()
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Attendance Report</title>
</head>
<body>
    <h1>Today's Attendance</h1>
    
    <table class="table">
        <thead>
            <tr>
                <th>Employee ID</th>
                <th>Name</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Status</th>
                <?php if ($can_edit || $can_approve): ?>
                    <th>Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($attendance)): ?>
                <tr>
                    <td><?= $row['employee_id'] ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= $row['time_in'] ?></td>
                    <td><?= $row['time_out'] ?></td>
                    <td>
                        <span class="badge badge-<?= $row['status'] == 'Present' ? 'success' : 'danger' ?>">
                            <?= $row['status'] ?>
                        </span>
                    </td>
                    
                    <?php if ($can_edit || $can_approve): ?>
                        <td>
                            <?php if ($can_edit): ?>
                                <a href="edit_attendance.php?id=<?= $row['id'] ?>" 
                                   class="btn btn-primary btn-sm">
                                    <i class="fa fa-edit"></i> Edit
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($can_approve): ?>
                                <a href="approve_attendance.php?id=<?= $row['id'] ?>" 
                                   class="btn btn-success btn-sm">
                                    <i class="fa fa-check"></i> Approve
                                </a>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
*/

// ============================================
// EXAMPLE 10: Complete Page Template
// ============================================
// Use this as a template for new pages

/*
<?php
session_start();
include("admincheck_role.php");
include("../config/db.php");
include("../config/permissions.php");

// Define module name
$module = 'employee_management';

// Require view permission (redirects if no access)
requirePermission($conn, $module, 'view');

// Get all user permissions for this module
$user_perms = getUserPermissions($conn, $module);

// Page title
$page_title = "Employee Management";

// Include header (you can create a common header file)
include_once("includes/header.php");
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <h1><?= $page_title ?></h1>
    </div>
    
    <!-- Action Buttons (conditional) -->
    <div class="mb-3">
        <?php if (hasPermission($conn, $module, 'create')): ?>
            <a href="add_employee.php" class="btn btn-success">
                <i class="fa fa-plus"></i> Add New
            </a>
        <?php endif; ?>
        
        <?php if (hasPermission($conn, $module, 'export')): ?>
            <a href="export.php" class="btn btn-info">
                <i class="fa fa-download"></i> Export
            </a>
        <?php endif; ?>
    </div>
    
    <!-- Main Content -->
    <div class="card">
        <div class="card-body">
            <!-- Your page content here -->
            <p>This page is protected by the permission system.</p>
            <p>Your permissions: <?= implode(', ', $user_perms) ?></p>
        </div>
    </div>
</div>

<?php
// Include footer
include_once("includes/footer.php");
?>
*/

// ============================================
// EXAMPLE 11: AJAX Permission Check
// ============================================
// Check permissions via AJAX

/*
// File: admin/check_permission_ajax.php
<?php
session_start();
include("admincheck_role.php");
include("../config/db.php");
include("../config/permissions.php");

header('Content-Type: application/json');

$module = $_POST['module'] ?? '';
$permission = $_POST['permission'] ?? 'view';

$has_permission = hasPermission($conn, $module, $permission);

echo json_encode([
    'has_permission' => $has_permission,
    'module' => $module,
    'permission' => $permission
]);
?>

// JavaScript usage:
<script>
function checkPermission(module, permission) {
    return fetch('check_permission_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'module=' + module + '&permission=' + permission
    })
    .then(response => response.json())
    .then(data => {
        return data.has_permission;
    });
}

// Usage
checkPermission('employee_management', 'create').then(hasCreate => {
    if (hasCreate) {
        console.log('User can create employees');
    } else {
        console.log('User cannot create employees');
    }
});
</script>
*/

// ============================================
// EXAMPLE 12: Permission-Based Redirect
// ============================================
// Redirect users to appropriate pages based on permissions

/*
<?php
session_start();
include("admincheck_role.php");
include("../config/db.php");
include("../config/permissions.php");

// After login, redirect based on permissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    // ... validate login ...
    
    // Check permissions and redirect
    if (hasPermission($conn, 'dashboard', 'view')) {
        header("Location: dashboard.php");
    } elseif (hasPermission($conn, 'employee_management', 'view')) {
        header("Location: employee.php");
    } elseif (hasPermission($conn, 'attendance', 'view')) {
        header("Location: attendance_report.php");
    } else {
        // No permissions - show message
        $_SESSION['error'] = "You don't have permission to access any modules.";
        header("Location: access_denied.php");
    }
    exit();
}
*/

// ============================================
// TESTING YOUR PERMISSIONS
// ============================================
// Create a test page to verify permissions work

/*
// File: admin/test_permissions.php
<?php
session_start();
include("admincheck_role.php");
include("../config/db.php");
include("../config/permissions.php");

// Only allow Super Admin to test
if (!isSuperAdmin()) {
    header("Location: dashboard.php");
    exit();
}

$modules = getAllModules();
$permissions = getAllPermissions();
$user_role = getCurrentUserRole($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Permission Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Permission Test Page</h1>
        
        <div class="alert alert-info">
            <strong>Logged in as:</strong> <?= htmlspecialchars($admin_name) ?><br>
            <strong>Role:</strong> <?= htmlspecialchars($user_role) ?><br>
            <strong>Is Super Admin:</strong> <?= isSuperAdmin() ? 'Yes' : 'No' ?>
        </div>
        
        <h3>Test All Modules</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Module</th>
                    <?php foreach ($permissions as $perm): ?>
                        <th><?= ucfirst($perm['permission_slug']) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($modules as $module_key => $module_name): ?>
                    <tr>
                        <td><strong><?= $module_name ?></strong></td>
                        <?php foreach ($permissions as $perm): ?>
                            <?php
                            $has_perm = hasPermission($conn, $module_key, $perm['permission_slug']);
                            $badge_class = $has_perm ? 'success' : 'danger';
                            $icon = $has_perm ? 'check' : 'times';
                            ?>
                            <td class="text-center">
                                <span class="badge bg-<?= $badge_class ?>">
                                    <i class="fa fa-<?= $icon ?>"></i>
                                </span>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h3 class="mt-4">Test Specific Permission</h3>
        <div class="input-group mb-3">
            <select class="form-select" id="moduleSelect">
                <?php foreach ($modules as $key => $name): ?>
                    <option value="<?= $key ?>"><?= $name ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select" id="permissionSelect">
                <?php foreach ($permissions as $perm): ?>
                    <option value="<?= $perm['permission_slug'] ?>">
                        <?= ucfirst($perm['permission_slug']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary" onclick="checkPermission()">
                Check Permission
            </button>
        </div>
        
        <div id="result" class="alert" style="display: none;"></div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function checkPermission() {
            const module = document.getElementById('moduleSelect').value;
            const permission = document.getElementById('permissionSelect').value;
            
            // This would need AJAX - simplified for demo
            const resultDiv = document.getElementById('result');
            resultDiv.style.display = 'block';
            resultDiv.className = 'alert alert-info';
            resultDiv.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Check via AJAX...';
        }
    </script>
</body>
</html>
*/

// ============================================
// IMPORTANT NOTES
// ============================================

/*
1. ALWAYS include permission checks at the TOP of protected pages
2. Use requirePermission() for critical pages (auto-redirects)
3. Use hasPermission() for conditional UI elements
4. NEVER rely solely on hiding UI elements - always check server-side
5. Test with different roles to ensure proper access control
6. Log important actions for audit trail
7. Keep module names consistent across all pages
8. Document which permissions each page requires
9. Regularly review permission logs for security
10. Backup permission configuration before major changes
*/

// ============================================
// QUICK REFERENCE
// ============================================

/*
FUNCTION USAGE:
---------------
hasPermission($conn, 'module', 'permission')
    → Returns true/false

requirePermission($conn, 'module', 'permission')
    → Redirects to access_denied.php if no permission

hasAnyPermission($conn, 'module', ['perm1', 'perm2'])
    → Returns true if user has ANY of the permissions

hasAllPermissions($conn, 'module', ['perm1', 'perm2'])
    → Returns true if user has ALL permissions

getUserPermissions($conn, 'module')
    → Returns array of all permissions for current user

isSuperAdmin()
    → Returns true if current user is Super Admin

getCurrentUserRole($conn)
    → Returns current user's role name

MODULE NAMES:
-------------
'dashboard'                    → Dashboard
'employee_management'          → Employee Management
'attendance'                   → Attendance
'leave_management'             → Leave Management
'payroll'                      → Payroll
'department'                   → Department
'reports'                      → Reports
'notifications'                → Notifications
'settings'                     → Settings
'user_management'              → User Management
'role_permission'              → Role & Permission

PERMISSION TYPES:
-----------------
'view'      → View/Read data
'create'    → Add new records
'edit'      → Modify existing records
'delete'     → Remove records
'approve'   → Approve requests
'export'    → Export to Excel/PDF
*/

?>