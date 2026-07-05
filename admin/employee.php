<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

include("../config/db.php");

$departments = mysqli_query($conn, "SELECT * FROM departments ORDER BY department_name");

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn,$_GET['search']) : "";
$department = isset($_GET['department']) ? mysqli_real_escape_string($conn,$_GET['department']) : "";
$role = isset($_GET['role']) ? mysqli_real_escape_string($conn,$_GET['role']) : "";

$sql = "SELECT * FROM employees WHERE 1=1";

if($search!="")
{
    $sql .= " AND (
        employee_id LIKE '%$search%'
        OR full_name LIKE '%$search%'
        OR email LIKE '%$search%'
    )";
}

if($department!="")
{
    $sql .= " AND department='$department'";
}

if($role!="")
{
    $sql .= " AND role='$role'";
}

$sql .= " ORDER BY id DESC";

$result = mysqli_query($conn,$sql);

?>

<!DOCTYPE html>
<html>
<head>

<title>Employees</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<?php include("../dark_mode.php"); ?>

<body class="bg-light">

<div class="container mt-5">

<h2>Employee List</h2>

<a href="add_employee.php" class="btn btn-success mb-3">
Add Employee
</a>

<form method="GET" class="mb-3">

<div class="row">

<div class="col-md-4">

<input
type="text"
name="search"
class="form-control"
placeholder="Search by Employee ID, Name or Email"
value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">

</div>

<div class="col-md-3">

<select name="department" class="form-control">

<option value="">All Departments</option>

<?php while($dept = mysqli_fetch_assoc($departments)){ ?>

<option
value="<?php echo $dept['department_name']; ?>"
<?php if(isset($_GET['department']) && $_GET['department']==$dept['department_name']) echo "selected"; ?>>

<?php echo $dept['department_name']; ?>

</option>

<?php } ?>

</select>

</div>

<div class="col-md-2">

<button class="btn btn-primary w-100">
Search
</button>

</div>

<div class="col-md-2">

<a href="employee.php" class="btn btn-secondary w-100">
Reset
</a>

</div>

</div>

</form>
<table class="table table-bordered table-striped">

<thead class="table-dark">

<tr>

<th>ID</th>
<th>Status</th>
<th>Employee ID</th>
<th>Name</th>
<th>Photo</th>
<th>Email</th>
<th>Role</th>
<th>Department</th>
<th>Shift</th>
<th>Designation</th>
<th>Action</th>


</tr>

</thead>

<tbody>

<?php while($row=mysqli_fetch_assoc($result)){ ?>

<tr>

<td><?php echo $row['id']; ?></td>
<td><?php echo (isset($row['is_active']) && $row['is_active']=='0') ? '<span class="badge bg-danger">Inactive</span>' : '<span class="badge bg-success">Active</span>'; ?></td>
<td><?php echo $row['employee_id']; ?></td>
<td><?php echo $row['full_name']; ?></td>
<td>
<?php
if(!empty($row['photo']))
{
?>
<img src="../uploads/<?php echo $row['photo']; ?>"
width="60"
height="60"
style="border-radius:50%; object-fit:cover;">
<?php
}
else
{
echo "No Photo";
}
?>
</td>
<td><?php echo $row['email']; ?></td>
<td><?php echo isset($row['role']) ? $row['role'] : 'Employee'; ?></td>
<td><?php echo $row['department']; ?></td>
<td><?php echo (!empty($row['shift_name']) ? $row['shift_name'] : 'Morning') . ' (' . (!empty($row['shift_start_time']) ? date('H:i', strtotime($row['shift_start_time'])) : '09:00') . ' - ' . (!empty($row['shift_end_time']) ? date('H:i', strtotime($row['shift_end_time'])) : '17:00') . ')'; ?></td>
<td><?php echo $row['designation']; ?></td>

<td>

<a href="edit_employee.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Edit</a>

<?php if (isset($row['is_active']) && $row['is_active']=='0') { ?>
    <a href="delete_employee.php?id=<?php echo $row['id']; ?>&action=activate" class="btn btn-success btn-sm">Activate</a>
<?php } else { ?>
    <a href="delete_employee.php?id=<?php echo $row['id']; ?>&action=confirm" class="btn btn-warning btn-sm">Deactivate</a>
<?php } ?>

</td>

</tr>

<?php } ?>

</tbody>

</table>

<a href="dashboard.php" class="btn btn-secondary">
Back Dashboard
</a>

</div>

</body>
</html>