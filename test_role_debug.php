<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("admin/admincheck_role.php");
include("config/db.php");

echo "<h2>Role Debug Info</h2>";
echo "Session admin_role: " . ($_SESSION['admin_role'] ?? 'NOT SET') . "<br>";
echo "Session admin: " . ($_SESSION['admin'] ?? 'NOT SET') . "<br>";
echo "Session admin_id: " . ($_SESSION['admin_id'] ?? 'NOT SET') . "<br>";

// Force fetch from database
$admin_id = $_SESSION['admin_id'] ?? 0;
$admin_email = $_SESSION['admin'] ?? '';

echo "<br><b>Fetching from admin table...</b><br>";

if ($admin_id > 0) {
    $q = mysqli_query($conn, "SELECT id, name, email, role FROM admin WHERE id='$admin_id' LIMIT 1");
} else {
    $q = mysqli_query($conn, "SELECT id, name, email, role FROM admin WHERE email='$admin_email' LIMIT 1");
}

if ($q && mysqli_num_rows($q) > 0) {
    $r = mysqli_fetch_assoc($q);
    echo "Admin ID: " . $r['id'] . "<br>";
    echo "Admin Name: " . $r['name'] . "<br>";
    echo "Admin Email: " . $r['email'] . "<br>";
    echo "<b>Admin Role: " . $r['role'] . "</b><br>";
} else {
    echo "Admin not found in database!<br>";
    if (!$q) echo "Query error: " . mysqli_error($conn) . "<br>";
}

echo "<br><b>Your current role from session: " . $admin_role . "</b><br>";
echo "Can manage status? " . (in_array($admin_role, ['Super Admin', 'Admin', 'Operations Manager']) ? 'YES' : 'NO') . "<br>";

// Debug: show all admins
echo "<br><b>All Admins in system:</b><br>";
$all = mysqli_query($conn, "SELECT id, name, email, role FROM admin");
while ($row = mysqli_fetch_assoc($all)) {
    echo "ID: {$row['id']} | {$row['name']} | {$row['email']} | Role: {$row['role']}<br>";
}
?>