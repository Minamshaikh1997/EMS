<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

include("../config/db.php");
include("roles_helper.php");

$departments = mysqli_query($conn, "SELECT * FROM departments ORDER BY department_name ASC");

$employeeCode = '';
$lastEmployee = mysqli_fetch_assoc(mysqli_query($conn, "SELECT employee_id FROM employees ORDER BY id DESC LIMIT 1"));
if ($lastEmployee && !empty($lastEmployee['employee_id'])) {
    $lastNumber = (int) preg_replace('/\D/', '', $lastEmployee['employee_id']);
    $employeeCode = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
} else {
    $employeeCode = '00001';
}

// Get manageable roles for dropdown
$manageableRoles = getManageableRoles($conn);

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
    $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : 'Active';
    $reporting_manager_id = isset($_POST['reporting_manager_id']) ? intval($_POST['reporting_manager_id']) : 0;
    $reporting_supervisor_id = isset($_POST['reporting_supervisor_id']) ? intval($_POST['reporting_supervisor_id']) : 0;
    $reporting_team_lead_id = isset($_POST['reporting_team_lead_id']) ? intval($_POST['reporting_team_lead_id']) : 0;

$photo = "";

if($_FILES['photo']['name']!="")
{
    $photo = time()."_".$_FILES['photo']['name'];

    move_uploaded_file(
        $_FILES['photo']['tmp_name'],
        "../uploads/".$photo
    );
}

    // Ensure columns exist
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS status ENUM('Active','Inactive','Suspended','Terminated') DEFAULT 'Active'");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS reporting_manager_id INT DEFAULT NULL");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS reporting_supervisor_id INT DEFAULT NULL");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS reporting_team_lead_id INT DEFAULT NULL");

    // Check duplicate email
    $exists = mysqli_query($conn, "SELECT id FROM employees WHERE email='$email' LIMIT 1");
    if ($exists && mysqli_num_rows($exists) > 0) {
        $error = "An employee with this email already exists.";
    } else {
        // Ensure unique employee_id
        $eid_check = mysqli_query($conn, "SELECT id FROM employees WHERE employee_id='$employee_id' LIMIT 1");
        if ($eid_check && mysqli_num_rows($eid_check) > 0) {
            $lastEmployee = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM employees ORDER BY id DESC LIMIT 1"));
            $next = $lastEmployee ? $lastEmployee['id'] + 1 : 1;
            $employee_id = str_pad($next,5,'0',STR_PAD_LEFT);
        }

        // Set is_active based on status
        $is_active = ($status == 'Active') ? 1 : 0;

        $sql = "INSERT INTO employees
(employee_id, full_name, email, password, role, photo, department, designation, joining_date, shift_name, shift_start_time, shift_end_time, annual_leave, sick_leave, casual_leave, is_active, status, reporting_manager_id, reporting_supervisor_id, reporting_team_lead_id)
VALUES
('$employee_id','$full_name','$email','$password','$role','$photo','$department','$designation','$joining_date','$shift_name','$shift_start_time','$shift_end_time',7,10,10,$is_active,'$status','$reporting_manager_id','$reporting_supervisor_id','$reporting_team_lead_id')";

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

// Get ALL active employees for reporting dropdowns
$allEmployees = mysqli_query($conn, "SELECT id, employee_id, full_name, role FROM employees WHERE status='Active' ORDER BY full_name ASC");
$managers = $allEmployees;
$supervisors = $allEmployees;
$teamLeads = $allEmployees;
?>
<!DOCTYPE html>
<html>
<head>
<title>Add Employee</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
</head>

<?php include("../dark_mode.php"); ?>

<body class="bg-light">

<div class="container mt-5 mb-5">

<div class="card shadow">

<div class="card-header bg-primary text-white">
<h3><i class="fa fa-user-plus"></i> Add Employee</h3>
</div>

<div class="card-body">

<?php if(isset($error)){ ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php } elseif(isset($success)) { ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php } ?>

<form method="POST" enctype="multipart/form-data">

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">Department</label>
        <select name="department" class="form-control" required>
            <option value="">Select Department</option>
            <?php while($dept = mysqli_fetch_assoc($departments)) { ?>
            <option value="<?php echo $dept['department_name']; ?>"><?php echo $dept['department_name']; ?></option>
            <?php } ?>
        </select>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">Status</label>
        <select name="status" class="form-control" required>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
            <option value="Suspended">Suspended</option>
            <option value="Terminated">Terminated</option>
        </select>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">Full Name</label>
        <input type="text" name="full_name" class="form-control" required>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">Email</label>
        <input type="email" name="email" class="form-control" required>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">Role</label>
        <select name="role" class="form-control" required>
            <option value="">Select Role</option>
            <?php
            // Only show manageable roles
            $allRoles = ['Operations Manager','Supervisor','Team Lead','WFM Executive','Finance Manager','Accountant','Employee'];
            foreach ($allRoles as $r) {
                if (empty($manageableRoles) || in_array($r, $manageableRoles)) {
                    echo "<option value=\"$r\">$r</option>";
                }
            }
            ?>
        </select>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">Employee Code</label>
        <input type="text" name="employee_id" class="form-control" value="<?php echo htmlspecialchars($employeeCode); ?>" readonly required>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">Designation</label>
        <input type="text" name="designation" class="form-control" placeholder="e.g. Senior Developer">
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">Joining Date</label>
        <input type="date" name="joining_date" class="form-control">
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">Shift</label>
        <select name="shift_name" class="form-control" required>
            <option value="Morning">Morning</option>
            <option value="Evening">Evening</option>
            <option value="Night">Night</option>
            <option value="Flexible">Flexible</option>
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">Shift Start</label>
        <input type="time" name="shift_start_time" class="form-control" value="09:00" required>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">Shift End</label>
        <input type="time" name="shift_end_time" class="form-control" value="17:00" required>
    </div>
</div>

<hr>
<h5 class="text-primary"><i class="fa fa-sitemap"></i> Reporting Structure</h5>
<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Reporting Manager</label>
        <select name="reporting_manager_id" class="form-control">
            <option value="">Select Manager</option>
            <?php while($m = mysqli_fetch_assoc($managers)) { ?>
            <option value="<?php echo $m['id']; ?>"><?php echo $m['employee_id']; ?> - <?php echo $m['full_name']; ?> (<?php echo $m['role']; ?>)</option>
            <?php } ?>
        </select>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Reporting Supervisor</label>
        <select name="reporting_supervisor_id" class="form-control">
            <option value="">Select Supervisor</option>
            <?php while($s = mysqli_fetch_assoc($supervisors)) { ?>
            <option value="<?php echo $s['id']; ?>"><?php echo $s['employee_id']; ?> - <?php echo $s['full_name']; ?> (<?php echo $s['role']; ?>)</option>
            <?php } ?>
        </select>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Reporting Team Lead</label>
        <select name="reporting_team_lead_id" class="form-control">
            <option value="">Select Team Lead</option>
            <?php while($t = mysqli_fetch_assoc($teamLeads)) { ?>
            <option value="<?php echo $t['id']; ?>"><?php echo $t['employee_id']; ?> - <?php echo $t['full_name']; ?> (<?php echo $t['role']; ?>)</option>
            <?php } ?>
        </select>
    </div>
</div>

<div class="mb-3">
<label class="form-label fw-bold">Employee Photo</label>
<input type="file" name="photo" class="form-control" accept="image/*">
</div>

<div class="d-grid gap-2 d-md-flex">
    <button type="submit" name="save" class="btn btn-success">
        <i class="fa fa-save"></i> Save Employee
    </button>
    <a href="employee.php" class="btn btn-secondary">
        <i class="fa fa-arrow-left"></i> Back to Employees
    </a>
    <a href="dashboard.php" class="btn btn-primary">
        <i class="fa fa-arrow-left"></i> Back to Dashboard
    </a>
</div>

</form>

</div>

</div>

</div>

</body>
</html>