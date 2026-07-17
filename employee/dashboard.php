<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['employee_id'])) {
    header("Location: ../index.php");
    exit();
}

$employee_id = $_SESSION['employee_id'];
$employee_name = $_SESSION['employee_name'] ?? '';
$employee_role = $_SESSION['employee_role'] ?? 'Employee';

if ($employee_role === 'Admin') {
    header("Location: ../admin/dashboard.php");
    exit();
}

$employee = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM employees WHERE id='$employee_id' LIMIT 1"));
$photo = !empty($employee['photo']) ? $employee['photo'] : 'default.png';

$totalLeaves = 0;
$approvedLeaves = 0;
$pendingLeaves = 0;
$rejectedLeaves = 0;
$today = date('Y-m-d');
$attendanceStatus = 'Absent';
$checkInTime = '—';
$checkOutTime = '—';
$workingHours = '—';

$leaveCounts = mysqli_query($conn, "SELECT status, COUNT(*) AS cnt FROM leave_requests WHERE employee_id='$employee_id' GROUP BY status");
if ($leaveCounts) {
    while ($row = mysqli_fetch_assoc($leaveCounts)) {
        $totalLeaves += $row['cnt'];
        if ($row['status'] === 'Approved') {
            $approvedLeaves = $row['cnt'];
        } elseif ($row['status'] === 'Pending') {
            $pendingLeaves = $row['cnt'];
        } elseif ($row['status'] === 'Rejected') {
            $rejectedLeaves = $row['cnt'];
        }
    }
}

$todayAttendance = mysqli_query($conn, "SELECT * FROM attendance WHERE employee_id='$employee_id' AND attendance_date='$today' LIMIT 1");
if ($todayAttendance && mysqli_num_rows($todayAttendance) > 0) {
    $attendance = mysqli_fetch_assoc($todayAttendance);
    $attendanceStatus = $attendance['status'] ?? 'Present';
    $checkInTime = !empty($attendance['check_in']) ? date('h:i A', strtotime($attendance['check_in'])) : '—';
    $checkOutTime = !empty($attendance['check_out']) ? date('h:i A', strtotime($attendance['check_out'])) : '—';
    $workingHours = !empty($attendance['working_hours']) ? $attendance['working_hours'] : '—';
}

$balanceQuery = mysqli_query($conn, "SELECT * FROM leave_balance WHERE employee_id='$employee_id' LIMIT 1");
if ($balanceQuery && mysqli_num_rows($balanceQuery) === 0) {
    mysqli_query($conn, "INSERT INTO leave_balance (employee_id, casual_leave, sick_leave, annual_leave) VALUES ('$employee_id', 12, 10, 20)");
    $balanceQuery = mysqli_query($conn, "SELECT * FROM leave_balance WHERE employee_id='$employee_id' LIMIT 1");
}
$leaveBalance = ($balanceQuery) ? mysqli_fetch_assoc($balanceQuery) : ['casual_leave' => 0, 'sick_leave' => 0, 'annual_leave' => 0];

$latestNotices = mysqli_query($conn, "SELECT * FROM notices ORDER BY id DESC LIMIT 5");
$upcomingHolidays = mysqli_query($conn, "
    SELECT * FROM holidays
    WHERE holiday_date >= CURDATE()
    ORDER BY holiday_date ASC
    LIMIT 5
");

$shiftLabel = !empty($employee['shift_name']) ? $employee['shift_name'] : 'Morning';
$shiftStart = !empty($employee['shift_start_time']) ? date('H:i', strtotime($employee['shift_start_time'])) : '09:00';
$shiftEnd = !empty($employee['shift_end_time']) ? date('H:i', strtotime($employee['shift_end_time'])) : '17:00';

// Adjustment Statistics for Employee
$totalAdj = 0;
$adjApproved = 0;
$adjPending = 0;
$adjRejected = 0;
$adjCounts = mysqli_query($conn, "SELECT status, COUNT(*) AS cnt FROM attendance_adjustments WHERE employee_id='$employee_id' GROUP BY status");
if ($adjCounts) {
    while ($row = mysqli_fetch_assoc($adjCounts)) {
        $totalAdj += $row['cnt'];
        if ($row['status'] === 'Approved') $adjApproved = $row['cnt'];
        elseif ($row['status'] === 'Pending') $adjPending = $row['cnt'];
        elseif ($row['status'] === 'Rejected' || $row['status'] === 'Cancelled') $adjRejected += $row['cnt'];
    }
}
$designation = $employee['designation'] ?? $employee_role;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - <?php echo htmlspecialchars($employee_name); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
    --primary: #2563eb;
    --primary-dark: #1d4ed8;
    --primary-light: #60a5fa;
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
    --radius: 16px;
    --radius-sm: 10px;
    --radius-xs: 8px;
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
    min-height: 100vh;
    padding: 0;
}

