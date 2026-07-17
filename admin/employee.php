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

// Get reporting names for display
function getReportingName($conn, $id) {
    if (!$id) return '—';
    $q = mysqli_query($conn, "SELECT employee_id, full_name FROM employees WHERE id='$id' LIMIT 1");
    if ($q && mysqli_num_rows($q) > 0) {
        $r = mysqli_fetch_assoc($q);
        return $r['employee_id'] . ' - ' . $r['full_name'];
    }
    return '—';
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Employees</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<style>
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
.table th { white-space: nowrap; }
.reporting-cell { font-size: 12px; color: #666; }
</style>
</head>

<?php include("../dark_mode.php"); ?>

<body class="bg-light">

<div class="container-fluid mt-4 px-4">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="fa fa-users text-primary"></i> Employee List</h2>
    <a href="add_employee.php" class="btn btn-success">
        <i class="fa fa-user-plus"></i> Add Employee
    </a>
</div>

<form method="GET" class="mb-3">
<div class="row g-2">
    <div class="col-md-4">
        <input type="text" name="search" class="form-control" placeholder="Search by ID, Name or Email" value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">
    </div>
    <div class="col-md-3">
        <select name="department" class="form-control">
            <option value="">All Departments</option>
            <?php while($dept = mysqli_fetch_assoc($departments)){ ?>
            <option value="<?php echo $dept['department_name']; ?>" <?php if(isset($_GET['department']) && $_GET['department']==$dept['department_name']) echo "selected"; ?>>
                <?php echo $dept['department_name']; ?>
            </option>
            <?php } ?>
        </select>
    </div>
    <div class="col-md-2">
        <select name="status" class="form-control">
            <option value="">All Status</option>
            <option value="Active" <?php if(isset($_GET['status']) && $_GET['status']=='Active') echo 'selected'; ?>>Active</option>
            <option value="Inactive" <?php if(isset($_GET['status']) && $_GET['status']=='Inactive') echo 'selected'; ?>>Inactive</option>
            <option value="Suspended" <?php if(isset($_GET['status']) && $_GET['status']=='Suspended') echo 'selected'; ?>>Suspended</option>
            <option value="Terminated" <?php if(isset($_GET['status']) && $_GET['status']=='Terminated') echo 'selected'; ?>>Terminated</option>
        </select>
    </div>
    <div class="col-md-2">
        <button class="btn btn-primary w-100"><i class="fa fa-search"></i> Search</button>
    </div>
    <div class="col-md-1">
        <a href="employee.php" class="btn btn-secondary w-100"><i class="fa fa-refresh"></i></a>
    </div>
</div>
</form>

<div class="table-responsive">
<table class="table table-bordered table-hover align-middle">
<thead class="table-dark">
    <tr>
        <th>ID</th>
        <th>Photo</th>
        <th>Emp ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Status</th>
        <th>Department</th>
        <th>Shift</th>
        <th>Designation</th>
        <th>Reporting To</th>
        <th>Action</th>
    </tr>
</thead>
<tbody>
<?php while($row=mysqli_fetch_assoc($result)){ 
    $status = $row['status'] ?? 'Active';
    $statusBadge = 'bg-success';
    if ($status == 'Inactive') $statusBadge = 'bg-secondary';
    elseif ($status == 'Suspended') $statusBadge = 'bg-warning text-dark';
    elseif ($status == 'Terminated') $statusBadge = 'bg-danger';
?>
    <tr>
        <td><?php echo $row['id']; ?></td>
        <td>
            <?php if(!empty($row['photo'])){ ?>
            <img src="../uploads/<?php echo $row['photo']; ?>" width="50" height="50" style="border-radius:50%; object-fit:cover;">
            <?php } else { ?>
            <span class="text-muted"><i class="fa fa-user-circle fa-2x"></i></span>
            <?php } ?>
        </td>
        <td><strong><?php echo $row['employee_id']; ?></strong></td>
        <td><?php echo $row['full_name']; ?></td>
        <td><?php echo $row['email']; ?></td>
        <td><span class="badge bg-info text-dark"><?php echo $row['role'] ?? 'Employee'; ?></span></td>
        <td><span class="badge <?php echo $statusBadge; ?>"><?php echo $status; ?></span></td>
        <td><?php echo $row['department']; ?></td>
        <td><?php echo (!empty($row['shift_name']) ? $row['shift_name'] : 'Morning') . ' (' . (!empty($row['shift_start_time']) ? date('H:i', strtotime($row['shift_start_time'])) : '09:00') . ' - ' . (!empty($row['shift_end_time']) ? date('H:i', strtotime($row['shift_end_time'])) : '17:00') . ')'; ?></td>
        <td><?php echo $row['designation']; ?></td>
        <td class="small">
            <strong>Manager:</strong> <?php echo getReportingName($conn, $row['reporting_manager_id'] ?? 0); ?><br>
            <strong>Supervisor:</strong> <?php echo getReportingName($conn, $row['reporting_supervisor_id'] ?? 0); ?><br>
            <strong>Team Lead:</strong> <?php echo getReportingName($conn, $row['reporting_team_lead_id'] ?? 0); ?>
        </td>
        <td>
            <a href="edit_employee.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm"><i class="fa fa-edit"></i></a>
            <?php if ($status != 'Active') { ?>
                <a href="delete_employee.php?id=<?php echo $row['id']; ?>&action=activate" class="btn btn-success btn-sm"><i class="fa fa-check"></i></a>
            <?php } else { ?>
                <a href="delete_employee.php?id=<?php echo $row['id']; ?>&action=confirm" class="btn btn-warning btn-sm"><i class="fa fa-ban"></i></a>
            <?php } ?>
        </td>
    </tr>
<?php } ?>
<?php if (mysqli_num_rows($result) == 0) { ?>
    <tr><td colspan="12" class="text-center text-muted py-4">No employees found</td></tr>
<?php } ?>
</tbody>
</table>
</div>

<a href="dashboard.php" class="btn btn-primary"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>

</div>
</body>
</html>
