<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Diagnostic and Fix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-warning">
                <h3><i class="fas fa-stethoscope"></i> Database Diagnostic</h3>
            </div>
            <div class="card-body">
                <?php
                include('../config/db.php');
                
                echo "<div class='alert alert-info'><strong>Database:</strong> " . htmlspecialchars($database) . " | <strong>Host:</strong> " . htmlspecialchars($host) . "</div>";
                
                // Test 1: Check connection
                echo "<h4>1. Database Connection Test</h4>";
                if ($conn) {
                    echo "<div class='alert alert-success'>✓ Database connection successful</div>";
                } else {
                    echo "<div class='alert alert-danger'>✗ Database connection failed: " . mysqli_connect_error() . "</div>";
                    exit();
                }
                
                // Test 2: Check if employees table exists
                echo "<h4>2. Check Dependencies</h4>";
                $emp_check = mysqli_query($conn, "SHOW TABLES LIKE 'employees'");
                if (mysqli_num_rows($emp_check) > 0) {
                    echo "<div class='alert alert-success'>✓ employees table exists</div>";
                } else {
                    echo "<div class='alert alert-danger'>✗ employees table does NOT exist (required for foreign key)</div>";
                }
                
                // Test 3: Try to create salary_components table
                echo "<h4>3. Creating salary_components Table</h4>";
                $sql1 = "CREATE TABLE IF NOT EXISTS salary_components (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    component_name VARCHAR(100) NOT NULL,
                    component_type ENUM('Allowance', 'Deduction') NOT NULL,
                    status ENUM('Active', 'Inactive') DEFAULT 'Active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                
                if (mysqli_query($conn, $sql1)) {
                    echo "<div class='alert alert-success'>✓ salary_components table created/verified</div>";
                } else {
                    echo "<div class='alert alert-danger'>✗ Error: " . mysqli_error($conn) . "</div>";
                }
                
                // Test 4: Try to create salary_structure table
                echo "<h4>4. Creating salary_structure Table</h4>";
                $sql2 = "CREATE TABLE IF NOT EXISTS salary_structure (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    employee_id INT NOT NULL,
                    basic_salary DECIMAL(10,2) DEFAULT 0.00,
                    house_allowance DECIMAL(10,2) DEFAULT 0.00,
                    medical_allowance DECIMAL(10,2) DEFAULT 0.00,
                    transport_allowance DECIMAL(10,2) DEFAULT 0.00,
                    other_allowance DECIMAL(10,2) DEFAULT 0.00,
                    tax_deduction DECIMAL(10,2) DEFAULT 0.00,
                    other_deduction DECIMAL(10,2) DEFAULT 0.00,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_employee_id (employee_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                
                if (mysqli_query($conn, $sql2)) {
                    echo "<div class='alert alert-success'>✓ salary_structure table created/verified</div>";
                } else {
                    echo "<div class='alert alert-danger'>✗ Error: " . mysqli_error($conn) . "</div>";
                }
                
                // Test 5: Try to create salary_structure_components table
                echo "<h4>5. Creating salary_structure_components Table</h4>";
                $sql3 = "CREATE TABLE IF NOT EXISTS salary_structure_components (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    salary_structure_id INT NOT NULL,
                    component_id INT NOT NULL,
                    amount DECIMAL(10,2) DEFAULT 0.00,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_salary_structure_id (salary_structure_id),
                    INDEX idx_component_id (component_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                
                if (mysqli_query($conn, $sql3)) {
                    echo "<div class='alert alert-success'>✓ salary_structure_components table created/verified</div>";
                } else {
                    echo "<div class='alert alert-danger'>✗ Error: " . mysqli_error($conn) . "</div>";
                }
                
                // Test 6: Insert default components
                echo "<h4>6. Inserting Default Salary Components</h4>";
                $insert = "INSERT INTO salary_components (component_name, component_type, status) VALUES
                    ('House Allowance', 'Allowance', 'Active'),
                    ('Medical Allowance', 'Allowance', 'Active'),
                    ('Transport Allowance', 'Allowance', 'Active'),
                    ('Overtime', 'Allowance', 'Active'),
                    ('Bonus', 'Allowance', 'Active'),
                    ('Tax', 'Deduction', 'Active'),
                    ('Provident Fund', 'Deduction', 'Active'),
                    ('Insurance', 'Deduction', 'Active')
                    ON DUPLICATE KEY UPDATE component_name=component_name";
                
                if (mysqli_query($conn, $insert)) {
                    echo "<div class='alert alert-success'>✓ Default components inserted</div>";
                } else {
                    echo "<div class='alert alert-warning'>⚠ Could not insert defaults (may already exist): " . mysqli_error($conn) . "</div>";
                }
                
                // Final verification
                echo "<h4>7. Final Verification</h4>";
                $tables = ['salary_components', 'salary_structure', 'salary_structure_components'];
                $all_exist = true;
                
                foreach ($tables as $table) {
                    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
                    if (mysqli_num_rows($result) > 0) {
                        echo "<div class='alert alert-success'>✓ $table exists</div>";
                    } else {
                        echo "<div class='alert alert-danger'>✗ $table does NOT exist</div>";
                        $all_exist = false;
                    }
                }
                
                if ($all_exist) {
                    echo "<div class='alert alert-success'><h4><i class='fas fa-check-circle'></i> All tables created successfully!</h4></div>";
                    echo "<a href='../admin/payroll_dashboard.php' class='btn btn-primary btn-lg'>Go to Payroll Dashboard</a>";
                } else {
                    echo "<div class='alert alert-danger'><h4>Some tables could not be created. Please check the errors above.</h4></div>";
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