/* ===== TOP NAV ===== */
.top-nav {
    background: rgba(255,255,255,.95);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--gray-200);
    padding: 0 32px;
    height: 68px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 1050;
}

.top-nav-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.top-nav-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
}

.top-nav-brand .brand-icon {
    width: 38px;
    height: 38px;
    background: linear-gradient(135deg, var(--primary), #7c3aed);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: white;
}

.top-nav-brand .brand-text {
    font-size: 18px;
    font-weight: 800;
    color: var(--gray-900);
    letter-spacing: -.5px;
}

.top-nav-right {
    display: flex;
    align-items: center;
    gap: 10px;
}

.top-nav-date {
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

.top-nav-user {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px 6px 10px;
    background: rgba(37,99,235,.08);
    border-radius: 100px;
    font-size: 13px;
    font-weight: 600;
    color: var(--primary);
}

.top-nav-user .user-avatar-small {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    background: linear-gradient(135deg, var(--primary), #7c3aed);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 12px;
}

/* ===== PAGE CONTAINER ===== */
.page-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 28px 32px;
}

/* ===== WELCOME BANNER ===== */
.welcome-banner {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 40%, #7c3aed 100%);
    border-radius: var(--radius);
    padding: 32px 36px;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(37,99,235,.25);
}

.welcome-banner::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 400px;
    height: 400px;
    background: rgba(255,255,255,.04);
    border-radius: 50%;
}

.welcome-banner::after {
    content: '';
    position: absolute;
    bottom: -30%;
    left: 50%;
    width: 300px;
    height: 300px;
    background: rgba(255,255,255,.03);
    border-radius: 50%;
}

.welcome-banner-content {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 24px;
}

.welcome-banner .profile-img {
    width: 72px;
    height: 72px;
    border-radius: 16px;
    border: 3px solid rgba(255,255,255,.3);
    object-fit: cover;
    flex-shrink: 0;
    box-shadow: 0 4px 16px rgba(0,0,0,.2);
}

.welcome-banner .welcome-text h2 {
    font-size: 24px;
    font-weight: 700;
    color: white;
    margin-bottom: 4px;
}

.welcome-banner .welcome-text p {
    color: rgba(255,255,255,.8);
    font-size: 14px;
    margin: 0;
}

.welcome-banner .welcome-text p strong { color: white; }

.welcome-banner .banner-actions {
    margin-left: auto;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-banner {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: var(--radius-xs);
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: all .25s ease;
    border: none;
    color: white;
}

.btn-banner:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
    color: white;
}

.btn-banner-primary { background: rgba(255,255,255,.2); backdrop-filter: blur(10px); }
.btn-banner-primary:hover { background: rgba(255,255,255,.3); }
.btn-banner-danger { background: rgba(239,68,68,.7); }
.btn-banner-danger:hover { background: rgba(239,68,68,.9); }

/* ===== SECTION ===== */
.section-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.section-title i { color: var(--primary); }

/* ===== STATS GRID ===== */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 28px;
}

