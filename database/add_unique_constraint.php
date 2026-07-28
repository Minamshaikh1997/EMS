<?php
include(__DIR__ . '/../config/db.php');

echo "<h3>Adding Unique Constraint to Salary Structure</h3>";

// Check if constraint already exists
$check = mysqli_query($conn, "SHOW INDEX FROM salary_structure WHERE Key_name = 'unique_employee'");

if (mysqli_num_rows($check) > 0) {
    echo "<div class='alert alert-info'>Unique constraint already exists!</div>";
} else {
    // Add unique constraint
    $result = mysqli_query($conn, "ALTER TABLE salary_structure ADD UNIQUE KEY unique_employee (employee_id)");
    
    if ($result) {
        echo "<div class='alert alert-success'>✓ Unique constraint added successfully!</div>";
        echo "<div class='alert alert-success'>Future duplicate entries will be prevented at database level.</div>";
    } else {
        echo "<div class='alert alert-danger'>Error: " . mysqli_error($conn) . "</div>";
    }
}

echo "<hr>";
echo "<a href='../admin/salary_structure.php' class='btn btn-primary'>Go to Salary Structure</a>";

mysqli_close($conn);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Unique Constraint</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header"><h3>Done</h3></div>
            <div class="card-body"><?php echo "Complete!"; ?></div>
        </div>
    </div>
</body>
</html>