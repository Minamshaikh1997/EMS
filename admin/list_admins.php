<?php
// Temporary debug page — lists admin accounts (id, email, role)
include('../config/db.php');

echo "<h3>Admin accounts</h3>";
echo "<p><a href=\"dashboard.php\">Back to admin dashboard</a></p>";

$res = mysqli_query($conn, "SELECT id, email, role FROM admin");
if (!$res) {
    echo "<div style='color:red;'>DB query failed: " . mysqli_error($conn) . "</div>";
    exit;
}

echo "<table border=1 cellpadding=6 cellspacing=0>",
     "<tr><th>id</th><th>email</th><th>role</th></tr>";
while ($row = mysqli_fetch_assoc($res)) {
    echo "<tr><td>" . htmlspecialchars($row['id']) . "</td><td>" . htmlspecialchars($row['email']) . "</td><td>" . htmlspecialchars($row['role']) . "</td></tr>";
}
echo "</table>";

?>
