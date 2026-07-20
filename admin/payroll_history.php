<?php
session_start();

include("admincheck_role.php");
include("../config/db.php");

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.html");
    exit();
}

// Mark Payroll as Paid
if(isset($_GET['paid']))
{
    $id = intval($_GET['paid']);

    mysqli_query($conn,"
    UPDATE payroll
    SET payment_status='Paid'
    WHERE id='$id'
    ");

    header("Location: payroll_history.php");
    exit();
}

$payroll = mysqli_query($conn,"
SELECT
p.*,
e.employee_id,
e.full_name

FROM payroll p

INNER JOIN employees e
ON p.employee_id=e.id

ORDER BY p.payroll_year DESC,
FIELD(
p.payroll_month,
'January','February','March','April','May','June',
'July','August','September','October','November','December'
) DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payroll History - EMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="admin_panel.css" rel="stylesheet">
<style>
:root {
    --primary: #2563eb; --primary-dark: #1d4ed8; --success: #10b981; --warning: #f59e0b; --danger: #ef4444; --info: #06b6d4;
    --gray-50: #f8fafc; --gray-100: #f1f5f9; --gray-200: #e2e8f0; --gray-300: #cbd5e1; --gray-400: #94a3b8; --gray-500: #64748b;
    --gray-600: #475569; --gray-700: #334155; --gray-800: #1e293b; --gray-900: #0f172a;
    --radius: 16px; --radius-sm: 10px;
    --shadow: 0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.06);
    --shadow-md: 0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -1px rgba(0,0,0,.06);
}
body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: var(--gray-50); color: var(--gray-800); overflow-x: hidden; }
.table-modern { margin-bottom: 0; }
.table-modern thead th { font-size: 11px; font-weight: 700; color: var(--gray-500); text-transform: uppercase; letter-spacing: .5px; padding: 12px 16px; border-bottom: 2px solid var(--gray-200); background: var(--gray-50); white-space: nowrap; }
.table-modern tbody td { padding: 12px 16px; font-size: 13px; color: var(--gray-700); border-bottom: 1px solid var(--gray-100); vertical-align: middle; }
.table-modern tbody tr:hover { background: var(--gray-50); }
.table-modern tbody tr:last-child td { border-bottom: none; }
.badge-modern { padding: 4px 12px; border-radius: 100px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; }
.badge-paid { background: rgba(16,185,129,.12); color: #059669; }
.badge-pending { background: rgba(245,158,11,.12); color: #d97706; }
.card-modern { background: white; border-radius: var(--radius); border: 1px solid var(--gray-200); box-shadow: var(--shadow); overflow: hidden; }
.card-modern .card-header-custom { padding: 16px 24px; border-bottom: 1px solid var(--gray-200); display: flex; align-items: center; justify-content: space-between; background: var(--gray-50); flex-wrap: wrap; gap: 12px; }
.card-modern .card-header-custom h5 { font-size: 16px; font-weight: 700; color: var(--gray-800); margin: 0; display: flex; align-items: center; gap: 8px; }
.card-modern .card-header-custom h5 i { color: var(--primary); }
.card-modern .card-body-custom { padding: 20px; }
body.dark-mode { background: #0f172a; color: #e2e8f0; }
.dark-mode .card-modern { background: #1e293b; border-color: rgba(255,255,255,.08); }
.dark-mode .card-modern .card-header-custom { background: rgba(255,255,255,.04); border-color: rgba(255,255,255,.08); }
.dark-mode .card-modern .card-header-custom h5 { color: #e2e8f0; }
.dark-mode .table-modern thead th { color: var(--gray-400); border-color: rgba(255,255,255,.08); background: rgba(255,255,255,.04); }
.dark-mode .table-modern tbody td { color: #cbd5e1; border-color: rgba(255,255,255,.06); }
.dark-mode .table-modern tbody tr:hover { background: rgba(255,255,255,.04); }
@media (max-width: 768px) { .page-content { padding: 16px; } }
</style>
</head>
<body>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="fa-solid fa-building"></i></div>
        <div class="brand-text">EMS <small>Employee Management</small></div>
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
        <a href="employee.php" class="sidebar-link"><i class="fa fa-users"></i> Employees</a>
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
        <a href="payroll_history.php" class="sidebar-link active"><i class="fa fa-clock-rotate-left"></i> Payroll History</a>
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

<div class="main-content" id="mainContent">
    <header class="header">
        <div class="header-left">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><i class="fa fa-bars"></i></button>
            <h4>Payroll History <span>/ All Payroll Records</span></h4>
        </div>
        <div class="header-right">
            <span class="header-date"><i class="fa-regular fa-calendar"></i> <?=date('d M Y')?></span>
            <span class="header-admin-badge"><i class="fa fa-user-shield"></i> <span><?php echo htmlspecialchars($admin_name); ?></span></span>
            <?php $darkModeInTopbar = true; include("../dark_mode.php"); ?>
            <a href="logout.php" class="btn btn-danger btn-sm rounded-pill px-3"><i class="fa fa-right-from-bracket"></i> <span>Logout</span></a>
        </div>
    </header>

    <div class="page-content">
        <div class="card-modern">
            <div class="card-header-custom">
                <h5><i class="fa fa-file-invoice-dollar"></i> Payroll History</h5>
            </div>
            <div class="card-body-custom p-0">
                <div class="table-responsive">
                    <table class="table table-modern">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Name</th>
                                <th>Month</th>
                                <th>Year</th>
                                <th>Gross Salary</th>
                                <th>Net Salary</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while($row=mysqli_fetch_assoc($payroll)){ ?>
                            <tr>
                                <td><span class="fw-bold"><?=htmlspecialchars($row['employee_id'])?></span></td>
                                <td><?=htmlspecialchars($row['full_name'])?></td>
                                <td><?=htmlspecialchars($row['payroll_month'])?></td>
                                <td><?=htmlspecialchars($row['payroll_year'])?></td>
                                <td><?=number_format($row['gross_salary'],2)?></td>
                                <td class="fw-bold"><?=number_format($row['net_salary'],2)?></td>
                                <td>
                                    <?php
                                    $status = $row['payment_status'] ?? 'Pending';
                                    $badgeClass = ($status == "Paid") ? 'badge-paid' : 'badge-pending';
                                    ?>
                                    <span class="badge-modern <?=$badgeClass?>"><?=$status?></span>
                                </td>
                                <td>
                                    <?php if($row['payment_status']=="Pending"){ ?>
                                    <a href="payroll_history.php?paid=<?=$row['id']?>" class="btn btn-outline-success btn-sm" onclick="return confirm('Mark this payroll as Paid?')"><i class="fa fa-check"></i> Mark Paid</a>
                                    <?php }else{ ?>
                                    <span class="text-muted small"><i class="fa fa-check-circle text-success"></i> Completed</span>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                        <?php if(mysqli_num_rows($payroll) == 0): ?>
                            <tr><td colspan="8" class="text-center py-4 text-muted">No payroll records found</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebarBackdrop = document.getElementById('sidebarBackdrop');
sidebarToggle.addEventListener('click', function() {
    const isMobile = window.matchMedia('(max-width: 991px)').matches;
    if (isMobile) { sidebar.classList.toggle('open'); sidebarBackdrop.classList.toggle('show', isOpen); }
    else { document.body.classList.toggle('sidebar-collapsed'); }
});
sidebarBackdrop.addEventListener('click', function() { sidebar.classList.remove('open'); sidebarBackdrop.classList.remove('show'); });
</script>
</body>
</html>