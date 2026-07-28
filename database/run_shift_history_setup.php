<?php
// Script to add shift_history table to the database
// Usage: Navigate to http://localhost/EMS/database/run_shift_history_setup.php

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Shift History Table</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-database"></i> Add Shift History Table</h3>
            </div>
            <div class="card-body">
                <?php
                include('../config/db.php');
                
                echo "<div class='alert alert-info'><strong>Database:</strong> " . htmlspecialchars($database) . "</div>";
                
                // Check if table already exists
                $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'shift_history'");
                
                if (mysqli_num_rows($check_table) > 0) {
                    echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> <strong>Table 'shift_history' already exists!</strong></div>";
                    echo "<a href='../admin/manage_shifts.php' class='btn btn-primary'>Go to Manage Shifts</a>";
                    exit();
                }
                
                // Create the shift_history table
                $sql = "CREATE TABLE IF NOT EXISTS shift_history (
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
                    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
                    FOREIGN KEY (changed_by) REFERENCES admin(id) ON DELETE CASCADE
                )";
                
                if (mysqli_query($conn, $sql)) {
                    echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i> <strong>Success!</strong> Table 'shift_history' created successfully.</div>";
                    
                    // Add indexes
                    mysqli_query($conn, "CREATE INDEX idx_shift_history_employee ON shift_history(employee_id)");
                    mysqli_query($conn, "CREATE INDEX idx_shift_history_changed_at ON shift_history(changed_at DESC)");
                    
                    echo "<div class='alert alert-success'><i class='fas fa-check'></i> Indexes created successfully.</div>";
                    
                    echo "<hr>";
                    echo "<h4>Next Steps:</h4>";
                    echo "<ol>";
                    echo "<li>You can now use the <a href='../admin/manage_shifts.php'>Manage Shifts</a> page</li>";
                    echo "<li>Shift changes will be tracked in the history</li>";
                    echo "<li>You can view and delete history records as needed</li>";
                    echo "</ol>";
                    
                    echo "<a href='../admin/manage_shifts.php' class='btn btn-primary btn-lg'>Go to Manage Shifts</a>";
                } else {
                    echo "<div class='alert alert-danger'><i class='fas fa-times-circle'></i> <strong>Error!</strong> " . mysqli_error($conn) . "</div>";
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