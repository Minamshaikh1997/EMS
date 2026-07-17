<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("admincheck_role.php");
include("../config/db.php");

// Dashboard Statistics
$totalEmployees = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM employees"))['total'];
$activeEmployees = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM employees WHERE status='Active' AND is_active=1"))['total'];
$inactiveEmployees = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM employees WHERE status!='Active' OR is_active=0"))['total'];

// Employee status list for table
$employeeStatusList = mysqli_query($conn, "
    SELECT id, employee_id, full_name, department, designation, status, is_active
    FROM employees
    ORDER BY FIELD(status, 'Active', 'Inactive'), is_active DESC, full_name ASC
");

// Managers who can change employee status (Super Admin, Admin, Operations Manager)
$canManageStatus = in_array($admin_role, ['Super Admin', 'Admin', 'Operations Manager']);
$totalLeaves = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM leave_requests"))['total'];
$approvedLeaves = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM leave_requests WHERE status='Approved'"))['total'];
$pendingLeaves = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM leave_requests WHERE status='Pending'"))['total'];
$rejectedLeaves = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM leave_requests WHERE status='Rejected'"))['total'];
$todayAttendance = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM attendance WHERE attendance_date=CURDATE()"))['total'];

// Adjustment Statistics
$totalAdjustments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM attendance_adjustments"))['total'];
$pendingAdjustments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM attendance_adjustments WHERE status='Pending'"))['total'];
$approvedAdjustments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM attendance_adjustments WHERE status='Approved'"))['total'];
$rejectedAdjustments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM attendance_adjustments WHERE status='Rejected'"))['total'];

/* Latest Employees */
$latestEmployees = mysqli_query($conn, "
SELECT employee_id, full_name, department, joining_date
FROM employees
ORDER BY id DESC
LIMIT 5
");

/* Latest Leave Requests */
$latestLeaves = mysqli_query($conn, "
SELECT e.full_name, lr.leave_type, lr.status
FROM leave_requests lr
INNER JOIN employees e ON lr.employee_id = e.id
ORDER BY lr.id DESC
LIMIT 5
");

/* Latest Notices */
$latestNotices = mysqli_query($conn, "
SELECT *
FROM notices
ORDER BY id DESC
LIMIT 5
");

/* Upcoming Holidays */
$upcomingHolidays = mysqli_query($conn, "
SELECT *
FROM holidays
WHERE holiday_date >= CURDATE()
ORDER BY holiday_date ASC
LIMIT 5
");

/* Latest Adjustment Requests */
$latestAdjustments = mysqli_query($conn, "
SELECT a.*, e.full_name, e.department,
       s.name AS supervisor_name,
       ad.name AS admin_name
FROM attendance_adjustments a
INNER JOIN employees e ON a.employee_id = e.id
LEFT JOIN admin s ON a.supervisor_id = s.id
LEFT JOIN admin ad ON a.admin_id = ad.id
ORDER BY a.id DESC
LIMIT 5
");

// Department chart data
$deptChart = mysqli_query($conn, "SELECT department, COUNT(*) total FROM employees GROUP BY department");
$labels = []; $values = [];
while($r = mysqli_fetch_assoc($deptChart)){ $labels[] = $r['department'] ?: 'Unassigned'; $values[] = (int)$r['total']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - EMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="admin_panel.css" rel="stylesheet">
<style>
:root {
    --primary: #2563eb;
    --primary-dark: #1d4ed8;
    --primary-light: #3b82f6;
    --secondary: #64748b;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --info: #06b6d4;
    --dark: #0f172a;
    --gray-50: #f8fafc;
    --gray-100: #f1f5f9;
    --gray-200: #e2e8f0;
    --gray-300: #cbd5e1;
    --gray-400: #94a3b8;
    --gray-500: #64748b;
    --gray-600: #475569;
    --gray-700: #334155;
    --gray-800: #1e293b;
    --gray-900: #0f172a;
    --sidebar-width: 270px;
    --header-height: 70px;
    --radius: 16px;
    --radius-sm: 10px;
    --shadow: 0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.06);
    --shadow-md: 0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -1px rgba(0,0,0,.06);
    --shadow-lg: 0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -2px rgba(0,0,0,.05);
    --shadow-xl: 0 20px 25px -5px rgba(0,0,0,.1), 0 10px 10px -5px rgba(0,0,0,.04);
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--gray-50);
    color: var(--gray-800);
    overflow-x: hidden;
}

/* ===== SIDEBAR ===== */
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: var(--sidebar-width);
    height: 100vh;
    background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
    z-index: 1050;
    transition: transform .3s cubic-bezier(.4,0,.2,1);
    overflow-y: auto;
    overflow-x: hidden;
    border-right: 1px solid rgba(255,255,255,.05);
}

.sidebar::-webkit-scrollbar { width: 4px; }
.sidebar::-webkit-scrollbar-track { background: transparent; }
.sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 4px; }

.sidebar-brand {
    padding: 20px 24px;
    border-bottom: 1px solid rgba(255,255,255,.08);
    display: flex;
    align-items: center;
    gap: 12px;
}

.sidebar-brand .brand-icon {
    width: 42px;
    height: 42px;
    background: linear-gradient(135deg, var(--primary), #7c3aed);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: white;
    flex-shrink: 0;
}

.sidebar-brand .brand-text {
    font-size: 18px;
    font-weight: 700;
    color: white;
    line-height: 1.2;
}

.sidebar-brand .brand-text small {
    display: block;
    font-size: 11px;
    font-weight: 400;
    color: var(--gray-400);
    letter-spacing: .5px;
}

.sidebar-user {
    padding: 16px 20px;
    border-bottom: 1px solid rgba(255,255,255,.08);
    display: flex;
    align-items: center;
    gap: 12px;
}

.sidebar-user .user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--primary), #7c3aed);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 16px;
    flex-shrink: 0;
}

.sidebar-user .user-info { min-width: 0; }
.sidebar-user .user-name { font-size: 14px; font-weight: 600; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sidebar-user .user-role { font-size: 11px; color: var(--gray-400); }

.sidebar-nav { padding: 12px 0; }

.sidebar-section-title {
    padding: 16px 24px 6px;
    font-size: 11px;
    font-weight: 700;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: 1px;
}

.sidebar-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 24px;
    color: var(--gray-300);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all .2s ease;
    position: relative;
    border-left: 3px solid transparent;
}

.sidebar-link i {
    width: 20px;
    text-align: center;
    font-size: 15px;
    flex-shrink: 0;
}

.sidebar-link:hover {
    background: rgba(255,255,255,.06);
    color: white;
}

.sidebar-link.active {
    background: rgba(37,99,235,.15);
    color: var(--primary-light);
    border-left-color: var(--primary);
}

.sidebar-link.active::before {
    content: '';
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--primary);
}

/* ===== MAIN CONTENT ===== */
.main-content {
    margin-left: var(--sidebar-width);
    min-height: 100vh;
    transition: margin-left .3s cubic-bezier(.4,0,.2,1);
}

/* ===== HEADER ===== */
.header {
    background: rgba(255,255,255,.95);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--gray-200);
    padding: 0 32px;
    height: var(--header-height);
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 1020;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.header-left h4 {
    font-size: 18px;
    font-weight: 700;
    color: var(--gray-900);
    margin: 0;
}

.header-left h4 span { color: var(--gray-500); font-weight: 400; }

.header-right {
    display: flex;
    align-items: center;
    gap: 12px;
}

.header-date {
    font-size: 13px;
    color: var(--gray-500);
    font-weight: 500;
    padding: 6px 14px;
    background: var(--gray-100);
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.header-admin-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px 6px 10px;
    background: linear-gradient(135deg, rgba(37,99,235,.1), rgba(124,58,237,.1));
    border-radius: 100px;
    font-size: 13px;
    font-weight: 600;
    color: var(--primary);
}

.header-admin-badge i { font-size: 14px; }

/* ===== PAGE CONTENT ===== */
.page-content {
    padding: 28px 32px;
}

/* ===== STATS CARDS ===== */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: white;
    border-radius: var(--radius);
    padding: 20px;
    box-shadow: var(--shadow);
    transition: all .3s ease;
    position: relative;
    overflow: hidden;
    border: 1px solid var(--gray-200);
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.stat-card .stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    margin-bottom: 14px;
}

