<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

include("../config/db.php");

$departments = mysqli_query($conn, "SELECT * FROM departments ORDER BY department_name ASC");

$employeeCode = '';
$lastEmployee = mysqli_fetch_assoc(mysqli_query($conn, "SELECT employee_id FROM employees ORDER BY id DESC LIMIT 1"));
if ($lastEmployee && !empty($lastEmployee['employee_id'])) {
    $lastNumber = (int) preg_replace('/\D/', '', $lastEmployee['employee_id']);
    $employeeCode = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
} else {
    $employeeCode = '00001';
}

if(isset($_POST['save']))
{
    $employee_id = !empty($_POST['employee_id']) ? mysqli_real_escape_string($conn, $_POST['employee_id']) : $employeeCode;
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password_raw = $_POST['password'];
    $password = password_hash($password_raw, PASSWORD_DEFAULT);
    $role = isset($_POST['role']) ? mysqli_real_escape_string($conn, $_POST['role']) : 'Employee';
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $designation = mysqli_real_escape_string($conn, $_POST['designation']);
    $joining_date = mysqli_real_escape_string($conn, $_POST['joining_date']);
    $shift_name = !empty($_POST['shift_name']) ? mysqli_real_escape_string($conn, $_POST['shift_name']) : 'Morning';
    $shift_start_time = !empty($_POST['shift_start_time']) ? mysqli_real_escape_string($conn, $_POST['shift_start_time']) : '09:00';
    $shift_end_time = !empty($_POST['shift_end_time']) ? mysqli_real_escape_string($conn, $_POST['shift_end_time']) : '17:00';

$photo = "";

if($_FILES['photo']['name']!="")
{
    $photo = time()."_".$_FILES['photo']['name'];

    move_uploaded_file(
        $_FILES['photo']['tmp_name'],
        "../uploads/".$photo
    );
}

    // Ensure is_active column exists
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1");

    // Check duplicate email
    $exists = mysqli_query($conn, "SELECT id FROM employees WHERE email='$email' LIMIT 1");
    if ($exists && mysqli_num_rows($exists) > 0) {
        $error = "An employee with this email already exists.";
    } else {
        // Ensure unique employee_id
        $eid_check = mysqli_query($conn, "SELECT id FROM employees WHERE employee_id='$employee_id' LIMIT 1");
        if ($eid_check && mysqli_num_rows($eid_check) > 0) {
            // generate a new employee id based on last id
            $lastEmployee = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM employees ORDER BY id DESC LIMIT 1"));
            $next = $lastEmployee ? $lastEmployee['id'] + 1 : 1;
            $employee_id = str_pad($next,5,'0',STR_PAD_LEFT);
        }

        $sql = "INSERT INTO employees
(employee_id, full_name, email, password, role, photo, department, designation, joining_date, shift_name, shift_start_time, shift_end_time, annual_leave, sick_leave, casual_leave, is_active)
VALUES
('$employee_id','$full_name','$email','$password','$role','$photo','$department','$designation','$joining_date','$shift_name','$shift_start_time','$shift_end_time',7,10,10,1)";

        if(mysqli_query($conn,$sql))
        {
            $success = "Employee Added Successfully";
        }
        else
        {
            $error = "Database Error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Add Employee</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<?php include("../dark_mode.php"); ?>

<body class="bg-light">

<div class="container mt-5">

<div class="card">

<div class="card-header bg-primary text-white">
<h3>Add Employee</h3>
</div>

<div class="card-body">

<?php if(isset($error)){ ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php } elseif(isset($success)) { ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php } ?>

<form method="POST" enctype="multipart/form-data">

<div class="mb-3">
<label>Department</label>

<select name="department" class="form-control" required>

<option value="">Select Department</option>

<?php while($dept = mysqli_fetch_assoc($departments)) { ?>

<option value="<?php echo $dept['department_name']; ?>">
    <?php echo $dept['department_name']; ?>
</option>

<?php } ?>

</select>

</div>
<div class="mb-3">
<label>Full Name</label>
<input type="text" name="full_name" class="form-control" required>
</div>

<div class="mb-3">
<label>Email</label>
<input type="email" name="email" class="form-control" required>
</div>

<div class="mb-3">
<label>Password</label>
<input type="password" name="password" class="form-control" required>
</div>

<div class="mb-3">
<label>Role</label>
<select name="role" class="form-control" required>
    <option value="Employee">Employee</option>
    <option value="CEO">CEO</option>
    <option value="Admin">Admin</option>
    <option value="HR Manager">HR Manager</option>
    <option value="HR Executive">HR Executive</option>
    <option value="Operations Manager">Operations Manager</option>
    <option value="Operations Executive">Operations Executive</option>
    <option value="Finance Manager">Finance Manager</option>
    <option value="Accountant">Accountant</option>
    <option value="Sales Manager">Sales Manager</option>
    <option value="Sales Executive">Sales Executive</option>
    <option value="Marketing Manager">Marketing Manager</option>
    <option value="Customer Support Manager">Customer Support Manager</option>
    <option value="Customer Support Agent">Customer Support Agent</option>
    <option value="IT Manager">IT Manager</option>
    <option value="IT Support">IT Support</option>
    <option value="QA Manager">QA Manager</option>
    <option value="QA Executive">QA Executive</option>
    <option value="MIS Executive">MIS Executive</option>
    <option value="Trainer">Trainer</option>
    <option value="WFM Executive">WFM Executive</option>
    <option value="RTA Executive">RTA Executive</option>
    <option value="Team Lead">Team Lead</option>
    <option value="CSE">CSE</option>
</select>
</div>

<div class="mb-3">
<label>Employee Code</label>
<input type="text" name="employee_id" class="form-control" value="<?php echo htmlspecialchars($employeeCode); ?>" readonly required>
</div>

<div class="mb-3">
<label>Designation</label>
<input type="text" name="designation" class="form-control">
</div>

<div class="mb-3">
<label>Joining Date</label>
<input type="date" name="joining_date" class="form-control">
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label>Shift</label>
        <select name="shift_name" class="form-control" required>
            <option value="Morning">Morning</option>
            <option value="Evening">Evening</option>
            <option value="Night">Night</option>
            <option value="Flexible">Flexible</option>
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label>Shift Start</label>
        <input type="time" name="shift_start_time" class="form-control" value="09:00" required>
    </div>
    <div class="col-md-4 mb-3">
        <label>Shift End</label>
        <input type="time" name="shift_end_time" class="form-control" value="17:00" required>
    </div>
</div>

<div class="mb-3">
<label>Employee Photo</label>
<input type="file" name="photo" class="form-control" accept="image/*">

</div>

<button type="submit" name="save" class="btn btn-success">
Save Employee
</button>

<a href="dashboard.php" class="btn btn-secondary">
Back
</a>

</form>

</div>

</div>

</body>
</html>

</div>

</body>
</html>