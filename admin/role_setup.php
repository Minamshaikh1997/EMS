<?php
session_start();
include("../config/db.php");

// Only Super Admin (CEO) can run this
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.html");
    exit();
}

$message = "";
$error = "";

if (isset($_POST['run_setup'])) {
    
    // ==========================================
    // 1. Add new columns to employees table
    // ==========================================
    $alter_queries = [
        "ALTER TABLE employees ADD COLUMN IF NOT EXISTS status ENUM('Active','Inactive','Suspended','Terminated') DEFAULT 'Active'",
        "ALTER TABLE employees ADD COLUMN IF NOT EXISTS reporting_manager_id INT DEFAULT NULL",
        "ALTER TABLE employees ADD COLUMN IF NOT EXISTS reporting_supervisor_id INT DEFAULT NULL",
        "ALTER TABLE employees ADD COLUMN IF NOT EXISTS reporting_team_lead_id INT DEFAULT NULL",
    ];
    
    foreach ($alter_queries as $q) {
        mysqli_query($conn, $q);
    }
    
    // ==========================================
    // 2. Create admin_role column in admin table
    // ==========================================
    mysqli_query($conn, "ALTER TABLE admin ADD COLUMN IF NOT EXISTS role VARCHAR(100) DEFAULT 'Admin'");
    
    // ==========================================
    // 3. Update Super Admin (first admin user)
    // ==========================================
    mysqli_query($conn, "UPDATE admin SET role='Super Admin' WHERE id=1 OR email='superadmin@ems.com' OR role='CEO'");
    
    // ==========================================
    // 4. Create role_hierarchy table
    // ==========================================
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS role_hierarchy (
        id INT AUTO_INCREMENT PRIMARY KEY,
        role_name VARCHAR(100) NOT NULL UNIQUE,
        hierarchy_level INT NOT NULL DEFAULT 0,
        description VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Clear and insert hierarchy
    mysqli_query($conn, "TRUNCATE TABLE role_hierarchy");
    
    $hierarchy = [
        ['Super Admin', 1, 'Full system access. Can manage all users, settings, and data.'],
        ['Admin', 2, 'Managing Director. Manages departments, managers, employees. Cannot modify Super Admin.'],
        ['Operations Manager', 3, 'Manages Supervisors and Team Leads under assigned departments.'],
        ['WFM Executive', 3, 'Workforce Management. Manages attendance, shifts, schedules.'],
        ['Finance Manager', 3, 'Manages payroll and salary. Cannot manage users or attendance.'],
        ['Accountant', 4, 'Assists with payroll processing.'],
        ['Supervisor', 4, 'Manages Team Leads. Approves attendance adjustments and leave requests.'],
        ['Team Lead', 5, 'Manages employees in their team. Approves daily attendance.'],
        ['Employee', 6, 'Can view own profile, mark attendance, request leave, submit adjustments.'],
    ];
    
    foreach ($hierarchy as $h) {
        $role = mysqli_real_escape_string($conn, $h[0]);
        $level = intval($h[1]);
        $desc = mysqli_real_escape_string($conn, $h[2]);
        mysqli_query($conn, "INSERT INTO role_hierarchy (role_name, hierarchy_level, description) VALUES ('$role', '$level', '$desc')");
    }
    
    // ==========================================
    // 5. Set default reporting for existing employees
    // ==========================================
    // Get all employees and set default status
    mysqli_query($conn, "UPDATE employees SET status='Active' WHERE status IS NULL OR status=''");
    
    // Set is_active based on status
    mysqli_query($conn, "UPDATE employees SET is_active=1 WHERE status='Active'");
    mysqli_query($conn, "UPDATE employees SET is_active=0 WHERE status!='Active'");
    
    $message = "✅ Role setup completed successfully!";
}

// Show current hierarchy
$hierarchyRows = mysqli_query($conn, "SELECT * FROM role_hierarchy ORDER BY hierarchy_level ASC, role_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Role Setup</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-dark text-white">
            <h3>⚙️ Role Hierarchy Setup</h3>
        </div>
        <div class="card-body">
            <?php if ($message) echo "<div class='alert alert-success'>$message</div>"; ?>
            <?php if ($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
            
            <form method="POST">
                <div class="alert alert-warning">
                    <strong>⚠️ Important:</strong> This will set up the role hierarchy system. 
                    Make sure you have a database backup before running.
                </div>
                <button type="submit" name="run_setup" class="btn btn-primary btn-lg" onclick="return confirm('Run role setup? This will modify the database.')">
                    🚀 Run Role Setup
                </button>
            </form>
            
            <hr>
            
            <h5>Current Role Hierarchy</h5>
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Level</th>
                        <th>Role</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($r = mysqli_fetch_assoc($hierarchyRows)) { ?>
                    <tr>
                        <td><span class="badge bg-<?php echo $r['hierarchy_level'] <= 2 ? 'danger' : ($r['hierarchy_level'] <= 4 ? 'warning text-dark' : 'info'); ?>"><?php echo $r['hierarchy_level']; ?></span></td>
                        <td><strong><?php echo $r['role_name']; ?></strong></td>
                        <td><?php echo $r['description']; ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
            
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
</div>
</body>
</html>