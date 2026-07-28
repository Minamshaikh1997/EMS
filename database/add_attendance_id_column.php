<?php
// Add attendance_id column to attendance_adjustments table
include('config/db.php');

echo "Adding attendance_id column to attendance_adjustments table...\n\n";

// Check if column exists
$check = mysqli_query($conn, "SHOW COLUMNS FROM attendance_adjustments LIKE 'attendance_id'");

if (mysqli_num_rows($check) > 0) {
    echo "  ✓ Column 'attendance_id' already exists\n";
} else {
    $sql = "ALTER TABLE attendance_adjustments ADD COLUMN attendance_id INT DEFAULT NULL";
    if (mysqli_query($conn, $sql)) {
        echo "  ✓ Column 'attendance_id' added successfully\n";
    } else {
        echo "  ✗ Error adding 'attendance_id': " . mysqli_error($conn) . "\n";
        exit(1);
    }
}

echo "\nVerifying table structure...\n";
$result = mysqli_query($conn, 'DESCRIBE attendance_adjustments');
while($row = mysqli_fetch_assoc($result)) {
    echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\n✓ Done!\n";
?>