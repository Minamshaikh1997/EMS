<?php
include(__DIR__ . '/../config/db.php');

echo "<h3>Fixing Duplicate Salary Components</h3>";

// Get duplicates
$duplicates = mysqli_query($conn, "SELECT component_name, component_type, GROUP_CONCAT(id) as ids, COUNT(*) as cnt FROM salary_components GROUP BY component_name, component_type HAVING cnt > 1");

if (mysqli_num_rows($duplicates) > 0) {
    echo "<div class='alert alert-warning'>Found duplicates. Cleaning up...</div>";
    
    while ($dup = mysqli_fetch_assoc($duplicates)) {
        $ids = explode(',', $dup['ids']);
        $keep_id = $ids[0]; // Keep the first one
        $remove_ids = array_slice($ids, 1); // Remove the rest
        
        echo "<div><strong>{$dup['component_name']}</strong> - Keeping ID: $keep_id, Removing: " . implode(', ', $remove_ids) . "</div>";
        
        // Update any salary_structure_components that reference the duplicates to point to the kept one
        foreach ($remove_ids as $remove_id) {
            mysqli_query($conn, "UPDATE salary_structure_components SET component_id='$keep_id' WHERE component_id='$remove_id'");
            mysqli_query($conn, "DELETE FROM salary_components WHERE id='$remove_id'");
        }
    }
    
    echo "<div class='alert alert-success'>Duplicates cleaned! Kept one entry per component.</div>";
} else {
    echo "<div class='alert alert-info'>No duplicates found.</div>";
}

// Add unique constraint
echo "<hr><h4>Adding Unique Constraint...</h4>";
$result = mysqli_query($conn, "ALTER TABLE salary_components ADD UNIQUE KEY unique_component (component_name, component_type)");

if ($result) {
    echo "<div class='alert alert-success'>Unique constraint added! Future duplicates prevented.</div>";
} else {
    echo "<div class='alert alert-danger'>Error: " . mysqli_error($conn) . "</div>";
}

// Verify
$count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM salary_components"));
echo "<div class='alert alert-info'>Total components: {$count['total']}</div>";

echo "<hr><a href='../admin/salary_structure.php' class='btn btn-primary'>Go to Salary Structure</a>";

mysqli_close($conn);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Component Duplicates</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header"><h3>Fix Complete</h3></div>
            <div class="card-body"><?php echo "Done!"; ?></div>
        </div>
    </div>
</body>
</html>