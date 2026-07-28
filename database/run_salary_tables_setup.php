<?php
// Script to create salary tables
// Usage: Navigate to http://localhost/EMS/database/run_salary_tables_setup.php

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Salary Tables</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-database"></i> Create Salary Tables</h3>
            </div>
            <div class="card-body">
                <?php
                // Use absolute path for CLI execution
                if (isset($_SERVER['HTTP_HOST'])) {
                    include('../config/db.php');
                } else {
                    include(__DIR__ . '/../config/db.php');
                }
                
                echo "<div class='alert alert-info'><strong>Database:</strong> " . htmlspecialchars($database) . "</div>";
                
                // Check if tables already exist
                $check1 = mysqli_query($conn, "SHOW TABLES LIKE 'salary_structure'");
                $check2 = mysqli_query($conn, "SHOW TABLES LIKE 'salary_structure_components'");
                $check3 = mysqli_query($conn, "SHOW TABLES LIKE 'salary_components'");
                $check4 = mysqli_query($conn, "SHOW TABLES LIKE 'salary_slips'");
                $check5 = mysqli_query($conn, "SHOW TABLES LIKE 'payroll'");
                
                if (mysqli_num_rows($check1) > 0 && mysqli_num_rows($check2) > 0 && mysqli_num_rows($check3) > 0 && mysqli_num_rows($check4) > 0 && mysqli_num_rows($check5) > 0) {
                    echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> <strong>All salary and payroll tables already exist!</strong></div>";
                    echo "<a href='../admin/payroll_dashboard.php' class='btn btn-primary'>Go to Payroll Dashboard</a>";
                    exit();
                }
                
                // Create salary_components table first (without foreign keys)
                $sql1 = "CREATE TABLE IF NOT EXISTS salary_components (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    component_name VARCHAR(100) NOT NULL,
                    component_type ENUM('Allowance', 'Deduction') NOT NULL,
                    status ENUM('Active', 'Inactive') DEFAULT 'Active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                
                if (!mysqli_query($conn, $sql1)) {
                    echo "<div class='alert alert-danger'><i class='fas fa-times-circle'></i> <strong>Error creating salary_components!</strong> " . mysqli_error($conn) . "</div>";
                    exit();
                } else {
                    echo "<div class='alert alert-success'><i class='fas fa-check'></i> <strong>salary_components</strong> table created successfully.</div>";
                }
                
                // Insert default components
                $insert_components = "INSERT INTO salary_components (component_name, component_type, status) VALUES
                    ('House Allowance', 'Allowance', 'Active'),
                    ('Medical Allowance', 'Allowance', 'Active'),
                    ('Transport Allowance', 'Allowance', 'Active'),
                    ('Overtime', 'Allowance', 'Active'),
                    ('Bonus', 'Allowance', 'Active'),
                    ('Tax', 'Deduction', 'Active'),
                    ('Provident Fund', 'Deduction', 'Active'),
                    ('Insurance', 'Deduction', 'Active')
                    ON DUPLICATE KEY UPDATE component_name=component_name";
                
                mysqli_query($conn, $insert_components);
                echo "<div class='alert alert-success'><i class='fas fa-check'></i> Default salary components added.</div>";
                
                // Create salary_structure table (without foreign key initially)
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
                
                if (!mysqli_query($conn, $sql2)) {
                    echo "<div class='alert alert-danger'><i class='fas fa-times-circle'></i> <strong>Error creating salary_structure!</strong> " . mysqli_error($conn) . "</div>";
                    exit();
                } else {
                    echo "<div class='alert alert-success'><i class='fas fa-check'></i> <strong>salary_structure</strong> table created successfully.</div>";
                }
                
                // Create salary_structure_components table (without foreign keys initially)
                $sql3 = "CREATE TABLE IF NOT EXISTS salary_structure_components (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    salary_structure_id INT NOT NULL,
                    component_id INT NOT NULL,
                    amount DECIMAL(10,2) DEFAULT 0.00,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_salary_structure_id (salary_structure_id),
                    INDEX idx_component_id (component_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                
                if (!mysqli_query($conn, $sql3)) {
                    echo "<div class='alert alert-danger'><i class='fas fa-times-circle'></i> <strong>Error creating salary_structure_components!</strong> " . mysqli_error($conn) . "</div>";
                    exit();
                } else {
                    echo "<div class='alert alert-success'><i class='fas fa-check'></i> <strong>salary_structure_components</strong> table created successfully.</div>";
                }
                
                // Create payroll table
                $sql4 = "CREATE TABLE IF NOT EXISTS payroll (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    employee_id INT NOT NULL,
                    payroll_month VARCHAR(20) NOT NULL,
                    payroll_year INT NOT NULL,
                    basic_salary DECIMAL(10,2) DEFAULT 0.00,
                    allowances DECIMAL(10,2) DEFAULT 0.00,
                    overtime DECIMAL(10,2) DEFAULT 0.00,
                    bonus DECIMAL(10,2) DEFAULT 0.00,
                    deductions DECIMAL(10,2) DEFAULT 0.00,
                    tax DECIMAL(10,2) DEFAULT 0.00,
                    net_salary DECIMAL(10,2) DEFAULT 0.00,
                    payment_status ENUM('Pending', 'Paid') DEFAULT 'Pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_employee_id (employee_id),
                    INDEX idx_payroll_month (payroll_month),
                    INDEX idx_payroll_year (payroll_year),
                    INDEX idx_payment_status (payment_status),
                    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                
                if (!mysqli_query($conn, $sql4)) {
                    echo "<div class='alert alert-danger'><i class='fas fa-times-circle'></i> <strong>Error creating payroll!</strong> " . mysqli_error($conn) . "</div>";
                    exit();
                } else {
                    echo "<div class='alert alert-success'><i class='fas fa-check'></i> <strong>payroll</strong> table created successfully.</div>";
                }
                
                // Create salary_slips table
                $sql5 = "CREATE TABLE IF NOT EXISTS salary_slips (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    employee_id INT NOT NULL,
                    salary_month VARCHAR(7) NOT NULL,
                    basic_salary DECIMAL(10,2) DEFAULT 0.00,
                    allowance DECIMAL(10,2) DEFAULT 0.00,
                    deduction DECIMAL(10,2) DEFAULT 0.00,
                    gross_salary DECIMAL(10,2) DEFAULT 0.00,
                    net_salary DECIMAL(10,2) DEFAULT 0.00,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_employee_id (employee_id),
                    INDEX idx_salary_month (salary_month),
                    UNIQUE KEY unique_salary_slip (employee_id, salary_month)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                
                if (!mysqli_query($conn, $sql5)) {
                    echo "<div class='alert alert-danger'><i class='fas fa-times-circle'></i> <strong>Error creating salary_slips!</strong> " . mysqli_error($conn) . "</div>";
                    exit();
                } else {
                    echo "<div class='alert alert-success'><i class='fas fa-check'></i> <strong>salary_slips</strong> table created successfully.</div>";
                }
                
                echo "<hr>";
                echo "<div class='alert alert-success'><h4><i class='fas fa-check-circle'></i> All tables created successfully!</h4></div>";
                echo "<h4>Next Steps:</h4>";
                echo "<ol>";
                echo "<li>You can now access the <a href='../admin/payroll_dashboard.php'>Payroll Dashboard</a></li>";
                echo "<li>Go to <a href='../admin/salary_structure.php'>Salary Structure</a> to add salary details for employees</li>";
                echo "<li>Manage salary components in <a href='../admin/salary_components.php'>Salary Components</a></li>";
                echo "<li>Generate payroll in <a href='../admin/generate_payroll.php'>Generate Payroll</a></li>";
                echo "<li>Generate salary slips in <a href='../admin/salary_slips.php'>Salary Slips</a></li>";
                echo "</ol>";
                
                echo "<a href='../admin/payroll_dashboard.php' class='btn btn-primary btn-lg'>Go to Payroll Dashboard</a>";
                
                mysqli_close($conn);
                ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
</body>
</html>
