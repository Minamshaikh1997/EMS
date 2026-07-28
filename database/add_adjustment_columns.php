<?php
// Add missing columns to attendance_adjustments table
include('config/db.php');

$columns = [
    'request_no VARCHAR(50) DEFAULT NULL',
    'adjustment_type VARCHAR(50) DEFAULT \'Check In/Out\'',
    'attendance_date DATE DEFAULT NULL',
    'requested_check_in TIME DEFAULT NULL',
    'requested_check_out TIME DEFAULT NULL',
    'supervisor_comment TEXT DEFAULT NULL',
    'admin_comment TEXT DEFAULT NULL',
    'attachment VARCHAR(255) DEFAULT NULL'
];

echo "Adding missing columns to attendance_adjustments table...\n\n";

foreach ($columns as $column) {
    $column_name = explode(' ', $column)[0];
    
    // Check if column exists
    $check = mysqli_query($conn, "SHOW COLUMNS FROM attendance_adjustments LIKE '$column_name'");
    
    if (mysqli_num_rows($check) > 0) {
        echo "  ✓ Column '$column_name' already exists\n";
    } else {
        $sql = "ALTER TABLE attendance_adjustments ADD COLUMN $column";
        if (mysqli_query($conn, $sql)) {
            echo "  ✓ Column '$column_name' added successfully\n";
        } else {
            echo "  ✗ Error adding '$column_name': " . mysqli_error($conn) . "\n";
        }
    }
}

echo "\nVerifying table structure...\n";
$result = mysqli_query($conn, 'DESCRIBE attendance_adjustments');
while($row = mysqli_fetch_assoc($result)) {
    echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\n✓ Done!\n";
?>