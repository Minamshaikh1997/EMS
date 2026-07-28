<?php
// Add missing leave columns to employees table
include('config/db.php');

$columns = [
    'annual_leave INT DEFAULT 7',
    'sick_leave INT DEFAULT 10',
    'casual_leave INT DEFAULT 10'
];

echo "Adding missing leave columns to employees table...\n\n";

foreach ($columns as $column) {
    $column_name = explode(' ', $column)[0];
    
    // Check if column exists
    $check = mysqli_query($conn, "SHOW COLUMNS FROM employees LIKE '$column_name'");
    
    if (mysqli_num_rows($check) > 0) {
        echo "  ✓ Column '$column_name' already exists\n";
    } else {
        $sql = "ALTER TABLE employees ADD COLUMN $column";
        if (mysqli_query($conn, $sql)) {
            echo "  ✓ Column '$column_name' added successfully\n";
        } else {
            echo "  ✗ Error adding '$column_name': " . mysqli_error($conn) . "\n";
        }
    }
}

echo "\nVerifying table structure...\n";
$result = mysqli_query($conn, 'DESCRIBE employees');
while($row = mysqli_fetch_assoc($result)) {
    echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\n✓ Done!\n";
?>