<?php
session_start();
include("admincheck_role.php");
include("../config/db.php");

if(!isset($_SESSION['admin'])){
    header("Location: ../index.html");
    exit();
}

$id = intval($_GET['id']);

$result = mysqli_query($conn,"
SELECT s.*,e.employee_id,e.full_name
FROM salary_structure s
INNER JOIN employees e
ON s.employee_id=e.id
WHERE s.id='$id'
");

$data = mysqli_fetch_assoc($result);

if(isset($_POST['update']))
{

$basic=$_POST['basic_salary'];

$house=$_POST['house_allowance'];

$medical=$_POST['medical_allowance'];

$transport=$_POST['transport_allowance'];

$other=$_POST['other_allowance'];

$tax=$_POST['tax_deduction'];

$deduction=$_POST['other_deduction'];

mysqli_query($conn,"
UPDATE salary_structure SET

basic_salary='$basic',

house_allowance='$house',

medical_allowance='$medical',

transport_allowance='$transport',

other_allowance='$other',

tax_deduction='$tax',

other_deduction='$deduction'

WHERE id='$id'
");

header("Location: salary_structure.php");
exit();

}
?>

<!DOCTYPE html>

<html>

<head>

<title>Edit Salary</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<div class="container mt-5">

<div class="card shadow">

<div class="card-header bg-warning">

<h3>

<i class="fa fa-edit"></i>

Edit Salary Structure

</h3>

</div>

<div class="card-body">

<form method="POST">

<div class="row">

<div class="col-md-6 mb-3">

<label>Employee</label>

<input
class="form-control"
value="<?php echo $data['employee_id']." - ".$data['full_name'];?>"
readonly>

</div>

<div class="col-md-6 mb-3">

<label>Basic Salary</label>

<input
type="number"
name="basic_salary"
class="form-control"
value="<?=$data['basic_salary']?>">

</div>

<div class="col-md-6 mb-3">

<label>House Allowance</label>

<input
type="number"
name="house_allowance"
class="form-control"
value="<?=$data['house_allowance']?>">

</div>

<div class="col-md-6 mb-3">

<label>Medical Allowance</label>

<input
type="number"
name="medical_allowance"
class="form-control"
value="<?=$data['medical_allowance']?>">

</div>

<div class="col-md-6 mb-3">

<label>Transport Allowance</label>

<input
type="number"
name="transport_allowance"
class="form-control"
value="<?=$data['transport_allowance']?>">

</div>

<div class="col-md-6 mb-3">

<label>Other Allowance</label>

<input
type="number"
name="other_allowance"
class="form-control"
value="<?=$data['other_allowance']?>">

</div>

<div class="col-md-6 mb-3">

<label>Tax Deduction</label>

<input
type="number"
name="tax_deduction"
class="form-control"
value="<?=$data['tax_deduction']?>">

</div>

<div class="col-md-6 mb-3">

<label>Other Deduction</label>

<input
type="number"
name="other_deduction"
class="form-control"
value="<?=$data['other_deduction']?>">

</div>

<div class="col-12">

<button
type="submit"
name="update"
class="btn btn-success">

<i class="fa fa-save"></i>

Update Salary

</button>

<a
href="salary_structure.php"
class="btn btn-secondary">

Back to Salary Structure

</a>
<a href="dashboard.php" class="btn btn-primary"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>

</div>

</div>

</form>

</div>

</div>

</div>

</body>

</html>