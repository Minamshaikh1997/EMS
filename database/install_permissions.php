<?php
/**
 * Role & Permission Management System - Installer
 * 
 * This script will:
 * 1. Check database connection
 * 2. Create required tables
 * 3. Insert sample data
 * 4. Verify installation
 * 
 * Usage: Navigate to http://localhost/EMS/database/install_permissions.php
 * 
 * @package EMS
 * @version 1.0
 */

// Display errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Database configuration
$host = "localhost";
$user = "root";
$password = "";
$database = "employee_leave_system";

// Installation steps
$steps = [];
$errors = [];
$success = [];

// Function to execute SQL query
function executeQuery($conn, $sql, $step_name) {
    global $errors, $success;
    
    if (mysqli_query($conn, $sql)) {
        $success[] = "✅ $step_name";
        return true;
    } else {
        $errors[] = "❌ $step_name: " . mysqli_error($conn);
        return false;
    }
}

// Function to check if table exists
function tableExists($conn, $tableName) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
    return mysqli_num_rows($result) > 0;
}

// Process installation
if (isset($_POST['install'])) {
    // Connect to database
    $conn = mysqli_connect($host, $user, $password, $database);
    
    if (!$conn) {
        $errors[] = "❌ Database connection failed: " . mysqli_connect_error();
    } else {
        // Step 1: Create roles table
        $sql = "CREATE TABLE IF NOT EXISTS roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            role_name VARCHAR(100) NOT NULL UNIQUE,
            role_slug VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            hierarchy_level INT NOT NULL DEFAULT 6,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_hierarchy (hierarchy_level),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        executeQuery($conn, $sql, "Created 'roles' table");
        
        // Step 2: Create permissions table
        $sql = "CREATE TABLE IF NOT EXISTS permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            permission_name VARCHAR(100) NOT NULL UNIQUE,
            permission_slug VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_slug (permission_slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        executeQuery($conn, $sql, "Created 'permissions' table");
        
        // Step 3: Create role_permissions table
        $sql = "CREATE TABLE IF NOT EXISTS role_permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            role_id INT NOT NULL,
            permission_id INT NOT NULL,
            module_name VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_role_permission_module (role_id, permission_id, module_name),
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
            FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
            INDEX idx_role_module (role_id, module_name),
            INDEX idx_permission (permission_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        executeQuery($conn, $sql, "Created 'role_permissions' table");
        
        // Step 4: Create permission_logs table
        $sql = "CREATE TABLE IF NOT EXISTS permission_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT DEFAULT 0,
            admin_email VARCHAR(255) DEFAULT '',
            role VARCHAR(100) DEFAULT 'Unknown',
            module VARCHAR(100) NOT NULL,
            permission VARCHAR(100) NOT NULL,
            granted TINYINT(1) DEFAULT 1,
            ip_address VARCHAR(45) DEFAULT '',
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_admin (admin_id),
            INDEX idx_module (module),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        executeQuery($conn, $sql, "Created 'permission_logs' table");
        
        // Step 5: Insert roles
        $sql = "INSERT IGNORE INTO roles (role_name, role_slug, description, hierarchy_level) VALUES
        ('Super Admin', 'super_admin', 'Full system access. Can manage all users, settings, and data.', 1),
        ('Admin', 'admin', 'Managing Director. Manages departments, managers, employees.', 2),
        ('VP', 'vp', 'Vice President. High-level oversight and reporting.', 3),
        ('Supervisor', 'supervisor', 'Manages Team Leads. Approves attendance and leave requests.', 4),
        ('Team Lead', 'team_lead', 'Manages employees in their team. Approves daily attendance.', 5),
        ('WFM', 'wfm', 'Workforce Management. Manages attendance, shifts, schedules.', 3),
        ('Operations Manager', 'operations_manager', 'Manages operations and supervises teams.', 3),
        ('Employee', 'employee', 'Can view own profile, mark attendance, request leave.', 6)";
        executeQuery($conn, $sql, "Inserted 8 roles");
        
        // Step 6: Insert permissions
        $sql = "INSERT IGNORE INTO permissions (permission_name, permission_slug, description) VALUES
        ('View', 'view', 'Can view/module data'),
        ('Create', 'create', 'Can create new records'),
        ('Edit', 'edit', 'Can edit existing records'),
        ('Delete', 'delete', 'Can delete records'),
        ('Approve', 'approve', 'Can approve requests/records'),
        ('Export', 'export', 'Can export data to Excel/PDF')";
        executeQuery($conn, $sql, "Inserted 6 permissions");
        
        // Step 7: Get role IDs
        $role_ids = [];
        $result = mysqli_query($conn, "SELECT id, role_slug FROM roles");
        while ($row = mysqli_fetch_assoc($result)) {
            $role_ids[$row['role_slug']] = $row['id'];
        }
        
        // Step 8: Get permission IDs
        $permission_ids = [];
        $result = mysqli_query($conn, "SELECT id, permission_slug FROM permissions");
        while ($row = mysqli_fetch_assoc($result)) {
            $permission_ids[$row['permission_slug']] = $row['id'];
        }
        
        // Step 9: Clear existing permissions
        mysqli_query($conn, "DELETE FROM role_permissions");
        $success[] = "✅ Cleared existing permissions";
        
        // Step 10: Insert Super Admin permissions (all permissions on all modules)
        $modules = [
            'dashboard', 'employee_management', 'attendance', 'leave_management',
            'payroll', 'department', 'reports', 'notifications', 'settings',
            'user_management', 'role_permission'
        ];
        
        $super_admin_id = $role_ids['super_admin'] ?? 0;
        $total_perms = 0;
        
        if ($super_admin_id > 0) {
            foreach ($modules as $module) {
                foreach ($permission_ids as $perm_id) {
                    $sql = "INSERT IGNORE INTO role_permissions (role_id, permission_id, module_name) 
                            VALUES ($super_admin_id, $perm_id, '$module')";
                    if (mysqli_query($conn, $sql)) {
                        $total_perms++;
                    }
                }
            }
            $success[] = "✅ Granted all permissions to Super Admin ($total_perms permissions)";
        }
        
        // Step 11: Insert Admin permissions
        $admin_id = $role_ids['admin'] ?? 0;
        $admin_perms = [
            'dashboard' => ['view'],
            'employee_management' => ['view', 'create', 'edit', 'delete', 'export'],
            'attendance' => ['view', 'create', 'edit', 'delete', 'approve', 'export'],
            'leave_management' => ['view', 'create', 'edit', 'delete', 'approve', 'export'],
            'payroll' => ['view', 'create', 'edit', 'delete', 'approve', 'export'],
            'department' => ['view', 'create', 'edit', 'delete', 'export'],
            'reports' => ['view', 'export'],
            'notifications' => ['view', 'create', 'edit', 'delete'],
            'settings' => ['view', 'edit'],
            'user_management' => ['view', 'create', 'edit', 'delete'],
            'role_permission' => ['view', 'edit']
        ];
        
        $admin_perms_count = 0;
        if ($admin_id > 0) {
            foreach ($admin_perms as $module => $perms) {
                foreach ($perms as $perm_slug) {
                    if (isset($permission_ids[$perm_slug])) {
                        $perm_id = $permission_ids[$perm_slug];
                        $sql = "INSERT IGNORE INTO role_permissions (role_id, permission_id, module_name) 
                                VALUES ($admin_id, $perm_id, '$module')";
                        if (mysqli_query($conn, $sql)) {
                            $admin_perms_count++;
                        }
                    }
                }
            }
            $success[] = "✅ Granted permissions to Admin ($admin_perms_count permissions)";
        }
        
        // Step 12: Insert VP permissions
        $vp_id = $role_ids['vp'] ?? 0;
        $vp_perms = [
            'dashboard' => ['view'],
            'employee_management' => ['view'],
            'attendance' => ['view'],
            'leave_management' => ['view', 'approve'],
            'payroll' => ['view'],
            'reports' => ['view', 'export'],
            'notifications' => ['view']
        ];
        
        $vp_perms_count = 0;
        if ($vp_id > 0) {
            foreach ($vp_perms as $module => $perms) {
                foreach ($perms as $perm_slug) {
                    if (isset($permission_ids[$perm_slug])) {
                        $perm_id = $permission_ids[$perm_slug];
                        $sql = "INSERT IGNORE INTO role_permissions (role_id, permission_id, module_name) 
                                VALUES ($vp_id, $perm_id, '$module')";
                        if (mysqli_query($conn, $sql)) {
                            $vp_perms_count++;
                        }
                    }
                }
            }
            $success[] = "✅ Granted permissions to VP ($vp_perms_count permissions)";
        }
        
        // Step 13: Insert Operations Manager permissions
        $ops_id = $role_ids['operations_manager'] ?? 0;
        $ops_perms = [
            'dashboard' => ['view'],
            'employee_management' => ['view', 'create', 'edit'],
            'attendance' => ['view', 'approve'],
            'leave_management' => ['view', 'approve'],
            'reports' => ['view', 'export']
        ];
        
        $ops_perms_count = 0;
        if ($ops_id > 0) {
            foreach ($ops_perms as $module => $perms) {
                foreach ($perms as $perm_slug) {
                    if (isset($permission_ids[$perm_slug])) {
                        $perm_id = $permission_ids[$perm_slug];
                        $sql = "INSERT IGNORE INTO role_permissions (role_id, permission_id, module_name) 
                                VALUES ($ops_id, $perm_id, '$module')";
                        if (mysqli_query($conn, $sql)) {
                            $ops_perms_count++;
                        }
                    }
                }
            }
            $success[] = "✅ Granted permissions to Operations Manager ($ops_perms_count permissions)";
        }
        
        // Step 14: Insert WFM permissions
        $wfm_id = $role_ids['wfm'] ?? 0;
        $wfm_perms = [
            'dashboard' => ['view'],
            'employee_management' => ['view'],
            'attendance' => ['view', 'create', 'edit', 'approve', 'export'],
            'leave_management' => ['view', 'approve'],
            'reports' => ['view', 'export']
        ];
        
        $wfm_perms_count = 0;
        if ($wfm_id > 0) {
            foreach ($wfm_perms as $module => $perms) {
                foreach ($perms as $perm_slug) {
                    if (isset($permission_ids[$perm_slug])) {
                        $perm_id = $permission_ids[$perm_slug];
                        $sql = "INSERT IGNORE INTO role_permissions (role_id, permission_id, module_name) 
                                VALUES ($wfm_id, $perm_id, '$module')";
                        if (mysqli_query($conn, $sql)) {
                            $wfm_perms_count++;
                        }
                    }
                }
            }
            $success[] = "✅ Granted permissions to WFM ($wfm_perms_count permissions)";
        }
        
        // Step 15: Insert Supervisor permissions
        $supervisor_id = $role_ids['supervisor'] ?? 0;
        $supervisor_perms = [
            'dashboard' => ['view'],
            'employee_management' => ['view'],
            'attendance' => ['view', 'approve'],
            'leave_management' => ['view', 'approve']
        ];
        
        $supervisor_perms_count = 0;
        if ($supervisor_id > 0) {
            foreach ($supervisor_perms as $module => $perms) {
                foreach ($perms as $perm_slug) {
                    if (isset($permission_ids[$perm_slug])) {
                        $perm_id = $permission_ids[$perm_slug];
                        $sql = "INSERT IGNORE INTO role_permissions (role_id, permission_id, module_name) 
                                VALUES ($supervisor_id, $perm_id, '$module')";
                        if (mysqli_query($conn, $sql)) {
                            $supervisor_perms_count++;
                        }
                    }
                }
            }
            $success[] = "✅ Granted permissions to Supervisor ($supervisor_perms_count permissions)";
        }
        
        // Step 16: Insert Team Lead permissions
        $teamlead_id = $role_ids['team_lead'] ?? 0;
        $teamlead_perms = [
            'dashboard' => ['view'],
            'employee_management' => ['view'],
            'attendance' => ['view'],
            'leave_management' => ['view']
        ];
        
        $teamlead_perms_count = 0;
        if ($teamlead_id > 0) {
            foreach ($teamlead_perms as $module => $perms) {
                foreach ($perms as $perm_slug) {
                    if (isset($permission_ids[$perm_slug])) {
                        $perm_id = $permission_ids[$perm_slug];
                        $sql = "INSERT IGNORE INTO role_permissions (role_id, permission_id, module_name) 
                                VALUES ($teamlead_id, $perm_id, '$module')";
                        if (mysqli_query($conn, $sql)) {
                            $teamlead_perms_count++;
                        }
                    }
                }
            }
            $success[] = "✅ Granted permissions to Team Lead ($teamlead_perms_count permissions)";
        }
        
        // Step 17: Insert Employee permissions
        $employee_id = $role_ids['employee'] ?? 0;
        $employee_perms = [
            'dashboard' => ['view'],
            'employee_management' => ['view'],
            'attendance' => ['view'],
            'leave_management' => ['create', 'view'],
            'notifications' => ['view']
        ];
        
        $employee_perms_count = 0;
        if ($employee_id > 0) {
            foreach ($employee_perms as $module => $perms) {
                foreach ($perms as $perm_slug) {
                    if (isset($permission_ids[$perm_slug])) {
                        $perm_id = $permission_ids[$perm_slug];
                        $sql = "INSERT IGNORE INTO role_permissions (role_id, permission_id, module_name) 
                                VALUES ($employee_id, $perm_id, '$module')";
                        if (mysqli_query($conn, $sql)) {
                            $employee_perms_count++;
                        }
                    }
                }
            }
            $success[] = "✅ Granted permissions to Employee ($employee_perms_count permissions)";
        }
        
        // Step 18: Add indexes for performance
        $sql = "CREATE INDEX IF NOT EXISTS idx_role_module ON role_permissions(role_id, module_name)";
        executeQuery($conn, $sql, "Added performance indexes");
        
        // Close connection
        mysqli_close($conn);
        
        // Set installation complete flag
        $_SESSION['installation_complete'] = true;
    }
}

// Check if already installed
$conn = mysqli_connect($host, $user, $password, $database);
$already_installed = false;
if ($conn) {
    if (tableExists($conn, 'roles') && tableExists($conn, 'permissions') && tableExists($conn, 'role_permissions')) {
        $already_installed = true;
    }
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role & Permission System Installer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 0;
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }
        .installer-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 900px;
            margin: 0 auto;
            overflow: hidden;
        }
        .installer-header {
            background: linear-gradient(135deg, #1e293b, #334155);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .installer-header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
        }
        .installer-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        .installer-body {
            padding: 40px;
        }
        .step {
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            background: #f8fafc;
            border-left: 4px solid #cbd5e1;
        }
        .step.success {
            background: #d1fae5;
            border-left-color: #10b981;
        }
        .step.error {
            background: #fee2e2;
            border-left-color: #ef4444;
        }
        .btn-install {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            color: white;
            padding: 16px 50px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-install:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(16,185,129,0.4);
            color: white;
        }
        .alert-custom {
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .feature-list {
            list-style: none;
            padding: 0;
        }
        .feature-list li {
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .feature-list li:last-child {
            border-bottom: none;
        }
        .feature-list i {
            color: #10b981;
            margin-right: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="installer-container">
        <!-- Header -->
        <div class="installer-header">
            <h1><i class="fa-solid fa-shield-halved me-3"></i>Role & Permission System</h1>
            <p>Professional RBAC Installation Wizard</p>
        </div>
        
        <!-- Body -->
        <div class="installer-body">
            
            <?php if (isset($_SESSION['installation_complete']) && $_SESSION['installation_complete']): ?>
                <!-- Success Message -->
                <div class="alert alert-success alert-custom">
                    <h3><i class="fa fa-check-circle me-2"></i>Installation Complete!</h3>
                    <p class="mb-0">The Role & Permission Management System has been successfully installed.</p>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fa fa-list-check me-2"></i>Installation Summary</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($success as $msg): ?>
                                    <div class="step success">
                                        <?= $msg ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fa fa-rocket me-2"></i>Next Steps</h5>
                            </div>
                            <div class="card-body">
                                <ul class="feature-list">
                                    <li><i class="fa fa-check"></i> <strong>Login as Super Admin</strong></li>
                                    <li><i class="fa fa-check"></i> <strong>Go to:</strong> <a href="role_permissions.php">Role & Permission Management</a></li>
                                    <li><i class="fa fa-check"></i> <strong>Configure</strong> permissions for each role</li>
                                    <li><i class="fa fa-check"></i> <strong>Test</strong> with different user roles</li>
                                    <li><i class="fa fa-check"></i> <strong>Review</strong> <a href="test_permissions.php">Test Page</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center">
                    <a href="../admin/role_permissions.php" class="btn btn-install me-3">
                        <i class="fa fa-key me-2"></i>Manage Permissions
                    </a>
                    <a href="../admin/dashboard.php" class="btn btn-secondary btn-lg">
                        <i class="fa fa-gauge me-2"></i>Go to Dashboard
                    </a>
                </div>
                
                <?php unset($_SESSION['installation_complete']); ?>
                
            <?php elseif ($already_installed): ?>
                <!-- Already Installed -->
                <div class="alert alert-warning alert-custom">
                    <h3><i class="fa fa-exclamation-triangle me-2"></i>Already Installed</h3>
                    <p class="mb-0">The Role & Permission Management System appears to be already installed.</p>
                </div>
                
                <div class="text-center mt-4">
                    <a href="../admin/role_permissions.php" class="btn btn-install me-3">
                        <i class="fa fa-key me-2"></i>Manage Permissions
                    </a>
                    <a href="../admin/test_permissions.php" class="btn btn-info btn-lg me-3">
                        <i class="fa fa-flask me-2"></i>Test System
                    </a>
                    <a href="../admin/dashboard.php" class="btn btn-secondary btn-lg">
                        <i class="fa fa-gauge me-2"></i>Dashboard
                    </a>
                </div>
                
            <?php else: ?>
                <!-- Installation Form -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="alert alert-info alert-custom">
                            <h4><i class="fa fa-info-circle me-2"></i>Welcome to the Installer</h4>
                            <p class="mb-0">This wizard will set up the Role & Permission Management System for your EMS. Click the button below to begin.</p>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fa fa-database me-2"></i>Database Information</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Host:</strong></td>
                                        <td><?= htmlspecialchars($host) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Database:</strong></td>
                                        <td><?= htmlspecialchars($database) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Username:</strong></td>
                                        <td><?= htmlspecialchars($user) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td>
                                            <?php
                                            $test_conn = mysqli_connect($host, $user, $password, $database);
                                            if ($test_conn) {
                                                echo '<span class="badge bg-success">Connected</span>';
                                                mysqli_close($test_conn);
                                            } else {
                                                echo '<span class="badge bg-danger">Failed</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fa fa-list-check me-2"></i>What will be installed:</h5>
                            </div>
                            <div class="card-body">
                                <ul class="feature-list">
                                    <li><i class="fa fa-check"></i> 4 Database Tables</li>
                                    <li><i class="fa fa-check"></i> 8 User Roles</li>
                                    <li><i class="fa fa-check"></i> 6 Permission Types</li>
                                    <li><i class="fa fa-check"></i> 11 Modules</li>
                                    <li><i class="fa fa-check"></i> Sample Permissions</li>
                                    <li><i class="fa fa-check"></i> Performance Indexes</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <h5><i class="fa fa-exclamation-circle me-2"></i>Errors:</h5>
                        <?php foreach ($errors as $error): ?>
                            <div><?= $error ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="text-center mt-4">
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="install" class="btn btn-install btn-lg">
                            <i class="fa fa-download me-2"></i>Install Now
                        </button>
                    </form>
                </div>
                
                <div class="alert alert-warning mt-4">
                    <i class="fa fa-exclamation-triangle me-2"></i>
                    <strong>Note:</strong> This will create new tables and insert sample data. Make sure you have a database backup before proceeding.
                </div>
                
            <?php endif; ?>
            
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
<?php
// Close connection if open
if (isset($conn) && $conn) {
    mysqli_close($conn);
}
?>