.stat-card {
    background: white;
    border-radius: var(--radius);
    padding: 22px;
    border: 1px solid var(--gray-200);
    box-shadow: var(--shadow);
    transition: all .3s ease;
    position: relative;
    overflow: hidden;
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
    margin-bottom: 2px;
}

.stat-card .stat-value {
    font-size: 26px;
    font-weight: 800;
    color: var(--gray-900);
    line-height: 1.2;
}

.stat-primary .stat-icon { background: rgba(37,99,235,.12); color: var(--primary); }
.stat-success .stat-icon { background: rgba(16,185,129,.12); color: var(--success); }
.stat-warning .stat-icon { background: rgba(245,158,11,.12); color: var(--warning); }
.stat-danger .stat-icon { background: rgba(239,68,68,.12); color: var(--danger); }

/* ===== FEATURE CARDS ===== */
.feature-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 28px;
}

.feature-card {
    background: white;
    border-radius: var(--radius);
    border: 1px solid var(--gray-200);
    box-shadow: var(--shadow);
    overflow: hidden;
    transition: all .3s ease;
}

.feature-card:hover {
    box-shadow: var(--shadow-md);
    border-color: var(--gray-300);
}

.feature-card .fc-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--gray-200);
    background: var(--gray-50);
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    font-weight: 700;
    color: var(--gray-800);
}

.feature-card .fc-header i { font-size: 16px; }

.fc-header-primary i { color: var(--primary); }
.fc-header-success i { color: var(--success); }
.fc-header-info i { color: var(--info); }

.feature-card .fc-body {
    padding: 20px;
}

.feature-card .fc-body .fc-label {
    font-size: 11px;
    font-weight: 700;
    color: var(--gray-400);
    text-transform: uppercase;
    letter-spacing: .5px;
    margin-bottom: 8px;
}

.feature-card .fc-body .fc-value {
    font-size: 15px;
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: 4px;
}

.feature-card .fc-body .fc-sub {
    font-size: 13px;
    color: var(--gray-500);
}

.feature-card .fc-body .fc-link {
    margin-top: 12px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 600;
    color: var(--primary);
    text-decoration: none;
    padding: 6px 14px;
    border-radius: var(--radius-xs);
    background: rgba(37,99,235,.08);
    transition: all .2s ease;
}

.feature-card .fc-body .fc-link:hover {
    background: rgba(37,99,235,.15);
}

/* ===== LEAVE BALANCE BAR ===== */
.leave-bar-group { margin-top: 8px; }
.leave-bar-item { margin-bottom: 10px; }
.leave-bar-item:last-child { margin-bottom: 0; }
.leave-bar-item .lbl-row {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    font-weight: 600;
    color: var(--gray-600);
    margin-bottom: 4px;
}
.leave-bar-item .lbl-row span:last-child { color: var(--gray-900); }
.leave-bar-item .bar-track {
    height: 6px;
    background: var(--gray-100);
    border-radius: 100px;
    overflow: hidden;
}
.leave-bar-item .bar-fill {
    height: 100%;
    border-radius: 100px;
    transition: width .6s ease;
}

/* ===== QUICK MENU GRID ===== */
.menu-section {
    background: white;
    border-radius: var(--radius);
    border: 1px solid var(--gray-200);
    box-shadow: var(--shadow);
    padding: 24px;
    margin-bottom: 28px;
}

.menu-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-top: 4px;
}

.menu-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: 20px 12px;
    border-radius: var(--radius-sm);
    text-decoration: none;
    transition: all .25s ease;
    border: 1px solid var(--gray-200);
    background: white;
    color: var(--gray-700);
}

.menu-item:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
    border-color: var(--primary);
    color: var(--gray-700);
    text-decoration: none;
}

.menu-item .menu-icon {
    width: 50px;
    height: 50px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
}

.menu-item .menu-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--gray-600);
    text-align: center;
}

