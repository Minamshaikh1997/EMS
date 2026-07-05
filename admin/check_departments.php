<?php
include('../config/db.php');

echo "<h3>Departments check</h3>";

// Check if table exists
$res = mysqli_query($conn, "SHOW TABLES LIKE 'departments'");
if (!$res) {
    echo "DB error: " . mysqli_error($conn);
    exit;
}

if (mysqli_num_rows($res) === 0) {
    echo "Table 'departments' does not exist.<br>";
    echo "You can create it by visiting <a href='seed_departments.php'>seed_departments.php</a> (admin only).";
    exit;
}

// Show count and rows
$countRes = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM departments");
$countRow = mysqli_fetch_assoc($countRes);

echo "Rows: " . intval($countRow['cnt']) . "<br><br>";

$rows = mysqli_query($conn, "SELECT * FROM departments ORDER BY id ASC LIMIT 100");
if ($rows) {
    echo "<table border=1 cellpadding=6 cellspacing=0><tr><th>id</th><th>department_name</th></tr>";
    while ($r = mysqli_fetch_assoc($rows)) {
        echo "<tr><td>" . htmlspecialchars($r['id']) . "</td><td>" . htmlspecialchars($r['department_name']) . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "Error reading departments: " . mysqli_error($conn);
}

echo "<p><a href='seed_departments.php'>Seed Departments</a></p>";
?>