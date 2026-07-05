<?php
session_start();

if(!isset($_SESSION['admin']))
{
    header("Location: ../index.php");
    exit();
}

include("../config/db.php");

// Add Notice
if(isset($_POST['save']))
{
    $title = mysqli_real_escape_string($conn,$_POST['title']);
    $notice = mysqli_real_escape_string($conn,$_POST['notice']);

    mysqli_query($conn,"
    INSERT INTO notices
    (
        title,
        notice
    )
    VALUES
    (
        '$title',
        '$notice'
    )
    ");

    header("Location: add_notice.php");
    exit();
}

// Delete Notice
if(isset($_GET['delete']))
{
    $id = intval($_GET['delete']);

    mysqli_query($conn,"
    DELETE FROM notices
    WHERE id='$id'
    ");

    header("Location: add_notice.php");
    exit();
}

$result = mysqli_query($conn,"
SELECT *
FROM notices
ORDER BY id DESC
");
?>

<!DOCTYPE html>
<html>

<head>

<title>Notice Board</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<?php include("../dark_mode.php"); ?>

<body class="bg-light">

<div class="container mt-5">

<div class="card shadow">

<div class="card-header bg-primary text-white">

<h3>Notice Board Management</h3>

</div>

<div class="card-body">

<form method="POST">

<div class="mb-3">

<label>Notice Title</label>

<input
type="text"
name="title"
class="form-control"
required>

</div>

<div class="mb-3">

<label>Notice</label>

<textarea
name="notice"
class="form-control"
rows="4"
required></textarea>

</div>

<button
type="submit"
name="save"
class="btn btn-success">

Add Notice

</button>

</form>

<hr>

<table class="table table-bordered table-striped">

<thead class="table-dark">

<tr>

<th>ID</th>
<th>Title</th>
<th>Notice</th>
<th>Date</th>
<th>Action</th>

</tr>

</thead>

<tbody>

<?php while($row=mysqli_fetch_assoc($result)){ ?>

<tr>

<td><?php echo $row['id']; ?></td>

<td><?php echo $row['title']; ?></td>

<td><?php echo $row['notice']; ?></td>

<td><?php echo date("d-m-Y",strtotime($row['created_at'])); ?></td>

<td>

<a
href="?delete=<?php echo $row['id']; ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Delete this notice?')">

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