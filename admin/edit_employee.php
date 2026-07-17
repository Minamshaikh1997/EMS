<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

include("../config/db.php");
include("roles_helper.php");

// Load Departments
$departments = mysqli_query($conn, "SELECT * FROM departments ORDER BY department_name");

// Get Employee ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Load Employee Data
$result = mysqli_query($conn, "SELECT * FROM employees WHERE id='$id'");
$row = mysqli_fetch_assoc($result);

if (!$row) {
    header("Location: employee.php");
    exit();
}

// Get manageable roles
$manageableRoles = getManageableRoles($conn);

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
    $status = isset($_POST['status']) ? $_POST['status'] : 'Active';
    $reporting_manager_id = isset($_POST['reporting_manager_id']) ? intval($_POST['reporting_manager_id']) : 0;
    $reporting_supervisor_id = isset($_POST['reporting_supervisor_id']) ? intval($_POST['reporting_supervisor_id']) : 0;
    $reporting_team_lead_id = isset($_POST['reporting_team_lead_id']) ? intval($_POST['reporting_team_lead_id']) : 0;

    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS shift_name VARCHAR(100) DEFAULT 'Morning'");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS shift_start_time TIME DEFAULT '09:00:00'");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS shift_end_time TIME DEFAULT '17:00:00'");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS status ENUM('Active','Inactive','Suspended','Terminated') DEFAULT 'Active'");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS reporting_manager_id INT DEFAULT NULL");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS reporting_supervisor_id INT DEFAULT NULL");
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS reporting_team_lead_id INT DEFAULT NULL");

    $is_active = ($status == 'Active') ? 1 : 0;

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
        casual_leave='$casual_leave',
        status='$status',
        is_active='$is_active',
        reporting_manager_id='$reporting_manager_id',
        reporting_supervisor_id='$reporting_supervisor_id',
        reporting_team_lead_id='$reporting_team_lead_id'
        WHERE id='$id'
    ");

    echo "<script>
    alert('Employee Updated Successfully');
    window.location='employee.php';
    </script>";

    exit();
}

// Get ALL employees for reporting dropdowns
$allEmployees = mysqli_query($conn, "SELECT id, employee_id, full_name, role FROM employees ORDER BY full_name ASC");
$managers = $allEmployees;
$supervisors = $allEmployees;
$teamLeads = $allEmployees;
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Employee</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
</head>

<?php include("../dark_mode.php"); ?>

<body class="bg-light">

<div class="container mt-5 mb-5">

<div class="card shadow">

<div class="card-header bg-warning">
<h3><i class="fa fa-edit"></i> Edit Employee</h3>
</div>

<div class="card-body">

