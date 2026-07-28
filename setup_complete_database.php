<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("config/db.php");

echo "<h2>Complete Database Setup</h2>";
echo "<hr>";

// Test connection
if (!$conn) {
    die("<div class='alert alert-danger'>Database connection failed: " . mysqli_connect_error() . "</div>");
}

echo "<div class='alert alert-success'><i class='fas fa-check'></i> Database connected successfully</div>";

// Create all necessary tables
$tables = [];

// 1. Admin table
$tables['admin'] = "
CREATE TABLE IF NOT EXISTS admin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'Admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// 2. Employees table
$tables['employees'] = "
CREATE TABLE IF NOT EXISTS employees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id VARCHAR(50) DEFAULT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'Employee',
    department VARCHAR(100),
    designation VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    join_date DATE,
    joining_date DATE,
    status VARCHAR(20) DEFAULT 'Active',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// 3. Leave requests table
$tables['leave_requests'] = "
CREATE TABLE IF NOT EXISTS leave_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    leave_type VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT,
    status VARCHAR(20) DEFAULT 'Pending',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
)";

// 4. Attendance table
$tables['attendance'] = "
CREATE TABLE IF NOT EXISTS attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    check_in TIME,
    check_out TIME,
    status VARCHAR(20) DEFAULT 'Present',
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY unique_employee_date (employee_id, attendance_date)
)";

// 5. Attendance adjustments table
$tables['attendance_adjustments'] = "
CREATE TABLE IF NOT EXISTS attendance_adjustments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    adjustment_type VARCHAR(50) NOT NULL,
    reason TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'Pending',
    approved_by INT DEFAULT NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
)";

// 6. Notices table
$tables['notices'] = "
CREATE TABLE IF NOT EXISTS notices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    notice TEXT NOT NULL,
    priority VARCHAR(20) DEFAULT 'Normal',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

// 7. Holidays table
$tables['holidays'] = "
CREATE TABLE IF NOT EXISTS holidays (
    id INT PRIMARY KEY AUTO_INCREMENT,
    holiday_name VARCHAR(255) NOT NULL,
    holiday_date DATE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// 8. Roles table (for role permissions)
$tables['roles'] = "
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(100) NOT NULL UNIQUE,
    role_slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    hierarchy_level INT NOT NULL DEFAULT 6,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// 9. Permissions table
$tables['permissions'] = "
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    permission_name VARCHAR(100) NOT NULL UNIQUE,
    permission_slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// 10. Role permissions table
$tables['role_permissions'] = "
CREATE TABLE IF NOT EXISTS role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    module_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_role_permission_module (role_id, permission_id, module_name),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// 11. Permission logs table
$tables['permission_logs'] = "
CREATE TABLE IF NOT EXISTS permission_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT DEFAULT 0,
    admin_email VARCHAR(255) DEFAULT '',
    role VARCHAR(100) DEFAULT 'Unknown',
    module VARCHAR(100) NOT NULL,
    permission VARCHAR(100) NOT NULL,
    granted TINYINT(1) DEFAULT 1,
    ip_address VARCHAR(45) DEFAULT '',
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Create all tables
echo "<h4>Creating Database Tables:</h4>";
foreach ($tables as $table_name => $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "<div class='alert alert-success'>✅ Table '$table_name' created/verified</div>";
    } else {
        echo "<div class='alert alert-danger'>❌ Error creating '$table_name': " . mysqli_error($conn) . "</div>";
    }
}

// Add employee rights columns
echo "<h4>Adding Employee Rights Columns:</h4>";
$rights_columns = [
    'can_view_payroll' => 'TINYINT(1) DEFAULT 1',
    'can_apply_leave' => 'TINYINT(1) DEFAULT 1',
    'can_view_attendance' => 'TINYINT(1) DEFAULT 1',
    'can_submit_adjustment' => 'TINYINT(1) DEFAULT 1',
    'can_edit_profile' => 'TINYINT(1) DEFAULT 1',
    'can_view_reports' => 'TINYINT(1) DEFAULT 1',
    'can_change_password' => 'TINYINT(1) DEFAULT 1'
];

foreach ($rights_columns as $column => $definition) {
    $sql = "ALTER TABLE employees ADD COLUMN IF NOT EXISTS $column $definition";
    if (mysqli_query($conn, $sql)) {
        echo "<div class='alert alert-success'>✅ Column '$column' added</div>";
    } else {
        $error = mysqli_error($conn);
        if (strpos($error, 'Duplicate column name') !== false) {
            echo "<div class='alert alert-info'>ℹ️ Column '$column' already exists</div>";
        } else {
            echo "<div class='alert alert-danger'>❌ Error adding '$column': $error</div>";
        }
    }
}

// Insert default admin
echo "<h4>Creating Default Users:</h4>";
$check_admin = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM admin WHERE email='admin@ems.com'");
$admin_exists = mysqli_fetch_assoc($check_admin);

