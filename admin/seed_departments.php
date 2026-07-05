<?php
// One-click script to replace departments with the new organization structure.
// Visit this page in browser once (admin only) to reset departments.
include('../config/db.php');

// Ensure only an admin can run this from browser
session_start();
if (!isset($_SESSION['admin'])) {
    echo "Access denied. Please run while logged in as admin.";
    exit;
}

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
    echo "Departments reset successfully.<br><br>Inserted departments:<ul>";
    foreach ($departments as $d) echo "<li>".htmlspecialchars($d)."</li>";
    echo "</ul>";
} else {
    mysqli_query($conn, "ROLLBACK");
    echo "Error updating departments: " . mysqli_error($conn);
}

echo "<p><a href=\"employee.php\">Back to Employees</a></p>";

?>