.stat-card .stat-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: .5px;
    margin-bottom: 4px;
}

.stat-card .stat-value {
    font-size: 28px;
    font-weight: 800;
    color: var(--gray-900);
    line-height: 1.2;
}

.stat-card .stat-trend {
    position: absolute;
    top: 16px;
    right: 16px;
    font-size: 12px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 6px;
}

.stat-primary .stat-icon { background: rgba(37,99,235,.12); color: var(--primary); }
.stat-success .stat-icon { background: rgba(16,185,129,.12); color: var(--success); }
.stat-info .stat-icon { background: rgba(6,182,212,.12); color: var(--info); }
.stat-warning .stat-icon { background: rgba(245,158,11,.12); color: var(--warning); }
.stat-danger .stat-icon { background: rgba(239,68,68,.12); color: var(--danger); }
.stat-secondary .stat-icon { background: rgba(100,116,139,.12); color: var(--secondary); }

/* ===== SECTION HEADER ===== */
.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.section-header h5 {
    font-size: 16px;
    font-weight: 700;
    color: var(--gray-900);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.section-header h5 i { color: var(--primary); }

/* ===== CARDS ===== */
.card-modern {
    background: white;
    border-radius: var(--radius);
    border: 1px solid var(--gray-200);
    box-shadow: var(--shadow);
    transition: all .3s ease;
    overflow: hidden;
}

.card-modern:hover { box-shadow: var(--shadow-md); }

.card-modern .card-header-custom {
    padding: 16px 20px;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--gray-50);
}

