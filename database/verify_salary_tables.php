<?php
include_once '../config/db.php';

echo "<h2>Database Tables Verification</h2>";

// Check if salary_structure table exists
$result = mysqli_query($conn, "SHOW TABLES LIKE 'salary_structure'");
if (mysqli_num_rows($result) > 0) {
    echo "<div class='alert alert-success'>✓ salary_structure table exists</div>";
} else {
    echo "<div class='alert alert-danger'>✗ salary_structure table does NOT exist</div>";
}

// Check if salary_structure_components table exists
$result = mysqli_query($conn, "SHOW TABLES LIKE 'salary_structure_components'");
if (mysqli_num_rows($result) > 0) {
    echo "<div class='alert alert-success'>✓ salary_structure_components table exists</div>";
} else {
    echo "<div class='alert alert-danger'>✗ salary_structure_components table does NOT exist</div>";
}

// Check if salary_components table exists (needed for foreign key)
$result = mysqli_query($conn, "SHOW TABLES LIKE 'salary_components'");
if (mysqli_num_rows($result) > 0) {
    echo "<div class='alert alert-success'>✓ salary_components table exists</div>";
} else {
    echo "<div class='alert alert-warning'>⚠ salary_components table does NOT exist (needed for foreign key)</div>";
}

// Check if employees table exists (needed for foreign key)
$result = mysqli_query($conn, "SHOW TABLES LIKE 'employees'");
if (mysqli_num_rows($result) > 0) {
    echo "<div class='alert alert-success'>✓ employees table exists</div>";
} else {
    echo "<div class='alert alert-danger'>✗ employees table does NOT exist (needed for foreign key)</div>";
}

echo "<hr>";
echo "<a href='run_salary_tables_setup.php' class='btn btn-primary'>Create/Recreate Tables</a> ";
echo "<a href='../admin/payroll_dashboard.php' class='btn btn-success'>Go to Payroll Dashboard</a>";

mysqli_close($conn);
?>