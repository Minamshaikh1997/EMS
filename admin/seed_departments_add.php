<?php
// Safe seeder: inserts missing departments without deleting existing rows.
// Visit in browser while logged in as admin.
include('../config/db.php');
session_start();
if (!isset($_SESSION['admin'])) {
    echo "Access denied. Log in as admin and retry.";
    exit;
}

$desired = [
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

// fetch existing names
$res = mysqli_query($conn, "SELECT department_name FROM departments");
$existing = [];
if ($res) {
    while ($r = mysqli_fetch_assoc($res)) $existing[] = $r['department_name'];
}

$inserted = [];
$skipped = [];
$stmt = mysqli_prepare($conn, "INSERT INTO departments (department_name) VALUES (?)");
foreach ($desired as $d) {
    if (in_array($d, $existing)) {
        $skipped[] = $d;
        continue;
    }
    mysqli_stmt_bind_param($stmt, 's', $d);
    if (mysqli_stmt_execute($stmt)) $inserted[] = $d; else $skipped[] = $d . ' (error: ' . mysqli_error($conn) . ')';
}
mysqli_stmt_close($stmt);

echo "Inserted: <ul>";
foreach ($inserted as $i) echo "<li>".htmlspecialchars($i)."</li>";
echo "</ul>";
echo "Skipped/Existing: <ul>";
foreach ($skipped as $s) echo "<li>".htmlspecialchars($s)."</li>";
echo "</ul>";
echo "<p><a href=\"check_departments.php\">Check Departments</a></p>";

?>
