<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("config/db.php");

echo "<h2>Database Setup & Initialization</h2>";
echo "<hr>";

// Test connection
if (!$conn) {
    die("<div class='alert alert-danger'>Database connection failed: " . mysqli_connect_error() . "</div>");
}

echo "<div class='alert alert-success'><i class='fas fa-check'></i> Database connected successfully</div>";

// Create admin table if not exists
$create_admin_table = "
CREATE TABLE IF NOT EXISTS admin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'Admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $create_admin_table)) {
    echo "<div class='alert alert-success'>✅ Admin table created/verified</div>";
} else {
    echo "<div class='alert alert-danger'>❌ Error creating admin table: " . mysqli_error($conn) . "</div>";
}

// Create employees table if not exists
$create_employees_table = "
CREATE TABLE IF NOT EXISTS employees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'Employee',
    department VARCHAR(100),
    designation VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    join_date DATE,
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $create_employees_table)) {
    echo "<div class='alert alert-success'>✅ Employees table created/verified</div>";
} else {
    echo "<div class='alert alert-danger'>❌ Error creating employees table: " . mysqli_error($conn) . "</div>";
}

// Check if admin exists
$check_admin = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM admin WHERE email='admin@ems.com'");
$admin_exists = mysqli_fetch_assoc($check_admin);

if ($admin_exists['cnt'] == 0) {
    // Create default admin
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
    echo "<div class='alert alert-info'>ℹ️ Admin user already exists (admin@ems.com)</div>";
}

// Check if sample employee exists
$check_employee = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM employees WHERE email='employee@ems.com'");
$employee_exists = mysqli_fetch_assoc($check_employee);

if ($employee_exists['cnt'] == 0) {
    // Create sample employee
    $employee_password = password_hash('employee123', PASSWORD_DEFAULT);
    $insert_employee = "INSERT INTO employees (full_name, email, password, role, department, designation, phone, join_date, status) 
                       VALUES ('John Doe', 'employee@ems.com', '$employee_password', 'Employee', 'IT', 'Software Developer', '+92-300-1234567', '2024-01-01', 1)";
    
    if (mysqli_query($conn, $insert_employee)) {
        echo "<div class='alert alert-success'>✅ Sample employee created:<br>";
        echo "<strong>Email:</strong> employee@ems.com<br>";
        echo "<strong>Password:</strong> employee123<br>";
        echo "<strong>Role:</strong> Employee</div>";
    } else {
        echo "<div class='alert alert-danger'>❌ Error creating employee: " . mysqli_error($conn) . "</div>";
    }
} else {
    echo "<div class='alert alert-info'>ℹ️ Sample employee already exists (employee@ems.com)</div>";
}

// Add employee rights columns if they don't exist
$rights_columns = [
    'can_view_payroll' => 'TINYINT(1) DEFAULT 1',
    'can_apply_leave' => 'TINYINT(1) DEFAULT 1',
    'can_view_attendance' => 'TINYINT(1) DEFAULT 1',
    'can_submit_adjustment' => 'TINYINT(1) DEFAULT 1',
    'can_edit_profile' => 'TINYINT(1) DEFAULT 1',
    'can_view_reports' => 'TINYINT(1) DEFAULT 1',
    'can_change_password' => 'TINYINT(1) DEFAULT 1'
];

echo "<h4>Employee Rights Columns:</h4>";
foreach ($rights_columns as $column => $definition) {
    $sql = "ALTER TABLE employees ADD COLUMN IF NOT EXISTS $column $definition";
    if (mysqli_query($conn, $sql)) {
        echo "<div class='alert alert-success'>✅ Column '$column' added/verified</div>";
    } else {
        $error = mysqli_error($conn);
        if (strpos($error, 'Duplicate column name') !== false) {
            echo "<div class='alert alert-info'>ℹ️ Column '$column' already exists</div>";
        } else {
            echo "<div class='alert alert-danger'>❌ Error adding '$column': $error</div>";
        }
    }
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
echo "<li><a href='login.php' class='btn btn-primary'>Go to Login Page</a></li>";
echo "<li><a href='diagnose_login.php' class='btn btn-secondary'>Run Diagnostic</a></li>";
echo "<li><a href='admin/dashboard.php' class='btn btn-success'>Admin Dashboard (after login)</a></li>";
echo "<li><a href='employee/dashboard.php' class='btn btn-info'>Employee Dashboard (after login)</a></li>";
echo "</ol>";

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 30px auto; padding: 20px; background: #f5f5f5; }
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