.mi-attendance .menu-icon { background: rgba(37,99,235,.12); color: var(--primary); }
.mi-history .menu-icon { background: rgba(16,185,129,.12); color: var(--success); }
.mi-apply .menu-icon { background: rgba(245,158,11,.12); color: var(--warning); }
.mi-leave .menu-icon { background: rgba(6,182,212,.12); color: var(--info); }
.mi-adjustment .menu-icon { background: rgba(236,72,153,.12); color: #ec4899; }
.mi-myadj .menu-icon { background: rgba(139,92,246,.12); color: #8b5cf6; }
.mi-password .menu-icon { background: rgba(239,68,68,.12); color: var(--danger); }
.mi-balance .menu-icon { background: rgba(16,185,129,.12); color: var(--success); }

/* ===== NOTICES / HOLIDAYS ===== */
.content-card {
    background: white;
    border-radius: var(--radius);
    border: 1px solid var(--gray-200);
    box-shadow: var(--shadow);
    overflow: hidden;
    height: 100%;
    transition: all .3s ease;
}

.content-card:hover { box-shadow: var(--shadow-md); }

.content-card .cc-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    font-weight: 700;
    color: var(--gray-800);
    background: var(--gray-50);
}

.content-card .cc-header i { font-size: 16px; }
.cc-header-primary i { color: var(--primary); }
.cc-header-success i { color: var(--success); }

.content-card .cc-body {
    padding: 16px 20px;
}

.list-item {
    padding: 12px 0;
    border-bottom: 1px solid var(--gray-100);
}

.list-item:last-child { border-bottom: none; }

.list-item h6 {
    font-size: 14px;
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: 2px;
}

.list-item p {
    font-size: 13px;
    color: var(--gray-500);
    margin-bottom: 2px;
    line-height: 1.5;
}

.list-item small {
    font-size: 11px;
    color: var(--gray-400);
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

/* ===== EMPTY STATE ===== */
.empty-state {
    text-align: center;
    padding: 32px 20px;
    color: var(--gray-400);
}

.empty-state i {
    font-size: 36px;
    margin-bottom: 10px;
    display: block;
}

.empty-state p { font-size: 14px; margin: 0; }

/* ===== FOOTER ===== */
.footer {
    text-align: center;
    padding: 32px 0 12px;
    font-size: 13px;
    color: var(--gray-400);
}

/* ===== DARK MODE ===== */
body.dark-mode {
    background: #0f172a;
    color: #e2e8f0;
}

.dark-mode .top-nav {
    background: rgba(30,41,59,.95);
    border-bottom-color: rgba(255,255,255,.08);
}

.dark-mode .top-nav-brand .brand-text { color: #f1f5f9; }
.dark-mode .top-nav-date { background: rgba(255,255,255,.06); color: var(--gray-400); }
.dark-mode .top-nav-user { background: rgba(37,99,235,.2); }

.dark-mode .stat-card,
.dark-mode .feature-card,
.dark-mode .menu-section,
.dark-mode .content-card,
.dark-mode .menu-item {
    background: #1e293b;
    border-color: rgba(255,255,255,.08);
}

.dark-mode .stat-card .stat-value { color: #f1f5f9; }
.dark-mode .feature-card .fc-header,
.dark-mode .content-card .cc-header {
    background: rgba(255,255,255,.04);
    border-color: rgba(255,255,255,.08);
    color: #e2e8f0;
}
.dark-mode .feature-card .fc-body .fc-value { color: #e2e8f0; }
.dark-mode .feature-card .fc-body .fc-sub { color: var(--gray-400); }

.dark-mode .table-modern thead th { color: var(--gray-400); border-color: rgba(255,255,255,.08); }
.dark-mode .table-modern tbody td { color: #cbd5e1; border-color: rgba(255,255,255,.06); }
.dark-mode .table-modern tbody tr:hover { background: rgba(255,255,255,.04); }

.dark-mode .section-title { color: #e2e8f0; }

.dark-mode .list-item { border-color: rgba(255,255,255,.06); }
.dark-mode .list-item h6 { color: #e2e8f0; }
.dark-mode .list-item p { color: var(--gray-400); }

.dark-mode .menu-item { color: var(--gray-300); }
.dark-mode .menu-item:hover { border-color: var(--primary); }
.dark-mode .menu-item .menu-label { color: var(--gray-400); }

.dark-mode .leave-bar-item .bar-track { background: rgba(255,255,255,.08); }
.dark-mode .leave-bar-item .lbl-row span:last-child { color: #e2e8f0; }

/* ===== RESPONSIVE ===== */
@media (max-width: 991px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .feature-row { grid-template-columns: 1fr; }
    .menu-grid { grid-template-columns: repeat(2, 1fr); }
    .page-container { padding: 20px; }
    .top-nav { padding: 0 20px; }
    .welcome-banner { padding: 24px; }
    .welcome-banner-content { flex-wrap: wrap; }
    .banner-actions { margin-left: 0; width: 100%; }
    .top-nav-date { display: none; }
}

@media (max-width: 768px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .stat-card { padding: 16px; }
    .stat-card .stat-value { font-size: 22px; }
    .welcome-banner .profile-img { width: 56px; height: 56px; }
    .welcome-banner .welcome-text h2 { font-size: 20px; }
}

@media (max-width: 480px) {
    .page-container { padding: 14px; }
    .top-nav { padding: 0 14px; height: 60px; }
    .top-nav-user span { display: none; }
    .stats-grid { grid-template-columns: 1fr 1fr; gap: 8px; }
    .stat-card { padding: 14px; }
    .stat-card .stat-value { font-size: 20px; }
    .stat-card .stat-icon { width: 36px; height: 36px; font-size: 15px; }
    .menu-grid { grid-template-columns: 1fr 1fr; gap: 8px; }
    .menu-item { padding: 14px 8px; }
    .menu-item .menu-icon { width: 40px; height: 40px; font-size: 18px; }
    .welcome-banner { padding: 20px; }
    .welcome-banner .welcome-text h2 { font-size: 18px; }
}
</style>
</head>
<body>

<!-- Top Navigation -->
<nav class="top-nav">
    <div class="top-nav-left">
        <a href="dashboard.php" class="top-nav-brand">
            <div class="brand-icon"><i class="fa-solid fa-building"></i></div>
            <div class="brand-text">EMS</div>
        </a>
    </div>
    <div class="top-nav-right">
        <span class="top-nav-date"><i class="fa-regular fa-calendar"></i> <?=date('d M Y')?></span>
        <span class="top-nav-user">
            <div class="user-avatar-small"><?php echo strtoupper(substr($employee_name, 0, 1)); ?></div>
            <span><?php echo htmlspecialchars($employee_name); ?></span>
        </span>
        <?php include("../dark_mode.php"); ?>
        <a href="logout.php" class="btn btn-danger btn-sm rounded-pill px-3">
            <i class="fa fa-sign-out-alt"></i> <span>Logout</span>
        </a>
    </div>
</nav>

<!-- Page Container -->
<div class="page-container">

    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <div class="welcome-banner-content">
            <img src="../uploads/<?php echo $photo; ?>" alt="Profile" class="profile-img" onerror="this.src='https://ui-avatars.com/api/?name=<?=urlencode($employee_name)?>&background=2563eb&color=fff&size=72'">
            <div class="welcome-text">
                <h2>Welcome back, <?php echo htmlspecialchars($employee_name); ?>! 👋</h2>
                <p>Designation: <strong><?php echo htmlspecialchars($designation); ?></strong></p>
            </div>
            <div class="banner-actions">
                <a href="edit_profile.php" class="btn-banner btn-banner-primary"><i class="fas fa-edit"></i> Edit Profile</a>
                <a href="upload_photo.php" class="btn-banner btn-banner-primary"><i class="fas fa-camera"></i> Photo</a>
                <a href="logout.php" class="btn-banner btn-banner-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card stat-primary">
            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-label">Total Leaves</div>
            <div class="stat-value"><?php echo $totalLeaves; ?></div>
        </div>
        <div class="stat-card stat-success">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-label">Approved</div>
            <div class="stat-value"><?php echo $approvedLeaves; ?></div>
        </div>
        <div class="stat-card stat-warning">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-label">Pending</div>
            <div class="stat-value"><?php echo $pendingLeaves; ?></div>
        </div>
        <div class="stat-card stat-danger">
            <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
            <div class="stat-label">Rejected</div>
            <div class="stat-value"><?php echo $rejectedLeaves; ?></div>
        </div>
    </div>

    <!-- Adjustment Stats -->
    <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
        <div class="stat-card" style="border-top: 3px solid var(--primary);">
            <div class="stat-icon" style="background: rgba(37,99,235,.12); color: var(--primary);"><i class="fas fa-pen-alt"></i></div>
            <div class="stat-label">Total Adjustments</div>
            <div class="stat-value"><?php echo $totalAdj; ?></div>
        </div>
        <div class="stat-card" style="border-top: 3px solid var(--success);">
            <div class="stat-icon" style="background: rgba(16,185,129,.12); color: var(--success);"><i class="fas fa-check-circle"></i></div>
            <div class="stat-label">Adj. Approved</div>
            <div class="stat-value"><?php echo $adjApproved; ?></div>
        </div>
        <div class="stat-card" style="border-top: 3px solid var(--warning);">
            <div class="stat-icon" style="background: rgba(245,158,11,.12); color: var(--warning);"><i class="fas fa-clock"></i></div>
            <div class="stat-label">Adj. Pending</div>
            <div class="stat-value"><?php echo $adjPending; ?></div>
        </div>
        <div class="stat-card" style="border-top: 3px solid var(--danger);">
            <div class="stat-icon" style="background: rgba(239,68,68,.12); color: var(--danger);"><i class="fas fa-times-circle"></i></div>
            <div class="stat-label">Adj. Rejected</div>
            <div class="stat-value"><?php echo $adjRejected; ?></div>
        </div>
    </div>

    <!-- Feature Cards -->
    <div class="feature-row">
        <!-- Shift Info -->
        <div class="feature-card">
            <div class="fc-header fc-header-primary"><i class="fas fa-business-time"></i> Shift Information</div>
            <div class="fc-body">
                <div class="fc-label">Your Shift</div>
                <div class="fc-value"><?php echo htmlspecialchars($shiftLabel); ?></div>
                <div class="fc-sub"><?php echo $shiftStart; ?> – <?php echo $shiftEnd; ?></div>
            </div>
        </div>

        <!-- Leave Balance -->
        <div class="feature-card">
            <div class="fc-header fc-header-success"><i class="fas fa-chart-pie"></i> Leave Balance</div>
            <div class="fc-body">
                <div class="leave-bar-group">
                    <div class="leave-bar-item">
                        <div class="lbl-row"><span>Casual</span><span><?php echo (int)$leaveBalance['casual_leave']; ?> days</span></div>
                        <div class="bar-track"><div class="bar-fill" style="width: <?php echo min(100, ((int)$leaveBalance['casual_leave']/12)*100); ?>%; background: #10b981;"></div></div>
                    </div>
                    <div class="leave-bar-item">
                        <div class="lbl-row"><span>Sick</span><span><?php echo (int)$leaveBalance['sick_leave']; ?> days</span></div>
                        <div class="bar-track"><div class="bar-fill" style="width: <?php echo min(100, ((int)$leaveBalance['sick_leave']/10)*100); ?>%; background: #f59e0b;"></div></div>
                    </div>
                    <div class="leave-bar-item">
                        <div class="lbl-row"><span>Annual</span><span><?php echo (int)$leaveBalance['annual_leave']; ?> days</span></div>
                        <div class="bar-track"><div class="bar-fill" style="width: <?php echo min(100, ((int)$leaveBalance['annual_leave']/20)*100); ?>%; background: #3b82f6;"></div></div>
                    </div>
                </div>
                <a href="leave_balance.php" class="fc-link"><i class="fas fa-arrow-right"></i> Details</a>
            </div>
        </div>

        <!-- Today's Attendance -->
        <div class="feature-card">
            <div class="fc-header fc-header-info"><i class="fas fa-user-clock"></i> Today's Attendance</div>
            <div class="fc-body">
                <div class="fc-label">Status</div>
                <div class="fc-value" style="color: <?php echo ($attendanceStatus == 'Present' || $attendanceStatus == 'Present') ? '#10b981' : '#ef4444'; ?>">
                    <?php echo htmlspecialchars($attendanceStatus); ?>
                </div>
                <div class="fc-sub"><strong>Check-in:</strong> <?php echo $checkInTime; ?></div>
                <div class="fc-sub"><strong>Check-out:</strong> <?php echo $checkOutTime; ?></div>
                <?php if ($workingHours !== '—'): ?>
                <div class="fc-sub"><strong>Hours:</strong> <?php echo htmlspecialchars($workingHours); ?></div>
                <?php endif; ?>
                <a href="attendance.php" class="fc-link"><i class="fas fa-arrow-right"></i> Mark Attendance</a>
            </div>
        </div>
    </div>

    <!-- Quick Access Menu -->
    <div class="menu-section">
        <div class="section-title" style="margin-bottom: 20px;"><i class="fas fa-bars"></i> Quick Access</div>
        <div class="menu-grid">
            <a href="attendance.php" class="menu-item mi-attendance">
                <div class="menu-icon"><i class="fas fa-sign-in-alt"></i></div>
                <span class="menu-label">Attendance</span>
            </a>
            <a href="attendance_history.php" class="menu-item mi-history">
                <div class="menu-icon"><i class="fas fa-history"></i></div>
                <span class="menu-label">History</span>
            </a>
            <a href="apply_leave.php" class="menu-item mi-apply">
                <div class="menu-icon"><i class="fas fa-plus-circle"></i></div>
                <span class="menu-label">Apply Leave</span>
            </a>
            <a href="leave_history.php" class="menu-item mi-leave">
                <div class="menu-icon"><i class="fas fa-list"></i></div>
                <span class="menu-label">Leave History</span>
            </a>
            <a href="submit_adjustment.php" class="menu-item mi-adjustment">
                <div class="menu-icon"><i class="fas fa-pen-alt"></i></div>
                <span class="menu-label">Adjustment</span>
            </a>
            <a href="my_adjustments.php" class="menu-item mi-myadj">
                <div class="menu-icon"><i class="fas fa-clipboard-list"></i></div>
                <span class="menu-label">My Adjustments</span>
            </a>
            <a href="change_password.php" class="menu-item mi-password">
                <div class="menu-icon"><i class="fas fa-key"></i></div>
                <span class="menu-label">Password</span>
            </a>
            <a href="leave_balance.php" class="menu-item mi-balance">
                <div class="menu-icon"><i class="fas fa-chart-pie"></i></div>
                <span class="menu-label">Leave Balance</span>
            </a>
        </div>
    </div>

    <!-- Notices & Holidays -->
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="content-card">
                <div class="cc-header cc-header-primary"><i class="fas fa-bullhorn"></i> Latest Notices</div>
                <div class="cc-body">
                    <?php if ($latestNotices && mysqli_num_rows($latestNotices) > 0): ?>
                        <?php while ($notice = mysqli_fetch_assoc($latestNotices)): ?>
                        <div class="list-item">
                            <h6><?php echo htmlspecialchars($notice['title']); ?></h6>
                            <p><?php echo htmlspecialchars($notice['notice']); ?></p>
                            <?php if (!empty($notice['created_at'])): ?>
                            <small><i class="fa-regular fa-clock"></i> <?php echo date('d-m-Y', strtotime($notice['created_at'])); ?></small>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; ?>
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
            <div class="content-card">
                <div class="cc-header cc-header-success"><i class="fas fa-umbrella-beach"></i> Upcoming Holidays</div>
                <div class="cc-body">
                    <?php if ($upcomingHolidays && mysqli_num_rows($upcomingHolidays) > 0): ?>
                        <?php while ($holiday = mysqli_fetch_assoc($upcomingHolidays)): ?>
                        <div class="list-item">
                            <h6><?php echo htmlspecialchars($holiday['holiday_name']); ?></h6>
                            <p><?php echo htmlspecialchars($holiday['description'] ?? ''); ?></p>
                            <small><i class="fa-regular fa-calendar"></i> <?php echo date('d-m-Y', strtotime($holiday['holiday_date'])); ?></small>
                        </div>
                        <?php endwhile; ?>
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

    <!-- Recent Leave Requests -->
    <?php
    $recent = mysqli_query($conn, "SELECT leave_type, start_date, end_date, status FROM leave_requests WHERE employee_id='$employee_id' ORDER BY id DESC LIMIT 5");
    $recentAdjustments = mysqli_query($conn, "SELECT request_no, attendance_date, adjustment_type, status, created_at FROM attendance_adjustments WHERE employee_id='$employee_id' ORDER BY id DESC LIMIT 5");
    ?>
    <div class="content-card mt-4">
        <div class="cc-header cc-header-primary"><i class="fas fa-file-alt"></i> Recent Leave Requests</div>
        <div class="cc-body p-0">
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr><th>Leave Type</th><th>Start Date</th><th>End Date</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($recent) > 0): ?>
                            <?php while($leave = mysqli_fetch_assoc($recent)):
                                $sClass = 'badge-pending';
                                if($leave['status'] === 'Approved') $sClass = 'badge-approved';
                                elseif($leave['status'] === 'Rejected') $sClass = 'badge-rejected';
                            ?>
                            <tr>
                                <td><?php echo $leave['leave_type']; ?></td>
                                <td><?php echo date("d-m-Y", strtotime($leave['start_date'])); ?></td>
                                <td><?php echo date("d-m-Y", strtotime($leave['end_date'])); ?></td>
                                <td><span class="badge-modern <?php echo $sClass; ?>"><?php echo $leave['status']; ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">No leave requests yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Adjustment Requests -->
    <div class="content-card mt-4">
        <div class="cc-header cc-header-primary"><i class="fas fa-pen-alt"></i> Recent Adjustment Requests</div>
        <div class="cc-body p-0">
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr><th>Request #</th><th>Attendance Date</th><th>Type</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php if($recentAdjustments && mysqli_num_rows($recentAdjustments) > 0): ?>
                            <?php while($adj = mysqli_fetch_assoc($recentAdjustments)):
                                $sClass = 'badge-pending';
                                if($adj['status'] === 'Approved') $sClass = 'badge-approved';
                                elseif($adj['status'] === 'Rejected' || $adj['status'] === 'Cancelled') $sClass = 'badge-rejected';
                            ?>
                            <tr>
                                <td><span class="fw-bold"><?php echo htmlspecialchars($adj['request_no']); ?></span></td>
                                <td><?php echo date("d-m-Y", strtotime($adj['attendance_date'])); ?></td>
                                <td><?php echo htmlspecialchars($adj['adjustment_type']); ?></td>
                                <td><span class="badge-modern <?php echo $sClass; ?>"><?php echo $adj['status']; ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">No adjustment requests yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="p-3 border-top" style="border-color: var(--gray-200);">
            <a href="my_adjustments.php" class="btn btn-sm btn-outline-primary rounded-pill me-2"><i class="fas fa-clipboard-list"></i> View All</a>
            <a href="submit_adjustment.php" class="btn btn-sm btn-outline-success rounded-pill"><i class="fas fa-plus"></i> New Adjustment</a>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        Employee Management System &copy; 2026 &mdash; Developed by Minam Shaikh
    </div>

</div>

</body>
</html>