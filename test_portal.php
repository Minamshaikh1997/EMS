<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== EMS Portal Diagnostic Test ===\n\n";

// Test 1: Database Connection
echo "1. Testing Database Connection...\n";
include("config/db.php");
echo "   ✓ Database connected successfully\n\n";

// Test 2: Check required tables
echo "2. Checking Required Tables...\n";
$required_tables = ['admin', 'employees', 'departments', 'leave_requests', 'attendance', 'notices', 'holidays', 'roles', 'permissions', 'role_permissions', 'permission_logs'];
$missing_tables = [];

foreach ($required_tables as $table) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($result) > 0) {
        echo "   ✓ $table exists\n";
    } else {
        echo "   ✗ $table MISSING\n";
        $missing_tables[] = $table;
    }
}
echo "\n";

// Test 3: Check admin users
echo "3. Checking Admin Users...\n";
$result = mysqli_query($conn, "SELECT id, name, email, role FROM admin");
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "   ✓ Admin: {$row['name']} ({$row['email']}) - Role: {$row['role']}\n";
    }
} else {
    echo "   ✗ No admin users found\n";
}
echo "\n";

// Test 4: Check employees
echo "4. Checking Employees...\n";
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM employees");
$row = mysqli_fetch_assoc($result);
echo "   ✓ Total employees: {$row['total']}\n\n";

// Test 5: Check roles
echo "5. Checking Roles...\n";
$result = mysqli_query($conn, "SELECT role_name, hierarchy_level FROM roles ORDER BY hierarchy_level");
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "   ✓ {$row['role_name']} (Level {$row['hierarchy_level']})\n";
    }
} else {
    echo "   ✗ No roles found\n";
}
echo "\n";

// Test 6: Summary
echo "=== Summary ===\n";
if (empty($missing_tables)) {
    echo "✓ All required tables exist\n";
    echo "✓ Portal should be working\n";
    echo "\nNext steps:\n";
    echo "1. Open http://localhost/EMS/login.php in your browser\n";
    echo "2. Login with your admin credentials\n";
    echo "3. If you encounter any issues, check the error messages\n";
} else {
    echo "✗ Missing tables: " . implode(', ', $missing_tables) . "\n";
    echo "Please run the database installation scripts\n";
}

mysqli_close($conn);
?>