.card-modern .card-header-custom h6 {
    font-size: 14px;
    font-weight: 700;
    color: var(--gray-800);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.card-modern .card-body-custom {
    padding: 20px;
}

/* ===== TABLES ===== */
.table-modern {
    margin-bottom: 0;
}

.table-modern thead th {
    font-size: 11px;
    font-weight: 700;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: .5px;
    padding: 12px 16px;
    border-bottom: 2px solid var(--gray-200);
    background: transparent;
}

.table-modern tbody td {
    padding: 12px 16px;
    font-size: 13px;
    color: var(--gray-700);
    border-bottom: 1px solid var(--gray-100);
    vertical-align: middle;
}

.table-modern tbody tr:hover { background: var(--gray-50); }
.table-modern tbody tr:last-child td { border-bottom: none; }

/* ===== BADGES ===== */
.badge-modern {
    padding: 4px 12px;
    border-radius: 100px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .3px;
}

.badge-approved { background: rgba(16,185,129,.12); color: #059669; }
.badge-pending { background: rgba(245,158,11,.12); color: #d97706; }
.badge-rejected { background: rgba(239,68,68,.12); color: #dc2626; }
.badge-hold { background: rgba(100,116,139,.12); color: var(--secondary); }
.badge-cancelled { background: rgba(239,68,68,.12); color: #dc2626; }

/* ===== QUICK ACTIONS ===== */
.quick-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.btn-action {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: var(--radius-sm);
    font-size: 13px;
    font-weight: 600;
    border: none;
    transition: all .25s ease;
    text-decoration: none;
    color: white;
}

.btn-action:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
    color: white;
}

.btn-action-primary { background: linear-gradient(135deg, var(--primary), #7c3aed); }
.btn-action-success { background: linear-gradient(135deg, var(--success), #059669); }
.btn-action-info { background: linear-gradient(135deg, var(--info), #0284c7); }
.btn-action-warning { background: linear-gradient(135deg, var(--warning), #d97706); }
.btn-action-dark { background: linear-gradient(135deg, var(--dark), #1e293b); }
.btn-action-secondary { background: linear-gradient(135deg, var(--secondary), #475569); }

/* ===== NOTICE / HOLIDAY ITEMS ===== */
.list-item {
    padding: 14px 0;
    border-bottom: 1px solid var(--gray-100);
}

.list-item:last-child { border-bottom: none; }

.list-item h6 {
    font-size: 14px;
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: 4px;
}

.list-item p {
    font-size: 13px;
    color: var(--gray-500);
    margin-bottom: 4px;
    line-height: 1.5;
}

.list-item small {
    font-size: 11px;
    color: var(--gray-400);
}

/* ===== EMPTY STATE ===== */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--gray-400);
}

.empty-state i {
    font-size: 40px;
    margin-bottom: 12px;
    display: block;
}

.empty-state p { font-size: 14px; margin: 0; }

/* ===== SIDEBAR TOGGLE ===== */
.sidebar-toggle {
    width: 38px;
    height: 38px;
    border-radius: var(--radius-sm);
    border: 1px solid var(--gray-200);
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all .2s ease;
    color: var(--gray-600);
}

.sidebar-toggle:hover {
    background: var(--gray-100);
    color: var(--gray-900);
}

/* ===== SIDEBAR COLLAPSED ===== */
body.sidebar-collapsed .sidebar {
    transform: translateX(-100%);
}

body.sidebar-collapsed .main-content {
    margin-left: 0;
}

/* ===== SIDEBAR BACKDROP ===== */
.sidebar-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15,23,42,.5);
    z-index: 1045;
    opacity: 0;
    transition: opacity .3s ease;
}

.sidebar-backdrop.show {
    display: block;
    opacity: 1;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1200px) {
    .stats-grid { grid-template-columns: repeat(3, 1fr); }
}

@media (max-width: 991px) {
    .sidebar {
        transform: translateX(-100%);
    }
    .sidebar.open {
        transform: translateX(0);
    }
    .main-content { margin-left: 0; }
    .header { padding: 0 20px; }
    .page-content { padding: 20px; }
    .stats-grid { grid-template-columns: repeat(3, 1fr); }
    .header-date { display: none; }
}

@media (max-width: 768px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .header-left h4 { font-size: 16px; }
    .header-left h4 span { display: none; }
}

@media (max-width: 480px) {
    .stats-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
    .stat-card { padding: 14px; }
    .stat-card .stat-value { font-size: 22px; }
    .stat-card .stat-icon { width: 36px; height: 36px; font-size: 15px; }
    .page-content { padding: 16px; }
    .header { padding: 0 14px; height: 60px; }
    .header-admin-badge span { display: none; }
}

/* ===== DARK MODE OVERRIDES ===== */
body.dark-mode {
    background: #0f172a;
    color: #e2e8f0;
}

.dark-mode .header {
    background: rgba(30,41,59,.95);
    border-bottom-color: rgba(255,255,255,.08);
}

.dark-mode .header-left h4 { color: #f1f5f9; }
.dark-mode .header-left h4 span { color: var(--gray-400); }
.dark-mode .header-date { background: rgba(255,255,255,.06); color: var(--gray-400); }
.dark-mode .header-admin-badge { background: rgba(37,99,235,.2); }

.dark-mode .stat-card,
.dark-mode .card-modern {
    background: #1e293b;
    border-color: rgba(255,255,255,.08);
}

.dark-mode .stat-card .stat-value { color: #f1f5f9; }
.dark-mode .card-modern .card-header-custom { background: rgba(255,255,255,.04); border-color: rgba(255,255,255,.08); }
.dark-mode .card-modern .card-header-custom h6 { color: #e2e8f0; }

.dark-mode .table-modern thead th { color: var(--gray-400); border-color: rgba(255,255,255,.08); }
.dark-mode .table-modern tbody td { color: #cbd5e1; border-color: rgba(255,255,255,.06); }
.dark-mode .table-modern tbody tr:hover { background: rgba(255,255,255,.04); }

.dark-mode .list-item { border-color: rgba(255,255,255,.06); }
.dark-mode .list-item h6 { color: #e2e8f0; }
.dark-mode .list-item p { color: var(--gray-400); }

.dark-mode .sidebar-toggle { background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.1); color: var(--gray-400); }
.dark-mode .sidebar-toggle:hover { background: rgba(255,255,255,.12); color: white; }

.dark-mode .section-header h5 { color: #e2e8f0; }
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
        <a href="dashboard.php" class="sidebar-link active"><i class="fa fa-gauge"></i> Dashboard</a>
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
            <h4>Dashboard <span>/ Overview</span></h4>
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

        <!-- Session Messages -->
        <?php if(isset($_SESSION['status_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-pill px-4" role="alert">
                <i class="fa fa-check-circle me-2"></i> <?=$_SESSION['status_success']?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['status_success']); ?>
        <?php endif; ?>
        <?php if(isset($_SESSION['status_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-pill px-4" role="alert">
                <i class="fa fa-exclamation-circle me-2"></i> <?=$_SESSION['status_error']?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['status_error']); ?>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card stat-primary">
                <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                <div class="stat-label">Total Employees</div>
                <div class="stat-value"><?=$totalEmployees?></div>
            </div>
            <div class="stat-card stat-success">
                <div class="stat-icon"><i class="fa-solid fa-file-lines"></i></div>
                <div class="stat-label">Total Leaves</div>
                <div class="stat-value"><?=$totalLeaves?></div>
            </div>
            <div class="stat-card stat-info">
                <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
                <div class="stat-label">Today Attendance</div>
                <div class="stat-value"><?=$todayAttendance?></div>
            </div>
            <div class="stat-card stat-success">
                <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                <div class="stat-label">Approved</div>
                <div class="stat-value"><?=$approvedLeaves?></div>
            </div>
            <div class="stat-card stat-warning">
                <div class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></div>
                <div class="stat-label">Pending</div>
                <div class="stat-value"><?=$pendingLeaves?></div>
            </div>
            <div class="stat-card stat-danger">
                <div class="stat-icon"><i class="fa-solid fa-circle-xmark"></i></div>
                <div class="stat-label">Rejected</div>
                <div class="stat-value"><?=$rejectedLeaves?></div>
            </div>
        </div>

        <!-- Employee Status Section -->
        <div class="card-modern mt-4 mb-4">
            <div class="card-header-custom">
                <h6><i class="fa-solid fa-user-check"></i> Employee Activity Status</h6>
                <div>
                    <span class="badge-modern badge-approved me-1"><i class="fa fa-circle"></i> Active: <?=$activeEmployees?></span>
                    <span class="badge-modern badge-rejected"><i class="fa fa-circle"></i> Inactive: <?=$inactiveEmployees?></span>
                </div>
            </div>
            <div class="card-body-custom p-0">
                <div class="table-responsive">
                    <table class="table table-modern">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Designation</th>
                                <th>Status (Field)</th>
                                <th>is_active</th>
                                <th><?=$canManageStatus ? 'Actions' : ''?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php mysqli_data_seek($employeeStatusList, 0); ?>
                        <?php if($employeeStatusList && mysqli_num_rows($employeeStatusList) > 0): ?>
                            <?php while($emp = mysqli_fetch_assoc($employeeStatusList)):
                                $isActuallyActive = ($emp['status'] == 'Active' && $emp['is_active'] == 1);
                            ?>
                            <tr>
                                <td><span class="fw-bold"><?=htmlspecialchars($emp['employee_id'])?></span></td>
                                <td><?=htmlspecialchars($emp['full_name'])?></td>
                                <td><?=htmlspecialchars($emp['department'] ?? 'N/A')?></td>
                                <td><?=htmlspecialchars($emp['designation'] ?? 'N/A')?></td>
                                <td>
                                    <?php if($emp['status'] == 'Active'): ?>
                                        <span class="badge-modern badge-approved"><i class="fa fa-check-circle"></i> Active</span>
                                    <?php else: ?>
                                        <span class="badge-modern badge-rejected"><i class="fa fa-times-circle"></i> Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($emp['is_active'] == 1): ?>
                                        <span class="badge-modern badge-approved">Yes</span>
                                    <?php else: ?>
                                        <span class="badge-modern badge-rejected">No</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <?php if($canManageStatus): ?>
                                            <?php if($isActuallyActive): ?>
                                                <a href="toggle_employee_status.php?id=<?=$emp['id']?>&action=deactivate" class="btn btn-outline-warning" onclick="return confirm('Deactivate <?=htmlspecialchars($emp['full_name'])?>?')" title="Deactivate">
                                                    <i class="fa fa-pause-circle"></i> Deactivate
                                                </a>
                                            <?php else: ?>
                                                <a href="toggle_employee_status.php?id=<?=$emp['id']?>&action=activate" class="btn btn-outline-success" onclick="return confirm('Activate <?=htmlspecialchars($emp['full_name'])?>?')" title="Activate">
                                                    <i class="fa fa-play-circle"></i> Activate
                                                </a>
                                            <?php endif; ?>
                                            <a href="edit_employee.php?id=<?=$emp['id']?>" class="btn btn-outline-primary" title="Edit">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small"><i class="fa fa-eye"></i> View Only</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">No employees found</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Adjustment Stats -->
        <div class="section-header">
            <h5><i class="fa-solid fa-pen-to-square"></i> Attendance Adjustments</h5>
        </div>
        <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
            <div class="stat-card stat-secondary">
                <div class="stat-icon"><i class="fa-solid fa-pen"></i></div>
                <div class="stat-label">Total Adjustments</div>
                <div class="stat-value"><?=$totalAdjustments?></div>
            </div>
            <div class="stat-card stat-warning">
                <div class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></div>
                <div class="stat-label">Pending</div>
                <div class="stat-value"><?=$pendingAdjustments?></div>
            </div>
            <div class="stat-card stat-success">
                <div class="stat-icon"><i class="fa-solid fa-check-circle"></i></div>
                <div class="stat-label">Approved</div>
                <div class="stat-value"><?=$approvedAdjustments?></div>
            </div>
            <div class="stat-card stat-danger">
                <div class="stat-icon"><i class="fa-solid fa-times-circle"></i></div>
                <div class="stat-label">Rejected</div>
                <div class="stat-value"><?=$rejectedAdjustments?></div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-4 mt-1">
            <div class="col-lg-6">
                <div class="card-modern">
                    <div class="card-header-custom">
                        <h6><i class="fa-solid fa-chart-bar"></i> Department Wise Employees</h6>
                    </div>
                    <div class="card-body-custom">
                        <canvas id="deptChart" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card-modern">
                    <div class="card-header-custom">
                        <h6><i class="fa-solid fa-chart-pie"></i> Leave Statistics</h6>
                    </div>
                    <div class="card-body-custom">
                        <canvas id="leaveChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tables Row -->
        <div class="row g-4 mt-2">
            <div class="col-lg-6">
                <div class="card-modern">
                    <div class="card-header-custom">
                        <h6><i class="fa-solid fa-user-plus"></i> Latest Employees</h6>
                        <a href="employee.php" class="btn btn-sm btn-outline-primary rounded-pill">View All</a>
                    </div>
                    <div class="card-body-custom p-0">
                        <div class="table-responsive">
                            <table class="table table-modern">
                                <thead>
                                    <tr><th>ID</th><th>Name</th><th>Department</th><th>Joining</th></tr>
                                </thead>
                                <tbody>
                                <?php while($emp = mysqli_fetch_assoc($latestEmployees)){ ?>
                                    <tr>
                                        <td><span class="fw-bold"><?=htmlspecialchars($emp['employee_id'])?></span></td>
                                        <td><?=htmlspecialchars($emp['full_name'])?></td>
                                        <td><?=htmlspecialchars($emp['department'])?></td>
                                        <td><?=date('d-m-Y', strtotime($emp['joining_date']))?></td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card-modern">
                    <div class="card-header-custom">
                        <h6><i class="fa-solid fa-calendar-check"></i> Latest Leave Requests</h6>
                        <a href="leave_requests.php" class="btn btn-sm btn-outline-primary rounded-pill">View All</a>
                    </div>
                    <div class="card-body-custom p-0">
                        <div class="table-responsive">
                            <table class="table table-modern">
                                <thead>
                                    <tr><th>Employee</th><th>Type</th><th>Status</th></tr>
                                </thead>
                                <tbody>
                                <?php while($leave = mysqli_fetch_assoc($latestLeaves)){ ?>
                                    <tr>
                                        <td><?=htmlspecialchars($leave['full_name'])?></td>
                                        <td><?=htmlspecialchars($leave['leave_type'])?></td>
                                        <td>
                                            <?php
                                            $badgeClass = 'badge-pending';
                                            if($leave['status'] == 'Approved') $badgeClass = 'badge-approved';
                                            elseif($leave['status'] == 'Rejected') $badgeClass = 'badge-rejected';
                                            ?>
                                            <span class="badge-modern <?=$badgeClass?>"><?=$leave['status']?></span>
                                        </td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notices & Holidays Row -->
        <div class="row g-4 mt-2">
            <div class="col-lg-6">
                <div class="card-modern">
                    <div class="card-header-custom">
                        <h6><i class="fa-solid fa-bullhorn"></i> Latest Notices</h6>
                        <a href="add_notice.php" class="btn btn-sm btn-outline-primary rounded-pill">Manage</a>
                    </div>
                    <div class="card-body-custom">
                        <?php if($latestNotices && mysqli_num_rows($latestNotices) > 0): ?>
                            <?php while($notice = mysqli_fetch_assoc($latestNotices)){ ?>
                            <div class="list-item">
                                <h6><?=htmlspecialchars($notice['title'])?></h6>
                                <p><?=htmlspecialchars($notice['notice'])?></p>
                                <small><i class="fa-regular fa-clock"></i> <?=date('d-m-Y', strtotime($notice['created_at']))?></small>
                            </div>
                            <?php } ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fa-regular fa-bell"></i>
                                <p>No notices available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card-modern">
                    <div class="card-header-custom">
                        <h6><i class="fa-solid fa-plane"></i> Upcoming Holidays</h6>
                        <a href="add_holiday.php" class="btn btn-sm btn-outline-primary rounded-pill">Manage</a>
                    </div>
                    <div class="card-body-custom">
                        <?php if($upcomingHolidays && mysqli_num_rows($upcomingHolidays) > 0): ?>
                            <?php while($holiday = mysqli_fetch_assoc($upcomingHolidays)){ ?>
                            <div class="list-item">
                                <h6><?=htmlspecialchars($holiday['holiday_name'])?></h6>
                                <p><?=htmlspecialchars($holiday['description'] ?? '')?></p>
                                <small><i class="fa-regular fa-calendar"></i> <?=date('d-m-Y', strtotime($holiday['holiday_date']))?></small>
                            </div>
                            <?php } ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fa-regular fa-calendar"></i>
                                <p>No upcoming holidays</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card-modern mt-4">
            <div class="card-header-custom">
                <h6><i class="fa-solid fa-bolt"></i> Quick Actions</h6>
            </div>
            <div class="card-body-custom">
                <div class="quick-actions">
                    <a href="add_employee.php" class="btn-action btn-action-primary"><i class="fa fa-user-plus"></i> Add Employee</a>
                    <a href="leave_requests.php" class="btn-action btn-action-success"><i class="fa fa-check-circle"></i> Approve Leaves</a>
                    <a href="manage_shifts.php" class="btn-action btn-action-info"><i class="fa fa-clock"></i> Manage Shifts</a>
                    <a href="attendance_report.php" class="btn-action btn-action-warning"><i class="fa fa-clock"></i> Attendance</a>
                    <a href="reports.php" class="btn-action btn-action-dark"><i class="fa fa-chart-column"></i> Reports</a>
                    <a href="supervisor_adjustments.php" class="btn-action btn-action-secondary"><i class="fa fa-user-tie"></i> Supervisor</a>
                    <a href="admin_adjustments.php" class="btn-action btn-action-info"><i class="fa fa-shield-alt"></i> Admin Adjustments</a>
                </div>
            </div>
        </div>

        <!-- Latest Adjustment Requests -->
        <div class="card-modern mt-4">
            <div class="card-header-custom">
                <h6><i class="fa-solid fa-pen-to-square"></i> Latest Adjustment Requests</h6>
                <div>
                    <a href="supervisor_adjustments.php" class="btn btn-sm btn-outline-secondary rounded-pill me-1"><i class="fa fa-user-tie"></i> Supervisor</a>
                    <a href="admin_adjustments.php" class="btn btn-sm btn-outline-info rounded-pill"><i class="fa fa-shield-alt"></i> Admin</a>
                </div>
            </div>
            <div class="card-body-custom p-0">
                <div class="table-responsive">
                    <table class="table table-modern">
                        <thead>
                            <tr><th>Request #</th><th>Employee</th><th>Department</th><th>Date</th><th>Type</th><th>Status</th><th>Approved By</th></tr>
                        </thead>
                        <tbody>
                        <?php if($latestAdjustments && mysqli_num_rows($latestAdjustments) > 0): ?>
                            <?php while($adj = mysqli_fetch_assoc($latestAdjustments)):
                                $adjBadge = 'badge-pending';
                                if($adj['status'] == 'Approved') $adjBadge = 'badge-approved';
                                elseif($adj['status'] == 'Rejected') $adjBadge = 'badge-rejected';
                                elseif($adj['status'] == 'Hold') $adjBadge = 'badge-hold';
                                elseif($adj['status'] == 'Cancelled') $adjBadge = 'badge-cancelled';
                            ?>
                            <tr>
                                <td><span class="fw-bold"><?=htmlspecialchars($adj['request_no'])?></span></td>
                                <td><?=htmlspecialchars($adj['full_name'])?></td>
                                <td><?=htmlspecialchars($adj['department'])?></td>
                                <td><?=date('d-m-Y', strtotime($adj['attendance_date']))?></td>
                                <td><?=htmlspecialchars($adj['adjustment_type'])?></td>
                                <td><span class="badge-modern <?=$adjBadge?>"><?=$adj['status']?></span></td>
                                <td>
                                <?php
                                $approvedBy = '—';
                                if(in_array($adj['status'], ['Approved','Rejected','Hold','Cancelled'])) {
                                    if(!empty($adj['admin_name'])) $approvedBy = 'Admin: '.htmlspecialchars($adj['admin_name']);
                                    elseif(!empty($adj['supervisor_name'])) $approvedBy = 'Supervisor: '.htmlspecialchars($adj['supervisor_name']);
                                }
                                echo $approvedBy;
                                ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">No adjustment requests found</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="text-center mt-5 mb-2 text-muted" style="font-size: 13px;">
            Employee Management System &copy; 2026 &mdash; Developed by Minam Shaikh
        </footer>

    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Department Chart
new Chart(document.getElementById('deptChart'), {
    type: 'bar',
    data: {
        labels: <?=json_encode($labels)?>,
        datasets: [{
            label: 'Employees',
            data: <?=json_encode($values)?>,
            backgroundColor: 'rgba(37,99,235,.7)',
            borderColor: 'rgba(37,99,235,1)',
            borderWidth: 1,
            borderRadius: 6,
            barPercentage: 0.6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1 }
            }
        }
    }
});

// Leave Chart
new Chart(document.getElementById('leaveChart'), {
    type: 'doughnut',
    data: {
        labels: ['Approved', 'Pending', 'Rejected'],
        datasets: [{
            data: [<?=$approvedLeaves?>, <?=$pendingLeaves?>, <?=$rejectedLeaves?>],
            backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { padding: 16, usePointStyle: true }
            }
        },
        cutout: '65%'
    }
});

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