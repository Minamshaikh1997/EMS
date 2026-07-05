<?php
// Temporary script to seed departments without logging in.
// SECURITY: Remove this file after use.

include('../config/db.php');

$expected_key = 'seed_now_123';
$key = isset($_GET['key']) ? $_GET['key'] : '';
if ($key !== $expected_key) {
    echo "Unauthorized. Provide the correct key in URL, e.g. ?key=$expected_key";
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

// Create table if missing
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(255) NOT NULL
)");

mysqli_query($conn, "START TRANSACTION");
mysqli_query($conn, "DELETE FROM departments");

$stmt = mysqli_prepare($conn, "INSERT INTO departments (department_name) VALUES (?)");
foreach ($departments as $d) {
    mysqli_stmt_bind_param($stmt, 's', $d);
    mysqli_stmt_execute($stmt);
}
mysqli_stmt_close($stmt);

if (mysqli_errno($conn) === 0) {
    mysqli_query($conn, "COMMIT");
    echo "Departments seeded successfully.<br><ul>";
    foreach ($departments as $d) echo "<li>".htmlspecialchars($d)."</li>";
    echo "</ul><p>Now verify: <a href=\"check_departments.php\">Check Departments</a></p>";
} else {
    mysqli_query($conn, "ROLLBACK");
    echo "Error seeding departments: " . mysqli_error($conn);
}

echo "<p>Important: delete this file after use for security.</p>";

?>
