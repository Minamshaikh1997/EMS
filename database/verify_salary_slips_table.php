<?php
// Quick verification script to check if salary_slips table exists
include(__DIR__ . '/../config/db.php');

$result = mysqli_query($conn, "SHOW TABLES LIKE 'salary_slips'");

if (mysqli_num_rows($result) > 0) {
    echo "<div class='alert alert-success'>✓ salary_slips table exists!</div>";
    
    // Show table structure
    $structure = mysqli_query($conn, "DESCRIBE salary_slips");
    echo "<h4>Table Structure:</h4>";
    echo "<table class='table table-bordered'>";
    echo "<thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr></thead>";
    echo "<tbody>";
    while ($row = mysqli_fetch_assoc($structure)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
    
    // Count records
    $count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM salary_slips"));
    echo "<div class='alert alert-info'>Total records: {$count['total']}</div>";
    
    echo "<a href='../admin/payroll_dashboard.php' class='btn btn-primary'>Go to Payroll Dashboard</a>";
} else {
    echo "<div class='alert alert-danger'>✗ salary_slips table does NOT exist!</div>";
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Verify salary_slips Table</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h3>Table Verification</h3>
            </div>
            <div class="card-body">
                <?php echo $result ? "Table found!" : "Table not found!"; ?>
            </div>
        </div>
    </div>
</body>
</html>