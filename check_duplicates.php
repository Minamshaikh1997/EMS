<?php
include('config/db.php');

echo "Checking for duplicate emails...\n";
$result = mysqli_query($conn, "SELECT email, COUNT(*) as cnt FROM (SELECT email FROM admin UNION ALL SELECT email FROM employees) as all_users GROUP BY email HAVING cnt > 1");
if(mysqli_num_rows($result) > 0) {
    echo "DUPLICATE EMAILS FOUND:\n";
    while($row = mysqli_fetch_assoc($result)) {
        echo "  " . $row['email'] . " (" . $row['cnt'] . " times)\n";
    }
} else {
    echo "No duplicate emails\n";
}

echo "\nChecking employee table for admin@ems.com...\n";
$result = mysqli_query($conn, "SELECT id, full_name, email FROM employees WHERE email='admin@ems.com'");
if(mysqli_num_rows($result) > 0) {
    echo "WARNING: Employee account exists with admin@ems.com\n";
    while($row = mysqli_fetch_assoc($result)) {
        echo "  Employee: " . $row['full_name'] . " (ID: " . $row['id'] . ")\n";
    }
} else {
    echo "No employee account with that email\n";
}

mysqli_close($conn);
?>