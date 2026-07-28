<?php
// Fix incorrect leave balance values in the database
include('config/db.php');

echo "Fixing leave balance values...\n\n";

// Update all records to correct defaults
// Annual Leave should be 7, Casual Leave should be 10, Sick Leave should be 10
$sql = "UPDATE leave_balance SET 
    annual_leave = 7, 
    casual_leave = 10, 
    sick_leave = 10 
    WHERE annual_leave = 20 OR casual_leave = 12";

if (mysqli_query($conn, $sql)) {
    $affected = mysqli_affected_rows($conn);
    echo "✓ Updated $affected employee(s) leave balance\n";
} else {
    echo "✗ Error: " . mysqli_error($conn) . "\n";
    exit(1);
}

// Verify the changes
echo "\nVerifying leave balances:\n";
$result = mysqli_query($conn, "SELECT employee_id, annual_leave, casual_leave, sick_leave FROM leave_balance");
while($row = mysqli_fetch_assoc($result)) {
    echo "  Employee #{$row['employee_id']}: Annual={$row['annual_leave']}, Casual={$row['casual_leave']}, Sick={$row['sick_leave']}\n";
}

echo "\n✓ Done!\n";
echo "\nCorrect defaults:\n";
echo "  Annual Leave: 7 days\n";
echo "  Casual Leave: 10 days\n";
echo "  Sick Leave: 10 days\n";
?>