<?php
session_start();

include("admincheck_role.php");
include("../config/db.php");

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.html");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: salary_slips.php");
    exit();
}

$id = intval($_GET['id']);

$slip = mysqli_query($conn, "
    SELECT
        ss.*,
        e.employee_id AS emp_code,
        e.full_name
    FROM salary_slips ss
    INNER JOIN employees e
    ON ss.employee_id = e.id
    WHERE ss.id='$id'
");

if (mysqli_num_rows($slip) == 0) {
    echo "Salary Slip not found.";
    exit();
}

$row = mysqli_fetch_assoc($slip);

$salaryStructure = mysqli_query($conn, "
    SELECT id
    FROM salary_structure
    WHERE employee_id='{$row['employee_id']}'
");

$allowanceComponents = false;
$deductionComponents = false;

if (mysqli_num_rows($salaryStructure) > 0) {
    $salaryStructureRow = mysqli_fetch_assoc($salaryStructure);
    $salary_structure_id = $salaryStructureRow['id'];

    $allowanceComponents = mysqli_query($conn, "
        SELECT sc.component_name, ssc.amount
        FROM salary_structure_components ssc
        INNER JOIN salary_components sc ON ssc.component_id = sc.id
        WHERE ssc.salary_structure_id='$salary_structure_id'
        AND sc.component_type='Allowance'
        AND ssc.amount > 0
        ORDER BY sc.component_name ASC
    ");

    $deductionComponents = mysqli_query($conn, "
        SELECT sc.component_name, ssc.amount
        FROM salary_structure_components ssc
        INNER JOIN salary_components sc ON ssc.component_id = sc.id
        WHERE ssc.salary_structure_id='$salary_structure_id'
        AND sc.component_type='Deduction'
        AND ssc.amount > 0
        ORDER BY sc.component_name ASC
    ");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Print Salary Slip</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#f4f7fc;
    font-family:Arial, sans-serif;
}

.slip-box{
    max-width:850px;
    margin:30px auto;
    background:white;
    padding:30px;
    border:1px solid #ddd;
}

.company-title{
    text-align:center;
    border-bottom:2px solid #222;
    padding-bottom:15px;
    margin-bottom:25px;
}

.company-title h2{
    margin:0;
    font-weight:bold;
}

.info-table td{
    padding:8px;
}

.salary-table th,
.salary-table td{
    padding:10px;
}

.net-box{
    background:#eaf4ff;
    border:1px solid #0d6efd;
    padding:15px;
    font-size:20px;
    font-weight:bold;
    text-align:right;
}

.print-btn{
    max-width:850px;
    margin:20px auto;
    text-align:right;
}

@media print{
    body{
        background:white;
    }

    .print-btn{
        display:none;
    }

    .slip-box{
        margin:0;
        max-width:100%;
        border:none;
    }
}
</style>

</head>

<body>

<div class="print-btn">
    <button onclick="window.print()" class="btn btn-primary">
        Print Salary Slip
    </button>

    <a href="salary_slips.php" class="btn btn-secondary">
        Back
    </a>
</div>

<div class="slip-box">

<div class="company-title">
    <h2>Shaikh</h2>
    <p class="mb-0">Salary Slip - <?php echo date('F Y', strtotime($row['salary_month'] . '-01')); ?></p>
</div>

<table class="table table-bordered info-table">

<tr>
    <td><strong>Employee ID</strong></td>
    <td><?php echo $row['emp_code']; ?></td>
    <td><strong>Employee Name</strong></td>
    <td><?php echo $row['full_name']; ?></td>
</tr>

</table>

<table class="table table-bordered salary-table">

<thead class="table-dark">
<tr>
    <th>Earnings</th>
    <th class="text-end">Amount</th>
    <th>Deductions</th>
    <th class="text-end">Amount</th>
</tr>
</thead>

<tbody>

<tr>
    <td>Basic Salary</td>
    <td class="text-end"><?php echo number_format($row['basic_salary'], 2); ?></td>
    <td></td>
    <td></td>
</tr>

<?php
$allowanceRows = [];
$deductionRows = [];

if ($allowanceComponents) {
    while ($component = mysqli_fetch_assoc($allowanceComponents)) {
        $allowanceRows[] = $component;
    }
}

if ($deductionComponents) {
    while ($component = mysqli_fetch_assoc($deductionComponents)) {
        $deductionRows[] = $component;
    }
}

$maxRows = max(count($allowanceRows), count($deductionRows));

for ($i = 0; $i < $maxRows; $i++) {
?>

<tr>
    <td><?php echo isset($allowanceRows[$i]) ? $allowanceRows[$i]['component_name'] : ''; ?></td>
    <td class="text-end">
        <?php echo isset($allowanceRows[$i]) ? number_format($allowanceRows[$i]['amount'], 2) : ''; ?>
    </td>
    <td><?php echo isset($deductionRows[$i]) ? $deductionRows[$i]['component_name'] : ''; ?></td>
    <td class="text-end">
        <?php echo isset($deductionRows[$i]) ? number_format($deductionRows[$i]['amount'], 2) : ''; ?>
    </td>
</tr>

<?php } ?>

<tr>
    <th>Gross Salary</th>
    <th class="text-end"><?php echo number_format($row['gross_salary'], 2); ?></th>
    <th>Total Deduction</th>
    <th class="text-end"><?php echo number_format($row['deduction'], 2); ?></th>
</tr>

</tbody>

</table>

<div class="net-box">
    Net Salary: <?php echo number_format($row['net_salary'], 2); ?>
</div>

<div class="row mt-5">

<div class="col-6">
    <p>_________________________</p>
    <p>Employee Signature</p>
</div>

<div class="col-6 text-end">
    <p>_________________________</p>
    <p>Authorized Signature</p>
</div>

</div>

</div>

</body>
</html>
