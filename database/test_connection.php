<?php
// Test database connection and verify shift_history table
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Connection Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h3><i class="fas fa-database"></i> Database Connection Test</h3>
            </div>
            <div class="card-body">
                <?php
                echo "<h5>Test 1: Direct MySQLi Connection</h5>";
                $host = "localhost";
                $user = "root";
                $password = "";
                $database = "employee_leave_system";
                
                $conn = mysqli_connect($host, $user, $password, $database);
                
                if (!$conn) {
                    echo "<div class='alert alert-danger'><strong>Connection Failed:</strong> " . mysqli_connect_error() . "</div>";
                    exit();
                }
                
                echo "<div class='alert alert-success'><i class='fas fa-check'></i> Connected to database: <strong>$database</strong></div>";
                
                // Test 2: Check if shift_history table exists
                echo "<h5>Test 2: Check shift_history table</h5>";
                $result = mysqli_query($conn, "SHOW TABLES LIKE 'shift_history'");
                
                if (!$result) {
                    echo "<div class='alert alert-danger'><strong>Query Error:</strong> " . mysqli_error($conn) . "</div>";
                } elseif (mysqli_num_rows($result) > 0) {
                    echo "<div class='alert alert-success'><i class='fas fa-check'></i> Table 'shift_history' EXISTS</div>";
                } else {
                    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> Table 'shift_history' NOT FOUND</div>";
                }
                
                // Test 3: Try the exact query from manage_shifts.php
                echo "<h5>Test 3: Test query from manage_shifts.php (line 181)</h5>";
                $emp_id = 1; // Test with employee ID 1
                
                $query = "SELECT sh.*, a.name AS changed_by_name
                          FROM shift_history sh
                          LEFT JOIN admin a ON sh.changed_by = a.id
                          WHERE sh.employee_id = '$emp_id'
                          ORDER BY sh.id DESC LIMIT 10";
                
                echo "<div class='alert alert-secondary'><strong>Query:</strong><pre>" . htmlspecialchars($query) . "</pre></div>";
                
                $emp_history = mysqli_query($conn, $query);
                
                if (!$emp_history) {
                    echo "<div class='alert alert-danger'><strong>Query Failed:</strong> " . mysqli_error($conn) . "</div>";
                } else {
                    echo "<div class='alert alert-success'><i class='fas fa-check'></i> Query executed successfully!</div>";
                    echo "<p>Rows returned: " . mysqli_num_rows($emp_history) . "</p>";
                }
                
                // Test 4: List all tables
                echo "<h5>Test 4: All tables in database</h5>";
                $tables = mysqli_query($conn, "SHOW TABLES");
                echo "<div class='alert alert-info'><strong>Tables in $database:</strong><ul>";
                while ($table = mysqli_fetch_row($tables)) {
                    echo "<li>" . htmlspecialchars($table[0]) . "</li>";
                }
                echo "</ul></div>";
                
                // Test 5: Check employees table
                echo "<h5>Test 5: Check employees table</h5>";
                $emp_check = mysqli_query($conn, "SELECT COUNT(*) as count FROM employees");
                if ($emp_check) {
                    $count = mysqli_fetch_assoc($emp_check);
                    echo "<div class='alert alert-success'><i class='fas fa-check'></i> Employees table exists with <strong>" . $count['count'] . "</strong> employees</div>";
                } else {
                    echo "<div class='alert alert-danger'><strong>Error:</strong> " . mysqli_error($conn) . "</div>";
                }
                
                // Test 6: Check admin table
                echo "<h5>Test 6: Check admin table</h5>";
                $admin_check = mysqli_query($conn, "SELECT COUNT(*) as count FROM admin");
                if ($admin_check) {
                    $count = mysqli_fetch_assoc($admin_check);
                    echo "<div class='alert alert-success'><i class='fas fa-check'></i> Admin table exists with <strong>" . $count['count'] . "</strong> admins</div>";
                } else {
                    echo "<div class='alert alert-danger'><strong>Error:</strong> " . mysqli_error($conn) . "</div>";
                }
                
                mysqli_close($conn);
                ?>
                
                <hr>
                <div class="alert alert-warning">
                    <h5>If you're still getting an error on manage_shifts.php:</h5>
                    <ol>
                        <li>Clear your browser cache (Ctrl+Shift+Delete)</li>
                        <li>Restart XAMPP Apache service</li>
                        <li>Restart XAMPP MySQL service</li>
                        <li>Try accessing manage_shifts.php in an incognito/private window</li>
                    </ol>
                </div>
                
                <a href="../admin/manage_shifts.php" class="btn btn-primary btn-lg">Go to Manage Shifts</a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
</body>
</html>