<?php
session_start();

include("admincheck_role.php");
include("../config/db.php");

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.html");
    exit();
}

$message = "";

if (isset($_POST['generate'])) {
    $employee_id = intval($_POST['employee_id']);
    $month = mysqli_real_escape_string($conn, $_POST['month']);
    $year = intval($_POST['year']);

    $check = mysqli_query($conn, "
        SELECT id FROM payroll
        WHERE employee_id='$employee_id'
        AND payroll_month='$month'
        AND payroll_year='$year'
    ");

    if (mysqli_num_rows($check) > 0) {
        $message = "<div class='alert alert-warning'>Payroll already generated for this month.</div>";
    } else {
        $salary = mysqli_query($conn, "
            SELECT id, basic_salary
            FROM salary_structure
            WHERE employee_id='$employee_id'
            LIMIT 1
        ");

        if (mysqli_num_rows($salary) == 0) {
            $message = "<div class='alert alert-danger'>Salary Structure not found.</div>";
        } else {
            $row = mysqli_fetch_assoc($salary);
            $salary_structure_id = $row['id'];

            $allowanceResult = mysqli_query($conn, "
                SELECT IFNULL(SUM(ssc.amount), 0) AS total_allowance
                FROM salary_structure_components ssc
                INNER JOIN salary_components sc ON ssc.component_id = sc.id
                WHERE ssc.salary_structure_id='$salary_structure_id'
                AND sc.component_type='Allowance'
            ");

            $deductionResult = mysqli_query($conn, "
                SELECT IFNULL(SUM(ssc.amount), 0) AS total_deduction
                FROM salary_structure_components ssc
                INNER JOIN salary_components sc ON ssc.component_id = sc.id
                WHERE ssc.salary_structure_id='$salary_structure_id'
                AND sc.component_type='Deduction'
            ");

            $allowanceRow = mysqli_fetch_assoc($allowanceResult);
            $deductionRow = mysqli_fetch_assoc($deductionResult);

            $basic_salary = $row['basic_salary'];
            $allowances = $allowanceRow['total_allowance'];
            $deductions = $deductionRow['total_deduction'];
            $overtime = 0;
            $bonus = 0;
            $tax = 0;
            $net_salary = ($basic_salary + $allowances + $overtime + $bonus) - ($deductions + $tax);

            mysqli_query($conn, "
                INSERT INTO payroll
                (
                    employee_id,
                    payroll_month,
                    payroll_year,
                    basic_salary,
                    allowances,
                    overtime,
                    bonus,
                    deductions,
                    tax,
                    net_salary,
                    payment_status
                )
                VALUES
                (
                    '$employee_id',
                    '$month',
                    '$year',
                    '$basic_salary',
                    '$allowances',
                    '$overtime',
                    '$bonus',
                    '$deductions',
                    '$tax',
                    '$net_salary',
                    'Pending'
                )
            ");

            $message = "<div class='alert alert-success'>Payroll Generated Successfully.</div>";
        }
    }
}

$employees = mysqli_query($conn, "
    SELECT id, employee_id, full_name
    FROM employees
    ORDER BY full_name ASC
");

$months = [
    "January","February","March","April","May","June",
    "July","August","September","October","November","December"
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Generate Payroll</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="admin_panel.css" rel="stylesheet">
</head>

<body>

<div class="main" style="margin-left:0;">
<div class="container mt-5">

<div class="card shadow">
<div class="card-header bg-success text-white">
<h3 class="mb-0"><i class="fa fa-money-check-dollar"></i> Generate Payroll</h3>
</div>

<div class="card-body">

<?php echo $message; ?>

<form method="POST">
<div class="row">

<div class="col-md-4 mb-3">
<label class="form-label">Employee</label>
<select name="employee_id" class="form-select" required>
<option value="">Select Employee</option>
<?php while($emp = mysqli_fetch_assoc($employees)){ ?>
<option value="<?php echo $emp['id']; ?>">
<?php echo $emp['employee_id']; ?> - <?php echo $emp['full_name']; ?>
</option>
<?php } ?>
</select>
</div>

<div class="col-md-3 mb-3">
<label class="form-label">Month</label>
<select name="month" class="form-select" required>
<?php foreach($months as $m){ ?>
<option value="<?php echo $m; ?>" <?php echo ($m == date('F')) ? 'selected' : ''; ?>>
<?php echo $m; ?>
</option>
<?php } ?>
</select>
</div>

<div class="col-md-2 mb-3">
<label class="form-label">Year</label>
<input type="number" name="year" class="form-control" value="<?php echo date('Y'); ?>" required>
</div>

<div class="col-md-3 d-grid mb-3">
<label>&nbsp;</label>
<button type="submit" name="generate" class="btn btn-success">
<i class="fa fa-money-check-dollar"></i> Generate Payroll
</button>
</div>

</div>
</form>

<a href="payroll_dashboard.php" class="btn btn-secondary">
<i class="fa fa-arrow-left"></i> Back
</a>

</div>
</div>

</div>
</div>

</body>
</html>