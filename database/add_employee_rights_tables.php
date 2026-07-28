<?php
/**
 * Add employee rights/permissions system
 * Creates tables for managing individual employee access to features
 */

include_once '../config/db.php';

echo "<h2>Employee Rights Management System</h2>\n";

// Create employee_rights table
$sql1 = "CREATE TABLE IF NOT EXISTS employee_rights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    feature_name VARCHAR(100) NOT NULL,
    is_enabled TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY unique_employee_feature (employee_id, feature_name)
)";

if (mysqli_query($conn, $sql1)) {
    echo "<p style='color: green;'>✅ Created employee_rights table</p>\n";
} else {
    echo "<p style='color: red;'>❌ Error creating table: " . mysqli_error($conn) . "</p>\n";
}

// Insert default rights for all existing employees
$employees = mysqli_query($conn, "SELECT id FROM employees");

$features = [
    'can_view_payroll',
    'can_apply_leave',
    'can_view_attendance',
    'can_submit_adjustment',
    'can_edit_profile',
    'can_view_reports',
    'can_change_password'
];

$inserted = 0;
if ($employees) {
    while ($emp = mysqli_fetch_assoc($employees)) {
        foreach ($features as $feature) {
            $check = mysqli_query($conn, "SELECT id FROM employee_rights WHERE employee_id='{$emp['id']}' AND feature_name='$feature'");
            if (mysqli_num_rows($check) == 0) {
                mysqli_query($conn, "INSERT INTO employee_rights (employee_id, feature_name, is_enabled) VALUES ('{$emp['id']}', '$feature', 1)");
                $inserted++;
            }
        }
    }
}

echo "<p style='color: green;'>✅ Inserted $inserted default rights for all employees</p>\n";
echo "<p><strong>Available Features:</strong></p><ul>";
foreach ($features as $feature) {
    echo "<li>" . str_replace('_', ' ', ucfirst($feature)) . "</li>";
}
echo "</ul>";

mysqli_close($conn);
?>