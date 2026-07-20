<?php
session_start();

// Set timezone to Pakistan Standard Time
date_default_timezone_set('Asia/Karachi');

if (!isset($_SESSION['employee_id'])) {
    header("Location: ../index.php");
    exit();
}

include("../config/db.php");

$employee_id = $_SESSION['employee_id'];
$today = date("Y-m-d");
$currentTime = date("H:i:s");

// Today's Attendance
$check = mysqli_query($conn, "
SELECT * FROM attendance
WHERE employee_id='$employee_id'
AND attendance_date='$today'
");

$row = mysqli_fetch_assoc($check);

// =======================
// CHECK IN
// =======================
if(isset($_POST['check_in']))
{
    $already = mysqli_query($conn,"
    SELECT * FROM attendance
    WHERE employee_id='$employee_id'
    AND attendance_date='$today'
    ");

    if(mysqli_num_rows($already)==0)
    {
        // Get employee's shift start time
        $employeeShift = mysqli_fetch_assoc(mysqli_query($conn,"
        SELECT shift_start_time FROM employees
        WHERE id='$employee_id'
        "));

        $initialStatus = 'Present';

        // Check if employee is late
        if($employeeShift && !empty($employeeShift['shift_start_time']))
        {
            $shiftStart = date('H:i:s', strtotime($employeeShift['shift_start_time']));
            if($currentTime > $shiftStart)
            {
                $initialStatus = 'Late';
            }
        }

        mysqli_query($conn,"
        INSERT INTO attendance
        (employee_id, attendance_date, check_in, status)
        VALUES ('$employee_id', '$today', '$currentTime', '$initialStatus')
        ");
    }

    header("Location: attendance.php");
    exit();
}

// =======================
// CHECK OUT
// =======================
if(isset($_POST['check_out']))
{
    $attendance = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT * FROM attendance
    WHERE employee_id='$employee_id'
    AND attendance_date='$today'
    "));

    if($attendance)
    {
        $checkIn = strtotime($attendance['check_in']);
        $checkOut = strtotime($currentTime);
        $seconds = $checkOut - $checkIn;
        if($seconds < 0) { $seconds = 0; }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $workingHours = $hours." Hours ".$minutes." Minutes";
        $status = $attendance['status'];

        // Get employee's shift end time to check for early out
        $employeeShift = mysqli_fetch_assoc(mysqli_query($conn,"
        SELECT shift_end_time FROM employees
        WHERE id='$employee_id'
        "));

        $isEarlyOut = false;
        if($employeeShift && !empty($employeeShift['shift_end_time']))
        {
            $shiftEnd = date('H:i:s', strtotime($employeeShift['shift_end_time']));
            if($currentTime < $shiftEnd) { $isEarlyOut = true; }
        }

        if($isEarlyOut) { $status = "Early Out"; }
        elseif($hours >= 8) { $status = "Present"; }
        elseif($hours >= 4) { $status = "Half Day"; }
        else { $status = "Absent"; }

        mysqli_query($conn,"
        UPDATE attendance
        SET check_out='$currentTime', working_hours='$workingHours', status='$status'
        WHERE employee_id='$employee_id' AND attendance_date='$today'
        ");
    }

    header("Location: attendance.php");
    exit();
}

// Refresh attendance record
$check = mysqli_query($conn,"
SELECT * FROM attendance
WHERE employee_id='$employee_id'
AND attendance_date='$today'
");
$row = mysqli_fetch_assoc($check);

// Get recent attendance history (last 7 days)
$recentHistory = mysqli_query($conn, "
SELECT attendance_date, check_in, check_out, working_hours, status
FROM attendance
WHERE employee_id='$employee_id'
ORDER BY attendance_date DESC
LIMIT 7
");

// Get employee shift info
$employeeShift = mysqli_fetch_assoc(mysqli_query($conn, "
SELECT shift_name, shift_start_time, shift_end_time FROM employees WHERE id='$employee_id'
"));
$shiftName = $employeeShift['shift_name'] ?? 'Morning';
$shiftStartTime = !empty($employeeShift['shift_start_time']) ? date('h:i A', strtotime($employeeShift['shift_start_time'])) : '09:00 AM';
$shiftEndTime = !empty($employeeShift['shift_end_time']) ? date('h:i A', strtotime($employeeShift['shift_end_time'])) : '05:00 PM';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Attendance - <?php echo htmlspecialchars($_SESSION['employee_name'] ?? ''); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
    --primary: #2563eb;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --info: #06b6d4;
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
    --shadow: 0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.06);
    --shadow-md: 0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -1px rgba(0,0,0,.06);
    --shadow-lg: 0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -2px rgba(0,0,0,.05);
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--gray-50);
    color: var(--gray-800);
    min-height: 100vh;
}

/* ===== TOP BAR ===== */
.top-bar {
    background: rgba(255,255,255,.95);
    backdrop-filter: blur(20px);
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

.top-bar-left { display: flex; align-items: center; gap: 14px; }

.top-bar-brand {
    display: flex; align-items: center; gap: 10px; text-decoration: none;
}

.top-bar-brand .brand-icon {
    width: 38px; height: 38px;
    background: linear-gradient(135deg, var(--primary), #7c3aed);
    border-radius: 10px; display: flex; align-items: center; justify-content: center;
    font-size: 18px; color: white;
}

.top-bar-brand .brand-text {
    font-size: 18px; font-weight: 800; color: var(--gray-900); letter-spacing: -.5px;
}

.top-bar-right { display: flex; align-items: center; gap: 10px; }

.top-bar-date {
    font-size: 13px; color: var(--gray-500); font-weight: 500;
    padding: 6px 14px; background: var(--gray-100); border-radius: 8px;
    display: flex; align-items: center; gap: 6px;
}

.top-bar-user {
    display: flex; align-items: center; gap: 8px;
    padding: 6px 14px 6px 10px;
    background: rgba(37,99,235,.08); border-radius: 100px;
    font-size: 13px; font-weight: 600; color: var(--primary);
}

.top-bar-user .avatar-sm {
    width: 28px; height: 28px; border-radius: 8px;
    background: linear-gradient(135deg, var(--primary), #7c3aed);
    display: flex; align-items: center; justify-content: center;
    color: white; font-weight: 700; font-size: 12px;
}

/* ===== PAGE CONTAINER ===== */
.page-container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 28px 32px;
}

.page-header {
    margin-bottom: 24px;
}

.page-header h2 {
    font-size: 24px; font-weight: 800; color: var(--gray-900);
    display: flex; align-items: center; gap: 10px;
}

.page-header h2 i { color: var(--primary); }
.page-header h2 small { font-size: 14px; font-weight: 400; color: var(--gray-500); margin-left: 4px; }

/* ===== SHIFT INFO BAR ===== */
.shift-bar {
    background: linear-gradient(135deg, #1e293b, #334155);
    border-radius: var(--radius);
    padding: 14px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 24px;
    color: white;
}

.shift-bar .shift-info {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.shift-bar .shift-info .shift-tag {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    background: rgba(255,255,255,.1);
    border-radius: 100px;
    font-size: 13px;
    font-weight: 500;
}

.shift-bar .shift-info .shift-tag i { font-size: 14px; }

.shift-bar .shift-date {
    font-size: 14px; font-weight: 600; opacity: .9;
}

/* ===== MAIN ATTENDANCE CARD ===== */
.attendance-card {
    background: white;
    border-radius: var(--radius);
    border: 1px solid var(--gray-200);
    box-shadow: var(--shadow);
    overflow: hidden;
    margin-bottom: 24px;
}

.attendance-card .ac-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--gray-200);
    background: var(--gray-50);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.attendance-card .ac-header .ac-title {
    font-size: 15px; font-weight: 700; color: var(--gray-800);
    display: flex; align-items: center; gap: 8px;
}

.attendance-card .ac-header .ac-title i { color: var(--primary); }

.attendance-card .ac-body {
    padding: 32px 24px;
    text-align: center;
}

/* ===== STATUS DISPLAY ===== */
.status-display {
    margin-bottom: 24px;
}

.status-display .status-icon {
    width: 80px; height: 80px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 36px;
    margin: 0 auto 16px;
}

.status-display .status-text {
    font-size: 20px; font-weight: 700;
    margin-bottom: 4px;
}

.status-display .status-sub {
    font-size: 14px; color: var(--gray-500);
}

.status-display .status-time {
    font-size: 28px; font-weight: 800;
    color: var(--gray-900);
    margin-top: 8px;
}

.si-not-started { background: rgba(100,116,139,.1); color: var(--gray-500); }
.si-checked-in { background: rgba(37,99,235,.1); color: var(--primary); }
.si-checked-out { background: rgba(16,185,129,.1); color: var(--success); }
.si-absent { background: rgba(239,68,68,.1); color: var(--danger); }

.st-not-started { color: var(--gray-500); }
.st-checked-in { color: var(--primary); }
.st-checked-out { color: var(--success); }
.st-absent { color: var(--danger); }

/* ===== BUTTONS ===== */
.btn-large {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 16px 36px;
    border-radius: var(--radius-sm);
    font-size: 16px;
    font-weight: 700;
    border: none;
    transition: all .25s ease;
    text-decoration: none;
    color: white;
    cursor: pointer;
}

.btn-large:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
    color: white;
}

.btn-large i { font-size: 20px; }

.btn-check-in {
    background: linear-gradient(135deg, var(--success), #059669);
}

.btn-check-out {
    background: linear-gradient(135deg, var(--danger), #dc2626);
}

.btn-absent {
    background: linear-gradient(135deg, var(--gray-500), #475569);
}

.btn-outline-large {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border-radius: var(--radius-sm);
    font-size: 14px;
    font-weight: 600;
    border: 2px solid var(--gray-200);
    background: transparent;
    transition: all .25s ease;
    text-decoration: none;
    color: var(--gray-600);
    cursor: pointer;
}

.btn-outline-large:hover {
    border-color: var(--gray-400);
    background: var(--gray-50);
}

.action-group {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 14px;
    flex-wrap: wrap;
    margin-top: 20px;
}

/* ===== ATTENDANCE DETAILS TABLE ===== */
.details-table {
    max-width: 500px;
    margin: 20px auto 0;
}

.details-table .detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    border-bottom: 1px solid var(--gray-100);
}

.details-table .detail-row:last-child { border-bottom: none; }

.details-table .detail-row .detail-label {
    font-size: 13px; font-weight: 600;
    color: var(--gray-500);
}

.details-table .detail-row .detail-value {
    font-size: 14px; font-weight: 700;
    color: var(--gray-800);
}

/* ===== HISTORY CARD ===== */
.history-card {
    background: white;
    border-radius: var(--radius);
    border: 1px solid var(--gray-200);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.history-card .hc-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--gray-200);
    background: var(--gray-50);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.history-card .hc-header h5 {
    font-size: 15px; font-weight: 700; color: var(--gray-800); margin: 0;
    display: flex; align-items: center; gap: 8px;
}

.history-card .hc-header h5 i { color: var(--primary); }

/* ===== TABLE ===== */
.table-modern { margin-bottom: 0; }

.table-modern thead th {
    font-size: 11px; font-weight: 700; color: var(--gray-500);
    text-transform: uppercase; letter-spacing: .5px;
    padding: 12px 16px; border-bottom: 2px solid var(--gray-200);
    background: transparent;
}

.table-modern tbody td {
    padding: 12px 16px; font-size: 13px;
    color: var(--gray-700); border-bottom: 1px solid var(--gray-100);
    vertical-align: middle;
}

.table-modern tbody tr:hover { background: var(--gray-50); }
.table-modern tbody tr:last-child td { border-bottom: none; }

/* ===== BADGES ===== */
.badge-modern {
    padding: 4px 12px; border-radius: 100px;
    font-size: 11px; font-weight: 600;
    text-transform: uppercase; letter-spacing: .3px;
    display: inline-block;
}

.badge-present { background: rgba(16,185,129,.12); color: #059669; }
.badge-absent { background: rgba(239,68,68,.12); color: #dc2626; }
.badge-late { background: rgba(245,158,11,.12); color: #d97706; }
.badge-halfday { background: rgba(251,146,60,.12); color: #ea580c; }
.badge-earlyout { background: rgba(99,102,241,.12); color: #4f46e5; }

/* ===== EMPTY STATE ===== */
.empty-state {
    text-align: center; padding: 32px 20px; color: var(--gray-400);
}
.empty-state i { font-size: 36px; margin-bottom: 10px; display: block; }
.empty-state p { font-size: 14px; margin: 0; }

/* ===== DARK MODE ===== */
body.dark-mode {
    background: #0f172a; color: #e2e8f0;
}

.dark-mode .top-bar {
    background: rgba(30,41,59,.95);
    border-bottom-color: rgba(255,255,255,.08);
}
.dark-mode .top-bar-brand .brand-text { color: #f1f5f9; }
.dark-mode .top-bar-date { background: rgba(255,255,255,.06); color: var(--gray-400); }
.dark-mode .top-bar-user { background: rgba(37,99,235,.2); }

.dark-mode .shift-bar { background: linear-gradient(135deg, #0f172a, #1e293b); }

.dark-mode .attendance-card,
.dark-mode .history-card {
    background: #1e293b;
    border-color: rgba(255,255,255,.08);
}
.dark-mode .attendance-card .ac-header,
.dark-mode .history-card .hc-header {
    background: rgba(255,255,255,.04);
    border-color: rgba(255,255,255,.08);
}
.dark-mode .attendance-card .ac-header .ac-title { color: #e2e8f0; }

.dark-mode .status-display .status-time { color: #f1f5f9; }

.dark-mode .details-table .detail-row { border-color: rgba(255,255,255,.06); }
.dark-mode .details-table .detail-row .detail-label { color: var(--gray-400); }
.dark-mode .details-table .detail-row .detail-value { color: #e2e8f0; }

.dark-mode .table-modern thead th {
    color: var(--gray-400); border-color: rgba(255,255,255,.08);
}
.dark-mode .table-modern tbody td {
    color: #cbd5e1; border-color: rgba(255,255,255,.06);
}
.dark-mode .table-modern tbody tr:hover { background: rgba(255,255,255,.04); }

.dark-mode .page-header h2 { color: #e2e8f0; }
.dark-mode .page-header h2 small { color: var(--gray-400); }

.dark-mode .btn-outline-large {
    border-color: rgba(255,255,255,.15);
    color: var(--gray-300);
}
.dark-mode .btn-outline-large:hover {
    border-color: rgba(255,255,255,.25);
    background: rgba(255,255,255,.04);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 991px) {
    .page-container { padding: 20px; }
    .top-bar { padding: 0 20px; }
    .top-bar-date { display: none; }
}

@media (max-width: 768px) {
    .page-header h2 { font-size: 20px; }
    .page-header h2 small { display: none; }
    .attendance-card .ac-body { padding: 24px 16px; }
    .btn-large { padding: 14px 24px; font-size: 14px; }
    .shift-bar .shift-info .shift-tag { font-size: 12px; }
}

@media (max-width: 480px) {
    .page-container { padding: 14px; }
    .top-bar { padding: 0 14px; height: 60px; }
    .top-bar-user span { display: none; }
    .shift-bar { padding: 12px 16px; }
    .status-display .status-icon { width: 64px; height: 64px; font-size: 28px; }
    .status-display .status-text { font-size: 18px; }
    .status-display .status-time { font-size: 22px; }
    .action-group { flex-direction: column; }
    .btn-large { width: 100%; justify-content: center; }
}
</style>
</head>
<body>

<!-- Top Bar -->
<div class="top-bar">
    <div class="top-bar-left">
        <a href="dashboard.php" class="top-bar-brand">
            <div class="brand-icon"><i class="fa-solid fa-building"></i></div>
            <div class="brand-text">EMS</div>
        </a>
    </div>
    <div class="top-bar-right">
        <span class="top-bar-date"><i class="fa-regular fa-calendar"></i> <?=date('d M Y')?></span>
        <span class="top-bar-user">
            <div class="avatar-sm"><?php echo strtoupper(substr($_SESSION['employee_name'] ?? 'E', 0, 1)); ?></div>
            <span><?php echo htmlspecialchars($_SESSION['employee_name'] ?? 'Employee'); ?></span>
        </span>
        <?php include("../dark_mode.php"); ?>
        <a href="dashboard.php" class="btn btn-primary btn-sm rounded-pill px-3"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</div>

<!-- Page Container -->
<div class="page-container">

    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="fa-solid fa-clock"></i> Attendance <small>/ Mark your daily attendance</small></h2>
    </div>

    <!-- Shift Info Bar -->
    <div class="shift-bar">
        <div class="shift-info">
            <span class="shift-tag"><i class="fa-solid fa-briefcase"></i> <?php echo htmlspecialchars($shiftName); ?> Shift</span>
            <span class="shift-tag"><i class="fa-solid fa-right-to-bracket"></i> <?php echo $shiftStartTime; ?></span>
            <span class="shift-tag"><i class="fa-solid fa-right-from-bracket"></i> <?php echo $shiftEndTime; ?></span>
        </div>
        <div class="shift-date"><i class="fa-regular fa-calendar"></i> <?php echo date('l, d M Y'); ?></div>
    </div>

    <!-- Main Attendance Card -->
    <div class="attendance-card">
        <div class="ac-header">
            <span class="ac-title"><i class="fa-solid fa-clipboard-check"></i> Today's Attendance</span>
            <span class="text-muted" style="font-size: 13px; font-weight: 500;"><?php echo date('h:i A'); ?></span>
        </div>
        <div class="ac-body">

            <?php if(!$row): ?>
                <!-- Not Checked In -->
                <div class="status-display">
                    <div class="status-icon si-not-started"><i class="fa-regular fa-clock"></i></div>
                    <div class="status-text st-not-started">Not Started</div>
                    <div class="status-sub">You haven't marked your attendance for today yet</div>
                </div>
                <div class="action-group">
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="check_in" class="btn-large btn-check-in">
                            <i class="fa-solid fa-right-to-bracket"></i> Check In
                        </button>
                    </form>
                </div>

            <?php elseif($row['check_in'] != "" && $row['check_out'] == ""): ?>
                <!-- Checked In - Waiting for Check Out -->
                <div class="status-display">
                    <div class="status-icon si-checked-in"><i class="fa-solid fa-circle-check"></i></div>
                    <div class="status-text st-checked-in">Checked In</div>
                    <div class="status-sub">You are currently checked in</div>
                    <div class="status-time"><?php echo date('h:i A', strtotime($row['check_in'])); ?></div>
                </div>
                <div class="details-table">
                    <div class="detail-row">
                        <span class="detail-label">Check In Time</span>
                        <span class="detail-value"><?php echo date('h:i A', strtotime($row['check_in'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status</span>
                        <span class="detail-value">
                            <?php
                            $sClass = 'badge-present';
                            if($row['status'] == 'Late') $sClass = 'badge-late';
                            ?>
                            <span class="badge-modern <?php echo $sClass; ?>"><?php echo $row['status']; ?></span>
                        </span>
                    </div>
                </div>
                <div class="action-group">
                    <form method="POST">
                        <button type="submit" name="check_out" class="btn-large btn-check-out">
                            <i class="fa-solid fa-right-from-bracket"></i> Check Out
                        </button>
                    </form>
                </div>

            <?php elseif($row['status'] == 'Absent'): ?>
                <!-- Marked Absent -->
                <div class="status-display">
                    <div class="status-icon si-absent"><i class="fa-solid fa-xmark-circle"></i></div>
                    <div class="status-text st-absent">Absent</div>
                    <div class="status-sub">You have been marked as absent for today</div>
                </div>

            <?php else: ?>
                <!-- Completed -->
                <div class="status-display">
                    <div class="status-icon si-checked-out"><i class="fa-solid fa-check-double"></i></div>
                    <div class="status-text st-checked-out">Completed</div>
                    <div class="status-sub">Today's attendance has been completed</div>
                </div>
                <div class="details-table">
                    <div class="detail-row">
                        <span class="detail-label">Check In</span>
                        <span class="detail-value"><?php echo date('h:i A', strtotime($row['check_in'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Check Out</span>
                        <span class="detail-value"><?php echo date('h:i A', strtotime($row['check_out'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Working Hours</span>
                        <span class="detail-value"><?php echo $row['working_hours']; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status</span>
                        <span class="detail-value">
                            <?php
                            $sClass = 'badge-present';
                            if($row['status'] == 'Late') $sClass = 'badge-late';
                            elseif($row['status'] == 'Half Day') $sClass = 'badge-halfday';
                            elseif($row['status'] == 'Early Out') $sClass = 'badge-earlyout';
                            elseif($row['status'] == 'Absent') $sClass = 'badge-absent';
                            ?>
                            <span class="badge-modern <?php echo $sClass; ?>"><?php echo $row['status']; ?></span>
                        </span>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Recent History -->
    <div class="history-card">
        <div class="hc-header">
            <h5><i class="fa-solid fa-clock-rotate-left"></i> Recent History</h5>
            <a href="attendance_history.php" class="btn btn-sm btn-outline-primary rounded-pill">View All</a>
        </div>
        <div class="table-responsive">
            <table class="table table-modern">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Hours</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if($recentHistory && mysqli_num_rows($recentHistory) > 0): ?>
                    <?php while($h = mysqli_fetch_assoc($recentHistory)):
                        $hBadge = 'badge-absent';
                        if($h['status'] == 'Present') $hBadge = 'badge-present';
                        elseif($h['status'] == 'Late') $hBadge = 'badge-late';
                        elseif($h['status'] == 'Half Day') $hBadge = 'badge-halfday';
                        elseif($h['status'] == 'Early Out') $hBadge = 'badge-earlyout';
                    ?>
                    <tr>
                        <td><span class="fw-medium"><?php echo date('d-m-Y', strtotime($h['attendance_date'])); ?></span></td>
                        <td><?php echo !empty($h['check_in']) ? date('h:i A', strtotime($h['check_in'])) : '<span class="text-muted">—</span>'; ?></td>
                        <td><?php echo !empty($h['check_out']) ? date('h:i A', strtotime($h['check_out'])) : '<span class="text-muted">—</span>'; ?></td>
                        <td><?php echo !empty($h['working_hours']) ? $h['working_hours'] : '<span class="text-muted">—</span>'; ?></td>
                        <td><span class="badge-modern <?php echo $hBadge; ?>"><?php echo $h['status']; ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">No attendance history available</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<div class="text-center mt-3 mb-4">
    <a href="dashboard.php" class="btn btn-primary"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
</div>

</body>
</html>
