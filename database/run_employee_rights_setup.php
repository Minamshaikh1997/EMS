<?php
/**
 * Complete Employee Rights System Setup
 * This script sets up all necessary database columns for employee rights management
 */

include_once '../config/db.php';

echo "<h2>Employee Rights System - Complete Setup</h2>\n";
echo "<hr>\n";

// Add all necessary columns
$columns = [
    'can_view_payroll' => 'TINYINT(1) DEFAULT 1 COMMENT "Whether employee can view payroll/salary information"',
    'can_apply_leave' => 'TINYINT(1) DEFAULT 1 COMMENT "Whether employee can apply for leave"',
    'can_view_attendance' => 'TINYINT(1) DEFAULT 1 COMMENT "Whether employee can view attendance"',
    'can_submit_adjustment' => 'TINYINT(1) DEFAULT 1 COMMENT "Whether employee can submit adjustments"',
    'can_edit_profile' => 'TINYINT(1) DEFAULT 1 COMMENT "Whether employee can edit their profile"',
    'can_view_reports' => 'TINYINT(1) DEFAULT 1 COMMENT "Whether employee can view reports"',
    'can_change_password' => 'TINYINT(1) DEFAULT 1 COMMENT "Whether employee can change password"'
];

$success_count = 0;
$error_count = 0;

foreach ($columns as $column_name => $column_definition) {
    $sql = "ALTER TABLE employees ADD COLUMN IF NOT EXISTS $column_name $column_definition";
    
    if (mysqli_query($conn, $sql)) {
        echo "<p style='color: green;'>✅ Added column: <strong>$column_name</strong></p>\n";
        $success_count++;
    } else {
        $error_msg = mysqli_error($conn);
        if (strpos($error_msg, 'Duplicate column name') !== false) {
            echo "<p style='color: orange;'>⚠️ Column already exists: <strong>$column_name</strong></p>\n";
            $success_count++;
        } else {
            echo "<p style='color: red;'>❌ Error adding $column_name: $error_msg</p>\n";
            $error_count++;
        }
    }
}

echo "<hr>\n";
echo "<h3>Setup Summary:</h3>\n";
echo "<p style='color: green;'>✅ Successful: $success_count columns</p>\n";
if ($error_count > 0) {
    echo "<p style='color: red;'>❌ Failed: $error_count columns</p>\n";
}

echo "<hr>\n";
echo "<h3>Next Steps:</h3>\n";
echo "<ol>\n";
echo "<li>Go to <a href='admin/employee_rights_management.php' target='_blank'>Employee Rights Management</a> page</li>\n";
echo "<li>You can now manage individual employee rights</li>\n";
echo "<li>Employees will see only the features they have rights to access</li>\n";
echo "</ol>\n";

echo "<hr>\n";
echo "<p><strong>Available Rights:</strong></p>\n";
echo "<ul>\n";
foreach ($columns as $column_name => $definition) {
    $label = str_replace('can_', '', $column_name);
    $label = str_replace('_', ' ', ucfirst($label));
    echo "<li><strong>$column_name</strong> - $label</li>\n";
}
echo "</ul>\n";

mysqli_close($conn);
?>