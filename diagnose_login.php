<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include("config/db.php");

$results = [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Diagnostic Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h2><i class="fas fa-stethoscope"></i> Login System Diagnostic</h2>
            </div>
            <div class="card-body">
                
                <?php
                // Test 1: Database Connection
                echo "<h4>Test 1: Database Connection</h4>";
                if ($conn) {
                    echo "<div class='alert alert-success'><i class='fas fa-check'></i> Database connected successfully</div>";
                    $results[] = true;
                } else {
                    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> Database connection failed: " . mysqli_connect_error() . "</div>";
                    $results[] = false;
                }
                
                // Test 2: Check if admin table exists
                echo "<h4>Test 2: Admin Table Check</h4>";
                $admin_table = mysqli_query($conn, "SHOW TABLES LIKE 'admin'");
                if (mysqli_num_rows($admin_table) > 0) {
                    echo "<div class='alert alert-success'><i class='fas fa-check'></i> Admin table exists</div>";
                    $results[] = true;
                    
                    // Check admin users
                    $admin_count = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM admin");
                    $count = mysqli_fetch_assoc($admin_count);
                    echo "<div class='alert alert-info'>Total admin users: <strong>{$count['cnt']}</strong></div>";
                    
                    // Show admin users (without passwords)
                    $admins = mysqli_query($conn, "SELECT id, name, email, role FROM admin");
                    if (mysqli_num_rows($admins) > 0) {
                        echo "<div class='alert alert-secondary'><strong>Admin Users:</strong><ul>";
                        while ($admin = mysqli_fetch_assoc($admins)) {
                            echo "<li>ID: {$admin['id']} - Name: {$admin['name']} - Email: {$admin['email']} - Role: {$admin['role']}</li>";
                        }
                        echo "</ul></div>";
                    }
                } else {
                    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> Admin table NOT FOUND</div>";
                    $results[] = false;
                }
                
                // Test 3: Check if employees table exists
                echo "<h4>Test 3: Employees Table Check</h4>";
                $emp_table = mysqli_query($conn, "SHOW TABLES LIKE 'employees'");
                if (mysqli_num_rows($emp_table) > 0) {
                    echo "<div class='alert alert-success'><i class='fas fa-check'></i> Employees table exists</div>";
                    $results[] = true;
                    
                    // Check employee users
                    $emp_count = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM employees");
                    $count = mysqli_fetch_assoc($emp_count);
                    echo "<div class='alert alert-info'>Total employees: <strong>{$count['cnt']}</strong></div>";
                    
                    // Show first 5 employees
                    $employees = mysqli_query($conn, "SELECT id, full_name, email, role FROM employees LIMIT 5");
                    if (mysqli_num_rows($employees) > 0) {
                        echo "<div class='alert alert-secondary'><strong>Sample Employees:</strong><ul>";
                        while ($emp = mysqli_fetch_assoc($employees)) {
                            echo "<li>ID: {$emp['id']} - Name: {$emp['full_name']} - Email: {$emp['email']} - Role: {$emp['role']}</li>";
                        }
                        echo "</ul></div>";
                    }
                } else {
                    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> Employees table NOT FOUND</div>";
                    $results[] = false;
                }
                
                // Test 4: Test login with default credentials
                echo "<h4>Test 4: Test Admin Login</h4>";
                $test_email = 'admin@ems.com';
                $test_password = 'admin123';
                
                $adminQuery = mysqli_query($conn, "SELECT * FROM admin WHERE email='$test_email' LIMIT 1");
                if (mysqli_num_rows($adminQuery) > 0) {
                    $admin = mysqli_fetch_assoc($adminQuery);
                    echo "<div class='alert alert-success'><i class='fas fa-check'></i> Admin found: {$admin['name']}</div>";
                    
                    $verify = password_verify($test_password, $admin['password']);
                    $direct = ($test_password == $admin['password']);
                    
                    echo "<div class='alert alert-info'>";
                    echo "<strong>Password hash length:</strong> " . strlen($admin['password']) . "<br>";
                    echo "<strong>password_verify() result:</strong> " . ($verify ? "TRUE ✓" : "FALSE ✗") . "<br>";
                    echo "<strong>Direct comparison:</strong> " . ($direct ? "TRUE ✓" : "FALSE ✗") . "<br>";
                    echo "</div>";
                    
                    if ($verify || $direct) {
                        echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i> <strong>LOGIN WOULD SUCCEED!</strong></div>";
                        $results[] = true;
                    } else {
                        echo "<div class='alert alert-danger'><i class='fas fa-times-circle'></i> <strong>LOGIN WOULD FAIL - Password mismatch</strong></div>";
                        $results[] = false;
                    }
                } else {
                    echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> No admin found with email: $test_email</div>";
                    $results[] = false;
                }
                
                // Summary
                echo "<h4>Summary</h4>";
                $passed = array_sum($results);
                $total = count($results);
                echo "<div class='alert alert-" . ($passed == $total ? "success" : "danger") . "'>";
                echo "<strong>Tests Passed:</strong> $passed / $total<br>";
                if ($passed == $total) {
                    echo "<i class='fas fa-check-circle'></i> All tests passed! Login should work.";
                } else {
                    echo "<i class='fas fa-exclamation-circle'></i> Some tests failed. Check the details above.";
                }
                echo "</div>";
                
                mysqli_close($conn);
                ?>
                
                <hr>
                <div class="mt-4">
                    <a href="login.php" class="btn btn-primary btn-lg">Go to Login Page</a>
                    <a href="login_debug.php" class="btn btn-secondary btn-lg">Login Debug Tool</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
</body>
</html>