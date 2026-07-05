```php
<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

include("../config/db.php");

// Load Departments
$departments = mysqli_query($conn, "SELECT * FROM departments ORDER BY department_name");

// Get Employee ID
$id = $_GET['id'];

// Load Employee Data
$result = mysqli_query($conn, "SELECT * FROM employees WHERE id='$id'");
$row = mysqli_fetch_assoc($result);

// Update Employee
if(isset($_POST['update']))
{
    $employee_id   = $_POST['employee_id'];
    $full_name     = $_POST['full_name'];
    $email         = $_POST['email'];
    $role          = isset($_POST['role']) ? $_POST['role'] : 'Employee';
    $department    = $_POST['department'];
    $designation   = $_POST['designation'];
    $joining_date  = $_POST['joining_date'];
    $shift_name    = !empty($_POST['shift_name']) ? $_POST['shift_name'] : 'Morning';
    $shift_start_time = !empty($_POST['shift_start_time']) ? $_POST['shift_start_time'] : '09:00';
    $shift_end_time   = !empty($_POST['shift_end_time']) ? $_POST['shift_end_time'] : '17:00';
    $annual_leave  = $_POST['annual_leave'];
    $sick_leave    = $_POST['sick_leave'];
    $casual_leave  = $_POST['casual_leave'];

    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS shift_name VARCHAR(100) DEFAULT 'Morning'");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS shift_start_time TIME DEFAULT '09:00:00'");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS shift_end_time TIME DEFAULT '17:00:00'");

    mysqli_query($conn,"UPDATE employees SET
        employee_id='$employee_id',
        full_name='$full_name',
        email='$email',
        role='$role',
        department='$department',
        designation='$designation',
        joining_date='$joining_date',
        shift_name='$shift_name',
        shift_start_time='$shift_start_time',
        shift_end_time='$shift_end_time',
        annual_leave='$annual_leave',
        sick_leave='$sick_leave',
        casual_leave='$casual_leave'
        WHERE id='$id'
    ");

    echo "<script>
    alert('Employee Updated Successfully');
    window.location='employee.php';
    </script>";

    exit();
}
?>

<!DOCTYPE html>
<html>
<head>

<title>Edit Employee</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<?php include("../dark_mode.php"); ?>

<body class="bg-light">

<div class="container mt-5">

<div class="card shadow">

<div class="card-header bg-warning">
<h3>Edit Employee</h3>
</div>

<div class="card-body">

<form method="POST">

<div class="mb-3">
<label>Employee ID</label>
<input type="text" name="employee_id" class="form-control"
value="<?php echo $row['employee_id']; ?>" required>
</div>

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
<label>Role</label>
<select name="role" class="form-control" required>
    <option value="Employee" <?php if($row['role']=='Employee') echo 'selected'; ?>>Employee</option>
    <option value="CEO" <?php if($row['role']=='CEO') echo 'selected'; ?>>CEO</option>
    <option value="Admin" <?php if($row['role']=='Admin') echo 'selected'; ?>>Admin</option>
    <option value="HR Manager" <?php if($row['role']=='HR Manager') echo 'selected'; ?>>HR Manager</option>
    <option value="HR Executive" <?php if($row['role']=='HR Executive') echo 'selected'; ?>>HR Executive</option>
    <option value="Operations Manager" <?php if($row['role']=='Operations Manager') echo 'selected'; ?>>Operations Manager</option>
    <option value="Operations Executive" <?php if($row['role']=='Operations Executive') echo 'selected'; ?>>Operations Executive</option>
    <option value="Finance Manager" <?php if($row['role']=='Finance Manager') echo 'selected'; ?>>Finance Manager</option>
    <option value="Accountant" <?php if($row['role']=='Accountant') echo 'selected'; ?>>Accountant</option>
    <option value="Sales Manager" <?php if($row['role']=='Sales Manager') echo 'selected'; ?>>Sales Manager</option>
    <option value="Sales Executive" <?php if($row['role']=='Sales Executive') echo 'selected'; ?>>Sales Executive</option>
    <option value="Marketing Manager" <?php if($row['role']=='Marketing Manager') echo 'selected'; ?>>Marketing Manager</option>
    <option value="Customer Support Manager" <?php if($row['role']=='Customer Support Manager') echo 'selected'; ?>>Customer Support Manager</option>
    <option value="Customer Support Agent" <?php if($row['role']=='Customer Support Agent') echo 'selected'; ?>>Customer Support Agent</option>
    <option value="IT Manager" <?php if($row['role']=='IT Manager') echo 'selected'; ?>>IT Manager</option>
    <option value="IT Support" <?php if($row['role']=='IT Support') echo 'selected'; ?>>IT Support</option>
    <option value="QA Manager" <?php if($row['role']=='QA Manager') echo 'selected'; ?>>QA Manager</option>
    <option value="QA Executive" <?php if($row['role']=='QA Executive') echo 'selected'; ?>>QA Executive</option>
    <option value="MIS Executive" <?php if($row['role']=='MIS Executive') echo 'selected'; ?>>MIS Executive</option>
    <option value="Trainer" <?php if($row['role']=='Trainer') echo 'selected'; ?>>Trainer</option>
    <option value="WFM Executive" <?php if($row['role']=='WFM Executive') echo 'selected'; ?>>WFM Executive</option>
    <option value="RTA Executive" <?php if($row['role']=='RTA Executive') echo 'selected'; ?>>RTA Executive</option>
    <option value="Team Lead" <?php if($row['role']=='Team Lead') echo 'selected'; ?>>Team Lead</option>
    <option value="CSE" <?php if($row['role']=='CSE') echo 'selected'; ?>>CSE</option>
</select>
</div>

<div class="mb-3">
<label>Department</label>

<select name="department" class="form-control" required>

<?php while($dept = mysqli_fetch_assoc($departments)){ ?>

<option
value="<?php echo $dept['department_name']; ?>"
<?php if($row['department']==$dept['department_name']) echo "selected"; ?>>

<?php echo $dept['department_name']; ?>

</option>

<?php } ?>

</select>

</div>

<div class="mb-3">
<label>Designation</label>
<input type="text" name="designation" class="form-control"
value="<?php echo $row['designation']; ?>">
</div>

<div class="mb-3">
<label>Joining Date</label>
<input type="date"
name="joining_date"
class="form-control"
value="<?php echo $row['joining_date']; ?>">
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label>Shift</label>
        <select name="shift_name" class="form-control" required>
            <option value="Morning" <?php if(($row['shift_name'] ?? '')=='Morning') echo 'selected'; ?>>Morning</option>
            <option value="Evening" <?php if(($row['shift_name'] ?? '')=='Evening') echo 'selected'; ?>>Evening</option>
            <option value="Night" <?php if(($row['shift_name'] ?? '')=='Night') echo 'selected'; ?>>Night</option>
            <option value="Flexible" <?php if(($row['shift_name'] ?? '')=='Flexible') echo 'selected'; ?>>Flexible</option>
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label>Shift Start</label>
        <input type="time" name="shift_start_time" class="form-control" value="<?php echo isset($row['shift_start_time']) ? $row['shift_start_time'] : '09:00'; ?>" required>
    </div>
    <div class="col-md-4 mb-3">
        <label>Shift End</label>
        <input type="time" name="shift_end_time" class="form-control" value="<?php echo isset($row['shift_end_time']) ? $row['shift_end_time'] : '17:00'; ?>" required>
    </div>
</div>

<div class="row">

<div class="col-md-4">
<label>Annual Leave</label>
<input type="number"
name="annual_leave"
class="form-control"
value="<?php echo $row['annual_leave']; ?>">
</div>

<div class="col-md-4">
<label>Sick Leave</label>
<input type="number"
name="sick_leave"
class="form-control"
value="<?php echo $row['sick_leave']; ?>">
</div>

<div class="col-md-4">
<label>Casual Leave</label>
<input type="number"
name="casual_leave"
class="form-control"
value="<?php echo $row['casual_leave']; ?>">
</div>

</div>

<br>

<button type="submit" name="update" class="btn btn-primary">
Update Employee
</button>

<a href="employee.php" class="btn btn-secondary">
Back
</a>

</form>

</div>

</div>

</div>

</body>
</html>
```
