<?php
// Temporary debug page — lists employee email and login status
include('../config/db.php');

echo "<h3>Employee accounts</h3>";
echo "<p><a href=\"../index.php\">Back to main login</a></p>";

$res = mysqli_query($conn, "SELECT id, email, full_name, role, password FROM employees");
if (!$res) {
    echo "<div style='color:red;'>DB query failed: " . mysqli_error($conn) . "</div>";
    exit;
}

echo "<table border=1 cellpadding=6 cellspacing=0>",
     "<tr><th>id</th><th>email</th><th>name</th><th>role</th><th>password</th></tr>";
while ($row = mysqli_fetch_assoc($res)) {
    echo "<tr><td>" . htmlspecialchars($row['id']) . "</td><td>" . htmlspecialchars($row['email']) . "</td><td>" . htmlspecialchars($row['full_name']) . "</td><td>" . htmlspecialchars($row['role']) . "</td><td>" . htmlspecialchars($row['password']) . "</td></tr>";
}
 echo "</table>";
?>