<form method="POST">

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">Employee ID</label>
        <input type="text" name="employee_id" class="form-control" value="<?php echo $row['employee_id']; ?>" required>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">Full Name</label>
        <input type="text" name="full_name" class="form-control" value="<?php echo $row['full_name']; ?>" required>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">Email</label>
        <input type="email" name="email" class="form-control" value="<?php echo $row['email']; ?>" required>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">Role</label>
        <select name="role" class="form-control" required>
            <?php
            // Make sure current role is always in the list
            $allRoles = ['Super Admin','Admin','Operations Manager','Supervisor','Team Lead','WFM Executive','Finance Manager','Accountant','Employee'];
            $currentRole = $row['role'] ?? 'Employee';
            // Add current role if it's not in the standard list (e.g. custom role)
            if (!in_array($currentRole, $allRoles)) {
                $allRoles[] = $currentRole;
            }
            foreach ($allRoles as $r) {
                $sel = ($currentRole == $r) ? 'selected' : '';
                if (empty($manageableRoles) || in_array($r, $manageableRoles) || $currentRole == $r) {
                    echo "<option value=\"$r\" $sel>$r</option>";
                }
            }
            ?>
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">Department</label>
        <select name="department" class="form-control" required>
            <?php while($dept = mysqli_fetch_assoc($departments)){ ?>
            <option value="<?php echo $dept['department_name']; ?>" <?php if($row['department']==$dept['department_name']) echo "selected"; ?>>
                <?php echo $dept['department_name']; ?>
            </option>
            <?php } ?>
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">Status</label>
        <select name="status" class="form-control" required>
            <option value="Active" <?php echo ($row['status'] ?? 'Active') == 'Active' ? 'selected' : ''; ?>>Active</option>
            <option value="Inactive" <?php echo ($row['status'] ?? '') == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
            <option value="Suspended" <?php echo ($row['status'] ?? '') == 'Suspended' ? 'selected' : ''; ?>>Suspended</option>
            <option value="Terminated" <?php echo ($row['status'] ?? '') == 'Terminated' ? 'selected' : ''; ?>>Terminated</option>
        </select>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">Designation</label>
        <input type="text" name="designation" class="form-control" value="<?php echo $row['designation']; ?>">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">Joining Date</label>
        <input type="date" name="joining_date" class="form-control" value="<?php echo $row['joining_date']; ?>">
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">Shift</label>
        <select name="shift_name" class="form-control" required>
            <option value="Morning" <?php if(($row['shift_name'] ?? '')=='Morning') echo 'selected'; ?>>Morning</option>
            <option value="Evening" <?php if(($row['shift_name'] ?? '')=='Evening') echo 'selected'; ?>>Evening</option>
            <option value="Night" <?php if(($row['shift_name'] ?? '')=='Night') echo 'selected'; ?>>Night</option>
            <option value="Flexible" <?php if(($row['shift_name'] ?? '')=='Flexible') echo 'selected'; ?>>Flexible</option>
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">Shift Start</label>
        <input type="time" name="shift_start_time" class="form-control" value="<?php echo isset($row['shift_start_time']) ? $row['shift_start_time'] : '09:00'; ?>" required>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">Shift End</label>
        <input type="time" name="shift_end_time" class="form-control" value="<?php echo isset($row['shift_end_time']) ? $row['shift_end_time'] : '17:00'; ?>" required>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">Annual Leave</label>
        <input type="number" name="annual_leave" class="form-control" value="<?php echo $row['annual_leave']; ?>">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">Sick Leave</label>
        <input type="number" name="sick_leave" class="form-control" value="<?php echo $row['sick_leave']; ?>">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">Casual Leave</label>
        <input type="number" name="casual_leave" class="form-control" value="<?php echo $row['casual_leave']; ?>">
    </div>
</div>

<hr>
<h5 class="text-primary"><i class="fa fa-sitemap"></i> Reporting Structure</h5>
<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Reporting Manager</label>
        <select name="reporting_manager_id" class="form-control">
            <option value="">Select Manager</option>
            <?php while($m = mysqli_fetch_assoc($managers)) {
                $sel = ($m['id'] == ($row['reporting_manager_id'] ?? 0)) ? 'selected' : '';
            ?>
            <option value="<?php echo $m['id']; ?>" <?php echo $sel; ?>><?php echo $m['employee_id']; ?> - <?php echo $m['full_name']; ?> (<?php echo $m['role']; ?>)</option>
            <?php } ?>
        </select>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Reporting Supervisor</label>
        <select name="reporting_supervisor_id" class="form-control">
            <option value="">Select Supervisor</option>
            <?php while($s = mysqli_fetch_assoc($supervisors)) {
                $sel = ($s['id'] == ($row['reporting_supervisor_id'] ?? 0)) ? 'selected' : '';
            ?>
            <option value="<?php echo $s['id']; ?>" <?php echo $sel; ?>><?php echo $s['employee_id']; ?> - <?php echo $s['full_name']; ?> (<?php echo $s['role']; ?>)</option>
            <?php } ?>
        </select>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Reporting Team Lead</label>
        <select name="reporting_team_lead_id" class="form-control">
            <option value="">Select Team Lead</option>
            <?php while($t = mysqli_fetch_assoc($teamLeads)) {
                $sel = ($t['id'] == ($row['reporting_team_lead_id'] ?? 0)) ? 'selected' : '';
            ?>
            <option value="<?php echo $t['id']; ?>" <?php echo $sel; ?>><?php echo $t['employee_id']; ?> - <?php echo $t['full_name']; ?> (<?php echo $t['role']; ?>)</option>
            <?php } ?>
        </select>
    </div>
</div>

<br>

<div class="d-grid gap-2 d-md-flex">
    <button type="submit" name="update" class="btn btn-primary">
        <i class="fa fa-save"></i> Update Employee
    </button>
    <a href="employee.php" class="btn btn-secondary">
        <i class="fa fa-arrow-left"></i> Back to Employees
    </a>
</div>

</form>

</div>

</div>

</div>

</body>
</html>