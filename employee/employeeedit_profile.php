<?php
session_start();

if (!isset($_SESSION['employee_id'])) {
    header("Location: login.php");
    exit();
}

// Only users with role 'HR' can edit profiles here
if (!isset($_SESSION['employee_role']) || $_SESSION['employee_role'] !== 'HR') {
    header("Location: profile.php");
    exit();
}

include("../config/db.php");

$employee_id = $_SESSION['employee_id'];
$result = mysqli_query($conn, "SELECT * FROM employees WHERE id='$employee_id'");
$row = mysqli_fetch_assoc($result);

if(isset($_POST['update']))
{
    $full_name = mysqli_real_escape_string($conn,$_POST['full_name']);
    $email = mysqli_real_escape_string($conn,$_POST['email']);
    $department = mysqli_real_escape_string($conn,$_POST['department']);
    $designation = mysqli_real_escape_string($conn,$_POST['designation']);

    mysqli_query($conn,"UPDATE employees
    SET
    full_name='$full_name',
    email='$email',
    department='$department',
    designation='$designation'
    WHERE id='$employee_id'");

    header("Location: profile.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Profile</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<?php include("../dark_mode.php"); ?>

<body class="bg-light">

<div class="container mt-5">

<div class="card shadow">

<div class="card-header bg-primary text-white">

<h3>Edit Profile</h3>

</div>

<div class="card-body">

<form method="POST">

<div class="mb-3">
<label>Full Name</label>
<input type="text" name="full_name" class="form-control"
value="<?php echo $row['full_name']; ?>" required>
</div>

<div class="mb-3">
<label>Email</label>
<input type="email" name="email" class="form-control"
value="<?php echo $row['email']; ?>" required>
</div>

<div class="mb-3">
<label>Department</label>
<input type="text" name="department" class="form-control"
value="<?php echo $row['department']; ?>" required>
</div>

<div class="mb-3">
<label>Designation</label>
<input type="text" name="designation" class="form-control"
value="<?php echo $row['designation']; ?>" required>
</div>

<button type="submit" name="update" class="btn btn-success">
Update Profile
</button>

<a href="profile.php" class="btn btn-secondary">
Back
</a>

</form>

</div>

</div>

</div>

</body>
</html>