<?php
// Direct database fix script for shift_history table
// This will create the table directly and show detailed error messages

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Shift History Table</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3><i class="fas fa-wrench"></i> Database Fix - Shift History Table</h3>
            </div>
            <div class="card-body">
                <?php
                echo "<div class='alert alert-info'><strong>Starting database fix...</strong></div>";
                
                // Step 1: Connect to database
                echo "<h5>Step 1: Connecting to database...</h5>";
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
                
                // Step 2: Check if table exists
                echo "<h5>Step 2: Checking if shift_history table exists...</h5>";
                $result = mysqli_query($conn, "SHOW TABLES LIKE 'shift_history'");
                
                if (!$result) {
                    echo "<div class='alert alert-danger'><strong>Query Error:</strong> " . mysqli_error($conn) . "</div>";
                }
                
                if (mysqli_num_rows($result) > 0) {
                    echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> Table 'shift_history' already exists!</div>";
                    echo "<p>If you're still getting an error, try:</p>";
                    echo "<ol>";
                    echo "<li>Clear your browser cache</li>";
                    echo "<li>Restart XAMPP Apache and MySQL services</li>";
                    echo "<li>Check if you're using the correct database</li>";
                    echo "</ol>";
                    echo "<a href='../admin/manage_shifts.php' class='btn btn-primary'>Go to Manage Shifts</a>";
                    exit();
                }
                
                echo "<div class='alert alert-info'><i class='fas fa-info-circle'></i> Table does not exist. Creating it now...</div>";
                
                // Step 3: Create the table
                echo "<h5>Step 3: Creating shift_history table...</h5>";
                
                $create_table_sql = "CREATE TABLE shift_history (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    employee_id INT NOT NULL,
                    old_shift_name VARCHAR(50) NOT NULL,
                    old_shift_start TIME NOT NULL,
                    old_shift_end TIME NOT NULL,
                    new_shift_name VARCHAR(50) NOT NULL,
                    new_shift_start TIME NOT NULL,
                    new_shift_end TIME NOT NULL,
                    effective_date DATE NOT NULL,
                    changed_by INT NOT NULL,
                    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                
                echo "<div class='alert alert-secondary'><strong>SQL Query:</strong><pre>" . htmlspecialchars($create_table_sql) . "</pre></div>";
                
                if (mysqli_query($conn, $create_table_sql)) {
                    echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i> <strong>SUCCESS!</strong> Table 'shift_history' created successfully!</div>";
                    
                    // Step 4: Add indexes
                    echo "<h5>Step 4: Adding indexes...</h5>";
                    
                    $index1 = "CREATE INDEX idx_shift_history_employee ON shift_history(employee_id)";
                    $index2 = "CREATE INDEX idx_shift_history_changed_at ON shift_history(changed_at DESC)";
                    
                    if (mysqli_query($conn, $index1)) {
                        echo "<div class='alert alert-success'><i class='fas fa-check'></i> Index on employee_id created</div>";
                    } else {
                        echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> Index creation warning: " . mysqli_error($conn) . "</div>";
                    }
                    
                    if (mysqli_query($conn, $index2)) {
                        echo "<div class='alert alert-success'><i class='fas fa-check'></i> Index on changed_at created</div>";
                    } else {
                        echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> Index creation warning: " . mysqli_error($conn) . "</div>";
                    }
                    
                    // Step 5: Verify table was created
                    echo "<h5>Step 5: Verifying table creation...</h5>";
                    $verify = mysqli_query($conn, "SHOW TABLES LIKE 'shift_history'");
                    
                    if (mysqli_num_rows($verify) > 0) {
                        echo "<div class='alert alert-success'><i class='fas fa-check-double'></i> <strong>VERIFIED!</strong> Table exists in database.</div>";
                        
                        // Show table structure
                        $structure = mysqli_query($conn, "DESCRIBE shift_history");
                        echo "<h6>Table Structure:</h6>";
                        echo "<table class='table table-sm table-bordered'>";
                        echo "<thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr></thead>";
                        echo "<tbody>";
                        while ($row = mysqli_fetch_assoc($structure)) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
                            echo "</tr>";
                        }
                        echo "</tbody></table>";
                    }
                    
                    echo "<hr>";
                    echo "<div class='alert alert-success'>";
                    echo "<h4><i class='fas fa-party-horn'></i> Fix Complete!</h4>";
                    echo "<p class='mb-0'>The shift_history table has been successfully created. You can now use the Manage Shifts page.</p>";
                    echo "</div>";
                    
                    echo "<a href='../admin/manage_shifts.php' class='btn btn-primary btn-lg'>Go to Manage Shifts</a>";
                    
                } else {
                    echo "<div class='alert alert-danger'><strong>ERROR creating table:</strong> " . mysqli_error($conn) . "</div>";
                    echo "<div class='alert alert-warning'>";
                    echo "<h5>Possible issues:</h5>";
                    echo "<ul>";
                    echo "<li>Foreign key constraint failed - check if 'employees' and 'admin' tables exist</li>";
                    echo "<li>Database user doesn't have CREATE privileges</li>";
                    echo "<li>Table name conflict</li>";
                    echo "</ul>";
                    echo "</div>";
                }
                
                mysqli_close($conn);
                ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
</body>
</html>