<?php
// Command-line script to seed departments without requiring admin login
include('config/db.php');

$departments = [
    'CEO / Managing Director',
    'HR (Human Resources)',
    'Operations',
    'Finance & Accounts',
    'Sales',
    'Marketing',
    'Customer Support',
    'IT Department',
    'Administration (Admin)',
    'Legal & Compliance',
    'Business Intelligence (MIS/BI)',
    'Quality Assurance (QA)',
    'Training',
    'Workforce Management (WFM)',
    'MIS'
];

mysqli_query($conn, "START TRANSACTION");

// Ensure departments table exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(255) NOT NULL
)");

// Clear existing
mysqli_query($conn, "DELETE FROM departments");

$stmt = mysqli_prepare($conn, "INSERT INTO departments (department_name) VALUES (?)");
foreach ($departments as $d) {
    mysqli_stmt_bind_param($stmt, 's', $d);
    mysqli_stmt_execute($stmt);
}
mysqli_stmt_close($stmt);

if (mysqli_errno($conn) === 0) {
    mysqli_query($conn, "COMMIT");
    echo "✓ Departments seeded successfully!\n\n";
    echo "Inserted departments:\n";
    foreach ($departments as $d) {
        echo "  - " . $d . "\n";
    }
    echo "\nTotal: " . count($departments) . " departments\n";
} else {
    mysqli_query($conn, "ROLLBACK");
    echo "✗ Error: " . mysqli_error($conn) . "\n";
    exit(1);
}
?>