<?php
include(__DIR__ . '/../config/db.php');

echo "<h3>Checking Salary Components for Duplicates</h3>";

// Check for duplicate component names
$result = mysqli_query($conn, "SELECT component_name, component_type, COUNT(*) as cnt FROM salary_components GROUP BY component_name, component_type HAVING cnt > 1");

if (mysqli_num_rows($result) > 0) {
    echo "<div class='alert alert-warning'>Duplicate components found:</div><ul>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<li>{$row['component_name']} ({$row['component_type']}) - Count: {$row['cnt']}</li>";
    }
    echo "</ul>";
} else {
    echo "<div class='alert alert-info'>No duplicate component names found.</div>";
}

// Show all components
echo "<hr><h4>All Components:</h4>";
$all = mysqli_query($conn, "SELECT * FROM salary_components ORDER BY component_type, component_name");
echo "<table class='table table-bordered'><tr><th>ID</th><th>Name</th><th>Type</th><th>Status</th></tr>";
while ($row = mysqli_fetch_assoc($all)) {
    echo "<tr><td>{$row['id']}</td><td>{$row['component_name']}</td><td>{$row['component_type']}</td><td>{$row['status']}</td></tr>";
}
echo "</table>";

mysqli_close($conn);
?>