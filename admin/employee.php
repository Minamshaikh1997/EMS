<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

include("../config/db.php");
include("admincheck_role.php");

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
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Employees - EMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="admin_panel.css" rel="stylesheet">
<style>
/* Page-specific styles only - common styles in admin_panel.css */
.badge-active { background: rgba(16,185,129,.12); color: #059669; }
.badge-inactive { background: rgba(100,116,139,.12); color: var(--secondary); }
.badge-suspended { background: rgba(245,158,11,.12); color: #d97706; }
.badge-terminated { background: rgba(239,68,68,.12); color: #dc2626; }
.emp-avatar-sm { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }
.emp-avatar-placeholder { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), #7c3aed); display: inline-flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 14px; }
.reporting-cell { font-size: 11px; color: var(--gray-500); line-height: 1.6; }
.reporting-cell strong { color: var(--gray-600); }
</style>
</head>
<body>

<!-- Sidebar Backdrop -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="fa-solid fa-building"></i></div>
        <div class="brand-text">
            EMS
            <small>Employee Management</small>
        </div>
    </div>
    <div class="sidebar-user">
        <div class="user-avatar"><?php echo strtoupper(substr($admin_name, 0, 1)); ?></div>
        <div class="user-info">
            <div class="user-name"><?php echo htmlspecialchars($admin_name); ?></div>
            <div class="user-role"><?php echo htmlspecialchars($admin_role ?: 'Administrator'); ?></div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="sidebar-section-title">Main</div>
        <a href="dashboard.php" class="sidebar-link"><i class="fa fa-gauge"></i> Dashboard</a>
        <a href="employee.php" class="sidebar-link active"><i class="fa fa-users"></i> Employees</a>
        <a href="add_employee.php" class="sidebar-link"><i class="fa fa-user-plus"></i> Add Employee</a>
        <a href="leave_requests.php" class="sidebar-link"><i class="fa fa-calendar-check"></i> Leave Requests</a>
        <a href="supervisor_adjustments.php" class="sidebar-link"><i class="fa fa-user-tie"></i> Supervisor Adjustments</a>
        <a href="admin_adjustments.php" class="sidebar-link"><i class="fa fa-shield-alt"></i> Admin Adjustments</a>
        <a href="manage_shifts.php" class="sidebar-link"><i class="fa fa-clock-rotate-left"></i> Manage Shifts</a>
        <a href="attendance_report.php" class="sidebar-link"><i class="fa fa-clock"></i> Attendance</a>
        <a href="reports.php" class="sidebar-link"><i class="fa fa-chart-column"></i> Reports</a>

        <div class="sidebar-section-title">Payroll</div>
        <a href="payroll_dashboard.php" class="sidebar-link"><i class="fa-solid fa-money-bill-wave"></i> Payroll Dashboard</a>
        <a href="generate_payroll.php" class="sidebar-link"><i class="fa fa-file-invoice-dollar"></i> Generate Payroll</a>
        <a href="payroll_history.php" class="sidebar-link"><i class="fa fa-clock-rotate-left"></i> Payroll History</a>
        <a href="salary_components.php" class="sidebar-link"><i class="fa-solid fa-wallet"></i> Salary Components</a>
        <a href="salary_slips.php" class="sidebar-link"><i class="fa-solid fa-file-pdf"></i> Salary Slips</a>
        <a href="payroll_reports.php" class="sidebar-link"><i class="fa-solid fa-chart-line"></i> Payroll Reports</a>
        <a href="salary_structure.php" class="sidebar-link"><i class="fa fa-money-bill-wave"></i> Salary Structure</a>
        <a href="monthly_payroll.php" class="sidebar-link"><i class="fa fa-calendar"></i> Monthly Payroll</a>

        <div class="sidebar-section-title">System</div>
        <a href="add_notice.php" class="sidebar-link"><i class="fa fa-bullhorn"></i> Notices</a>
        <a href="add_holiday.php" class="sidebar-link"><i class="fa fa-plane"></i> Holidays</a>
        <a href="change_password.php" class="sidebar-link"><i class="fa fa-key"></i> Change Password</a>
        <a href="logout.php" class="sidebar-link"><i class="fa fa-right-from-bracket"></i> Logout</a>
    </nav>
</aside>

<!-- Main Content -->
<div class="main-content" id="mainContent">

    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                <i class="fa fa-bars"></i>
            </button>
            <h4>Employees <span>/ Employee List</span></h4>
        </div>
        <div class="header-right">
            <span class="header-date"><i class="fa-regular fa-calendar"></i> <?=date('d M Y')?></span>
            <span class="header-admin-badge"><i class="fa fa-user-shield"></i> <span><?php echo htmlspecialchars($admin_name); ?></span></span>
            <?php $darkModeInTopbar = true; include("../dark_mode.php"); ?>
            <a href="logout.php" class="btn btn-danger btn-sm rounded-pill px-3">
                <i class="fa fa-right-from-bracket"></i> <span>Logout</span>
            </a>
        </div>
    </header>

    <!-- Page Content -->
    <div class="page-content">

        <div class="card-modern">
            <div class="card-header-custom">
                <h5><i class="fa fa-users"></i> Employee List</h5>
                <a href="add_employee.php" class="btn btn-success rounded-pill px-3">
                    <i class="fa fa-user-plus"></i> Add Employee
                </a>
            </div>
            <div class="card-body-custom p-0">
                <!-- Filters -->
                <div class="p-3 border-bottom bg-light">
                    <form method="GET" class="row g-2">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by ID, Name or Email..." value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <select name="department" class="form-control form-control-sm">
                                <option value="">All Departments</option>
                                <?php
                                $deptsForFilter = mysqli_query($conn, "SELECT * FROM departments ORDER BY department_name");
                                while($dept = mysqli_fetch_assoc($deptsForFilter)){ ?>
                                <option value="<?php echo $dept['department_name']; ?>" <?php if(isset($_GET['department']) && $_GET['department']==$dept['department_name']) echo "selected"; ?>>
                                    <?php echo $dept['department_name']; ?>
                                </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-control form-control-sm">
                                <option value="">All Status</option>
                                <option value="Active" <?php if(isset($_GET['status']) && $_GET['status']=='Active') echo 'selected'; ?>>Active</option>
                                <option value="Inactive" <?php if(isset($_GET['status']) && $_GET['status']=='Inactive') echo 'selected'; ?>>Inactive</option>
                                <option value="Suspended" <?php if(isset($_GET['status']) && $_GET['status']=='Suspended') echo 'selected'; ?>>Suspended</option>
                                <option value="Terminated" <?php if(isset($_GET['status']) && $_GET['status']=='Terminated') echo 'selected'; ?>>Terminated</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-primary btn-sm rounded-pill px-3"><i class="fa fa-search"></i> Search</button>
                            <a href="employee.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3"><i class="fa fa-refresh"></i> Reset</a>
                        </div>
                    </form>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-modern">
                        <thead>
                            <tr>
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
                            $statusBadge = 'badge-active';
                            if ($status == 'Inactive') $statusBadge = 'badge-inactive';
                            elseif ($status == 'Suspended') $statusBadge = 'badge-suspended';
                            elseif ($status == 'Terminated') $statusBadge = 'badge-terminated';
                        ?>
                            <tr>
                                <td>
                                    <?php if(!empty($row['photo'])){ ?>
                                    <img src="../uploads/<?php echo $row['photo']; ?>" class="emp-avatar-sm" alt="">
                                    <?php } else { ?>
                                    <span class="emp-avatar-placeholder"><?php echo strtoupper(substr($row['full_name'], 0, 1)); ?></span>
                                    <?php } ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($row['employee_id']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><span class="badge badge-modern" style="background:rgba(6,182,212,.12);color:#0891b2;"><?php echo $row['role'] ?? 'Employee'; ?></span></td>
                                <td><span class="badge-modern <?php echo $statusBadge; ?>"><?php echo $status; ?></span></td>
                                <td><?php echo htmlspecialchars($row['department']); ?></td>
                                <td><?php echo (!empty($row['shift_name']) ? $row['shift_name'] : 'Morning') . ' (' . (!empty($row['shift_start_time']) ? date('H:i', strtotime($row['shift_start_time'])) : '09:00') . ' - ' . (!empty($row['shift_end_time']) ? date('H:i', strtotime($row['shift_end_time'])) : '17:00') . ')'; ?></td>
                                <td><?php echo htmlspecialchars($row['designation']); ?></td>
                                <td class="reporting-cell">
                                    <strong>Manager:</strong> <?php echo getReportingName($conn, $row['reporting_manager_id'] ?? 0); ?><br>
                                    <strong>Supervisor:</strong> <?php echo getReportingName($conn, $row['reporting_supervisor_id'] ?? 0); ?><br>
                                    <strong>Team Lead:</strong> <?php echo getReportingName($conn, $row['reporting_team_lead_id'] ?? 0); ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit_employee.php?id=<?php echo $row['id']; ?>" class="btn btn-outline-primary" title="Edit"><i class="fa fa-edit"></i></a>
                                        <?php if ($status != 'Active') { ?>
                                            <a href="delete_employee.php?id=<?php echo $row['id']; ?>&action=activate" class="btn btn-outline-success" title="Activate"><i class="fa fa-check"></i></a>
                                        <?php } else { ?>
                                            <a href="delete_employee.php?id=<?php echo $row['id']; ?>&action=confirm" class="btn btn-outline-warning" title="Deactivate"><i class="fa fa-ban"></i></a>
                                        <?php } ?>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                        <?php if (mysqli_num_rows($result) == 0) { ?>
                            <tr><td colspan="11" class="text-center py-4 text-muted">No employees found</td></tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
// Sidebar Toggle
const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebarBackdrop = document.getElementById('sidebarBackdrop');

sidebarToggle.addEventListener('click', function() {
    const isMobile = window.matchMedia('(max-width: 991px)').matches;
    if (isMobile) {
        const isOpen = sidebar.classList.toggle('open');
        sidebarBackdrop.classList.toggle('show', isOpen);
    } else {
        document.body.classList.toggle('sidebar-collapsed');
    }
});

sidebarBackdrop.addEventListener('click', function() {
    sidebar.classList.remove('open');
    sidebarBackdrop.classList.remove('show');
});
</script>
</body>
</html>