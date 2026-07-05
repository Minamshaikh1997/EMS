<?php
session_start();

if(!isset($_SESSION['admin']))
{
    header("Location: ../index.php");
    exit();
}

include("../config/db.php");

// Add Holiday
if(isset($_POST['save']))
{

    $holiday_name = mysqli_real_escape_string($conn,$_POST['holiday_name']);
    $holiday_date = $_POST['holiday_date'];
    $description = mysqli_real_escape_string($conn,$_POST['description']);

    mysqli_query($conn,"
    INSERT INTO holidays
    (
        holiday_name,
        holiday_date,
        description
    )
    VALUES
    (
        '$holiday_name',
        '$holiday_date',
        '$description'
    )
    ");

    header("Location: add_holiday.php");
    exit();
}

// Delete Holiday
if(isset($_GET['delete']))
{

    $id = intval($_GET['delete']);

    mysqli_query($conn,"
    DELETE FROM holidays
    WHERE id='$id'
    ");

    header("Location: add_holiday.php");
    exit();
}

$result = mysqli_query($conn,"
SELECT *
FROM holidays
ORDER BY holiday_date ASC
");
?>

<!DOCTYPE html>
<html>

<head>

<?php include("../dark_mode.php"); ?>

<body>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<div class="container mt-5">

<div class="card shadow">

<div class="card-header bg-primary text-white">

<h3>Holiday Management</h3>

</div>

<div class="card-body">

<form method="POST">

<div class="row">

<div class="col-md-4">

<label>Holiday Name</label>

<input
type="text"
name="holiday_name"
class="form-control"
required>

</div>

<div class="col-md-3">

<label>Date</label>

<input
type="date"
name="holiday_date"
class="form-control"
required>

</div>

<div class="col-md-3">

<label>Description</label>

<input
type="text"
name="description"
class="form-control">

</div>

<div class="col-md-2">

<label>&nbsp;</label>

<button
type="submit"
name="save"
class="btn btn-success w-100">

Save

</button>

</div>

</div>

</form>

<hr>

<table class="table table-bordered table-striped">

<thead class="table-dark">

<tr>

<th>ID</th>
<th>Holiday</th>
<th>Date</th>
<th>Description</th>
<th>Action</th>

</tr>

</thead>

<tbody>

<?php while($row=mysqli_fetch_assoc($result)){ ?>

<tr>

<td><?php echo $row['id']; ?></td>

<td><?php echo $row['holiday_name']; ?></td>

<td><?php echo date("d-m-Y",strtotime($row['holiday_date'])); ?></td>

<td><?php echo $row['description']; ?></td>

<td>

<a
href="?delete=<?php echo $row['id']; ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Delete this holiday?')">

Delete

</a>

</td>

</tr>

<?php } ?>

</tbody>

</table>

<a href="dashboard.php" class="btn btn-secondary">

Back Dashboard

</a>

</div>

</div>

</div>

</body>

</html>