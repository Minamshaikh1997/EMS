<?php
/**
 * Add payroll visibility column to employees table
 * This allows admin to control whether each employee can view their payroll/salary information
 */

include_once '../config/db.php';

echo "<h2>Add Payroll Visibility Column</h2>\n";

// Check if column already exists
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM employees LIKE 'can_view_payroll'");

if (mysqli_num_rows($check_column) > 0) {
    echo "<p style='color: orange;'>⚠️ Column 'can_view_payroll' already exists in employees table.</p>\n";
    exit();
}

// Add column
$sql = "ALTER TABLE employees ADD COLUMN can_view_payroll TINYINT(1) DEFAULT 1 COMMENT 'Whether employee can view payroll/salary information'";

if (mysqli_query($conn, $sql)) {
    echo "<p style='color: green;'>✅ Successfully added 'can_view_payroll' column to employees table!</p>\n";
    echo "<p>Default value: 1 (enabled for all existing employees)</p>\n";
} else {
    echo "<p style='color: red;'>❌ Error adding column: " . mysqli_error($conn) . "</p>\n";
}

mysqli_close($conn);
?>