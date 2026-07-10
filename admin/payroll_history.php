<?php
session_start();

include("admincheck_role.php");
include("../config/db.php");

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.html");
    exit();
}

// Mark Payroll as Paid
if(isset($_GET['paid']))
{
    $id = intval($_GET['paid']);

    mysqli_query($conn,"
    UPDATE payroll
    SET status='Paid'
    WHERE id='$id'
    ");

    header("Location: payroll_history.php");
    exit();
}

$payroll = mysqli_query($conn,"
SELECT
p.*,
e.employee_id,
e.full_name

FROM payroll p

INNER JOIN employees e
ON p.employee_id=e.id

ORDER BY p.payroll_year DESC,
FIELD(
p.payroll_month,
'January','February','March','April','May','June',
'July','August','September','October','November','December'
) DESC
");
?>

<!DOCTYPE html>

<html>

<head>

<title>Payroll History</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<div class="container mt-4">

<div class="card shadow">

<div class="card-header bg-primary text-white">

<h3>

<i class="fa fa-file-invoice-dollar"></i>

Payroll History

</h3>

</div>

<div class="card-body table-responsive">

<table class="table table-bordered table-hover">

<thead class="table-dark">

<tr>

<th>Employee ID</th>

<th>Name</th>

<th>Month</th>

<th>Year</th>

<th>Gross Salary</th>

<th>Net Salary</th>

<th>Status</th>

<th>Action</th>

</tr>

</thead>

<tbody>

<?php while($row=mysqli_fetch_assoc($payroll)){ ?>

<tr>

<td><?=$row['employee_id']?></td>

<td><?=$row['full_name']?></td>

<td><?=$row['payroll_month']?></td>

<td><?=$row['payroll_year']?></td>

<td><?=number_format($row['gross_salary'],2)?></td>

<td><?=number_format($row['net_salary'],2)?></td>

<td>

<?php

if($row['status']=="Paid")
{
    echo "<span class='badge bg-success'>Paid</span>";
}
else
{
    echo "<span class='badge bg-warning text-dark'>Pending</span>";
}

?>

</td>

<td>

<?php if($row['status']=="Pending"){ ?>

<a
href="payroll_history.php?paid=<?=$row['id']?>"
class="btn btn-success btn-sm">

<i class="fa fa-check"></i>

Paid

</a>

<?php }else{ ?>

<button
class="btn btn-secondary btn-sm"
disabled>

Completed

</button>

<?php } ?>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

</div>

</body>

</html>