if ($admin_exists['cnt'] == 0) {
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $insert_admin = "INSERT INTO admin (name, email, password, role) VALUES ('System Administrator', 'admin@ems.com', '$admin_password', 'Super Admin')";
    
    if (mysqli_query($conn, $insert_admin)) {
        echo "<div class='alert alert-success'>✅ Default admin created:<br>";
        echo "<strong>Email:</strong> admin@ems.com<br>";
        echo "<strong>Password:</strong> admin123<br>";
        echo "<strong>Role:</strong> Super Admin</div>";
    } else {
        echo "<div class='alert alert-danger'>❌ Error creating admin: " . mysqli_error($conn) . "</div>";
    }
} else {
    echo "<div class='alert alert-info'>ℹ️ Admin user already exists</div>";
}

// Insert sample employee
$check_employee = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM employees WHERE email='employee@ems.com'");
$employee_exists = mysqli_fetch_assoc($check_employee);

if ($employee_exists['cnt'] == 0) {
    $employee_password = password_hash('employee123', PASSWORD_DEFAULT);
    $insert_employee = "INSERT INTO employees (full_name, email, password, role, department, designation, phone, join_date, joining_date, status) 
                       VALUES ('John Doe', 'employee@ems.com', '$employee_password', 'Employee', 'IT', 'Software Developer', '+92-300-1234567', '2024-01-01', '2024-01-01', 'Active')";
    
    if (mysqli_query($conn, $insert_employee)) {
        echo "<div class='alert alert-success'>✅ Sample employee created:<br>";
        echo "<strong>Email:</strong> employee@ems.com<br>";
        echo "<strong>Password:</strong> employee123<br>";
        echo "<strong>Role:</strong> Employee</div>";
    } else {
        echo "<div class='alert alert-danger'>❌ Error creating employee: " . mysqli_error($conn) . "</div>";
    }
} else {
    echo "<div class='alert alert-info'>ℹ️ Sample employee already exists</div>";
}

// Insert sample notices
$check_notices = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notices");
$notices_count = mysqli_fetch_assoc($check_notices);

if ($notices_count['cnt'] == 0) {
    $notices = [
        ['Welcome to EMS', 'Welcome to the Employee Management System. Please login to access your dashboard.', 'High'],
        ['System Update', 'The system has been updated with new features. Check out the dashboard.', 'Normal'],
        ['Holiday Notice', 'Office will be closed on upcoming holidays. Check the holidays section.', 'Normal']
    ];
    
    foreach ($notices as $notice) {
        $sql = "INSERT INTO notices (title, notice, priority) VALUES ('{$notice[0]}', '{$notice[1]}', '{$notice[2]}')";
        mysqli_query($conn, $sql);
    }
    echo "<div class='alert alert-success'>✅ Sample notices created</div>";
}

// Insert sample holidays
$check_holidays = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM holidays");
$holidays_count = mysqli_fetch_assoc($check_holidays);

if ($holidays_count['cnt'] == 0) {
    $holidays = [
        ['Independence Day', '2026-08-14', 'Pakistan Independence Day'],
        ['Defense Day', '2026-09-06', 'Pakistan Defense Day'],
        ['Eid ul Fitr', '2026-03-30', 'Eid ul Fitr Holiday'],
        ['Eid ul Adha', '2026-06-06', 'Eid ul Adha Holiday']
    ];
    
    foreach ($holidays as $holiday) {
        $sql = "INSERT INTO holidays (holiday_name, holiday_date, description) VALUES ('{$holiday[0]}', '{$holiday[1]}', '{$holiday[2]}')";
        mysqli_query($conn, $sql);
    }
    echo "<div class='alert alert-success'>✅ Sample holidays created</div>";
}

// Summary
echo "<hr><h3>Setup Summary:</h3>";
$admin_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM admin"));
$emp_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM employees"));

echo "<div class='alert alert-info'>";
echo "<strong>Total Admins:</strong> " . $admin_count['cnt'] . "<br>";
echo "<strong>Total Employees:</strong> " . $emp_count['cnt'] . "<br><br>";
echo "<strong>Default Login Credentials:</strong><br>";
echo "<strong>Admin:</strong> admin@ems.com / admin123<br>";
echo "<strong>Employee:</strong> employee@ems.com / employee123";
echo "</div>";

echo "<hr><h3>Next Steps:</h3>";
echo "<ol>";
echo "<li><a href='login.php' class='btn btn-primary btn-lg'>Go to Login Page</a></li>";
echo "<li><a href='diagnose_login.php' class='btn btn-secondary btn-lg'>Run Diagnostic</a></li>";
echo "</ol>";

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Database Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 30px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #2563eb; }
        h3 { color: #1e293b; margin-top: 20px; }
        h4 { color: #334155; margin-top: 15px; }
        .alert { margin: 10px 0; }
        hr { margin: 20px 0; }
        ol { line-height: 2; }
        a.btn { margin-right: 10px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <?php echo $output ?? ''; ?>
    </div>
</body>
</html>