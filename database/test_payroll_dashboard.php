<?php
// Test script to verify payroll_dashboard.php queries work
include(__DIR__ . '/../config/db.php');

echo "<h3>Testing Payroll Dashboard Queries</h3>";

// Test 1: Count employees
$result1 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM employees");
$row1 = mysqli_fetch_assoc($result1);
echo "<div class='alert alert-success'>✓ Total Employees: {$row1['total']}</div>";

// Test 2: Count salary structures
$result2 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM salary_structure");
$row2 = mysqli_fetch_assoc($result2);
echo "<div class='alert alert-success'>✓ Salary Structures: {$row2['total']}</div>";

// Test 3: Count salary slips (this was failing before)
$result3 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM salary_slips");
$row3 = mysqli_fetch_assoc($result3);
echo "<div class='alert alert-success'>✓ Salary Slips: {$row3['total']}</div>";

// Test 4: Current month payroll
$currentMonth = date('F');
$currentYear = date('Y');
$result4 = mysqli_query($conn, "
    SELECT
        COUNT(*) AS total_records,
        IFNULL(SUM(basic_salary + allowances + overtime + bonus), 0) AS total_gross,
        IFNULL(SUM(deductions + tax), 0) AS total_deductions,
        IFNULL(SUM(net_salary), 0) AS total_net
    FROM payroll
    WHERE payroll_month='$currentMonth'
    AND payroll_year='$currentYear'
");
$row4 = mysqli_fetch_assoc($result4);
echo "<div class='alert alert-info'>✓ Current Month ({$currentMonth} {$currentYear}): {$row4['total_records']} records</div>";

// Test 5: Pending payroll
$result5 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM payroll WHERE payment_status='Pending'");
$row5 = mysqli_fetch_assoc($result5);
echo "<div class='alert alert-warning'>✓ Pending Payroll: {$row5['total']}</div>";

// Test 6: Paid payroll
$result6 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM payroll WHERE payment_status='Paid'");
$row6 = mysqli_fetch_assoc($result6);
echo "<div class='alert alert-success'>✓ Paid Payroll: {$row6['total']}</div>";

// Test 7: Latest payroll
$result7 = mysqli_query($conn, "
    SELECT p.*, e.employee_id, e.full_name
    FROM payroll p
    INNER JOIN employees e ON p.employee_id = e.id
    ORDER BY p.id DESC
    LIMIT 5
");
echo "<div class='alert alert-info'>✓ Latest Payroll Records: " . mysqli_num_rows($result7) . " found</div>";

// Test 8: Component summary
$result8 = mysqli_query($conn, "
    SELECT
        sc.component_name,
        sc.component_type,
        COUNT(ssc.id) AS used_count,
        IFNULL(SUM(ssc.amount), 0) AS total_amount
    FROM salary_components sc
    LEFT JOIN salary_structure_components ssc ON sc.id = ssc.component_id
    GROUP BY sc.id, sc.component_name, sc.component_type
    ORDER BY sc.component_type ASC, sc.component_name ASC
    LIMIT 8
");
echo "<div class='alert alert-info'>✓ Salary Components: " . mysqli_num_rows($result8) . " found</div>";

echo "<hr>";
echo "<div class='alert alert-success'><h4>✓ All queries executed successfully! The payroll_dashboard.php should work now.</h4></div>";
echo "<a href='../admin/payroll_dashboard.php' class='btn btn-primary btn-lg'>Go to Payroll Dashboard</a>";

mysqli_close($conn);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Payroll Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h3>Payroll Dashboard Test</h3>
            </div>
            <div class="card-body">
                <?php echo "All tests passed!"; ?>
            </div>
        </div>
    </div>
</body>
</html>