<?php
session_start();

if(!isset($_SESSION['admin']))
{
    header("Location: ../index.php");
    exit();
}

include("../config/db.php");

$search = "";
$from = "";
$to = "";

$sql = "SELECT
attendance.*,
employees.full_name,
employees.department
FROM attendance
LEFT JOIN employees
ON attendance.employee_id=employees.id
WHERE 1=1";

if(isset($_GET['filter']))
{
    $search = mysqli_real_escape_string($conn,$_GET['search']);
    $from = $_GET['from'];
    $to = $_GET['to'];

    if($search!="")
    {
        $sql .= " AND (
        employees.full_name LIKE '%$search%'
        OR employees.employee_id LIKE '%$search%'
        )";
    }

    if($from!="" && $to!="")
    {
        $sql .= " AND attendance_date BETWEEN '$from' AND '$to'";
    }
}

$sql .= " ORDER BY attendance_date DESC, employees.full_name ASC";

$result = mysqli_query($conn,$sql);

// Get total counts for summary cards
$totalPresent = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM attendance a JOIN employees e ON a.employee_id=e.id WHERE a.status='Present' " . (($from && $to) ? "AND a.attendance_date BETWEEN '$from' AND '$to'" : "")))['c'];
$totalAbsent = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM attendance a JOIN employees e ON a.employee_id=e.id WHERE a.status='Absent' " . (($from && $to) ? "AND a.attendance_date BETWEEN '$from' AND '$to'" : "")))['c'];
$totalLate = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM attendance a JOIN employees e ON a.employee_id=e.id WHERE a.status='Late' " . (($from && $to) ? "AND a.attendance_date BETWEEN '$from' AND '$to'" : "")))['c'];
$totalHalfDay = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM attendance a JOIN employees e ON a.employee_id=e.id WHERE a.status='Half Day' " . (($from && $to) ? "AND a.attendance_date BETWEEN '$from' AND '$to'" : "")))['c'];
$totalRecords = mysqli_num_rows($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Attendance Report - EMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
    --primary: #2563eb;
    --primary-dark: #1d4ed8;
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
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
}

.top-bar-brand .brand-icon {
    width: 38px; height: 38px;
    background: linear-gradient(135deg, var(--primary), #7c3aed);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px; color: white;
}

.top-bar-brand .brand-text {
    font-size: 18px; font-weight: 800;
    color: var(--gray-900); letter-spacing: -.5px;
}

.top-bar-right { display: flex; align-items: center; gap: 10px; }

.top-bar-date {
    font-size: 13px; color: var(--gray-500); font-weight: 500;
    padding: 6px 14px; background: var(--gray-100); border-radius: 8px;
    display: flex; align-items: center; gap: 6px;
}

/* ===== PAGE CONTAINER ===== */
.page-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 28px 32px;
}

.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
}

.page-header h2 {
    font-size: 24px; font-weight: 800;
    color: var(--gray-900);
    display: flex; align-items: center; gap: 10px;
}

.page-header h2 i { color: var(--primary); }
.page-header h2 small { font-size: 14px; font-weight: 400; color: var(--gray-500); }

/* ===== STATS ROW ===== */
.stats-row {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 14px;
    margin-bottom: 24px;
}

.stat-card {
    background: white;
    border-radius: var(--radius);
    padding: 18px 20px;
    border: 1px solid var(--gray-200);
    box-shadow: var(--shadow);
    transition: all .3s ease;
    display: flex;
    align-items: center;
    gap: 16px;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
}

.stat-card .stat-icon {
    width: 48px; height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.stat-card .stat-info .stat-label {
    font-size: 12px; font-weight: 600;
    color: var(--gray-500); text-transform: uppercase;
    letter-spacing: .5px;
}

.stat-card .stat-info .stat-value {
    font-size: 24px; font-weight: 800;
    color: var(--gray-900); line-height: 1.3;
}

.sc-primary .stat-icon { background: rgba(37,99,235,.12); color: var(--primary); }
.sc-success .stat-icon { background: rgba(16,185,129,.12); color: var(--success); }
.sc-danger .stat-icon { background: rgba(239,68,68,.12); color: var(--danger); }
.sc-warning .stat-icon { background: rgba(245,158,11,.12); color: var(--warning); }
.sc-info .stat-icon { background: rgba(6,182,212,.12); color: var(--info); }

/* ===== FILTER CARD ===== */
.filter-card {
    background: white;
    border-radius: var(--radius);
    border: 1px solid var(--gray-200);
    box-shadow: var(--shadow);
    padding: 24px;
    margin-bottom: 24px;
}

.filter-card .filter-title {
    font-size: 14px; font-weight: 700;
    color: var(--gray-800);
    margin-bottom: 16px;
    display: flex; align-items: center; gap: 8px;
}

.filter-card .filter-title i { color: var(--primary); }

/* ===== TABLE CARD ===== */
.table-card {
    background: white;
    border-radius: var(--radius);
    border: 1px solid var(--gray-200);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.table-card .table-header {
    padding: 16px 24px;
    border-bottom: 1px solid var(--gray-200);
    background: var(--gray-50);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.table-card .table-header h5 {
    font-size: 15px; font-weight: 700;
    color: var(--gray-800); margin: 0;
    display: flex; align-items: center; gap: 8px;
}

.table-card .table-header h5 i { color: var(--primary); }

.table-card .table-header .record-count {
    font-size: 12px; font-weight: 600;
    color: var(--gray-500);
    background: var(--gray-200);
    padding: 4px 12px;
    border-radius: 100px;
}

/* ===== TABLE ===== */
.table-modern {
    margin-bottom: 0;
}

.table-modern thead th {
    font-size: 11px; font-weight: 700;
    color: var(--gray-500); text-transform: uppercase;
    letter-spacing: .5px;
    padding: 14px 16px;
    border-bottom: 2px solid var(--gray-200);
    background: var(--gray-50);
    white-space: nowrap;
}

.table-modern tbody td {
    padding: 14px 16px;
    font-size: 13px;
    color: var(--gray-700);
    border-bottom: 1px solid var(--gray-100);
    vertical-align: middle;
}

.table-modern tbody tr:hover { background: var(--gray-50); }
.table-modern tbody tr:last-child td { border-bottom: none; }

.emp-info {
    display: flex; align-items: center; gap: 10px;
}

.emp-avatar {
    width: 34px; height: 34px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--primary), #7c3aed);
    display: flex; align-items: center; justify-content: center;
    color: white; font-weight: 700; font-size: 13px;
    flex-shrink: 0;
}

.emp-details .emp-name {
    font-size: 13px; font-weight: 600;
    color: var(--gray-800);
}

.emp-details .emp-dept {
    font-size: 11px; color: var(--gray-500);
}

/* ===== BADGES ===== */
.badge-modern {
    padding: 5px 14px;
    border-radius: 100px;
    font-size: 11px; font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .3px;
    display: inline-block;
}

.badge-present { background: rgba(16,185,129,.12); color: #059669; }
.badge-absent { background: rgba(239,68,68,.12); color: #dc2626; }
.badge-late { background: rgba(245,158,11,.12); color: #d97706; }
.badge-halfday { background: rgba(251,146,60,.12); color: #ea580c; }
.badge-earlyout { background: rgba(99,102,241,.12); color: #4f46e5; }

.time-cell {
    font-family: 'Inter', monospace;
    font-size: 13px;
    font-weight: 600;
    color: var(--gray-700);
}

.time-cell .time-empty {
    color: var(--gray-400);
    font-weight: 400;
}

/* ===== TABLE FOOTER ===== */
.table-footer {
    padding: 16px 24px;
    border-top: 1px solid var(--gray-200);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}

/* ===== DARK MODE ===== */
body.dark-mode {
    background: #0f172a;
    color: #e2e8f0;
}

.dark-mode .top-bar {
    background: rgba(30,41,59,.95);
    border-bottom-color: rgba(255,255,255,.08);
}

.dark-mode .top-bar-brand .brand-text { color: #f1f5f9; }
.dark-mode .top-bar-date { background: rgba(255,255,255,.06); color: var(--gray-400); }

.dark-mode .stat-card,
.dark-mode .filter-card,
.dark-mode .table-card {
    background: #1e293b;
    border-color: rgba(255,255,255,.08);
}

.dark-mode .stat-card .stat-info .stat-value { color: #f1f5f9; }

.dark-mode .filter-card .filter-title { color: #e2e8f0; }
.dark-mode .filter-card .form-control {
    background: rgba(255,255,255,.06);
    border-color: rgba(255,255,255,.1);
    color: #e2e8f0;
}
.dark-mode .filter-card .form-control:focus {
    background: rgba(255,255,255,.08);
    color: #e2e8f0;
}
.dark-mode .filter-card .form-label { color: var(--gray-400); }

.dark-mode .table-card .table-header {
    background: rgba(255,255,255,.04);
    border-color: rgba(255,255,255,.08);
}
.dark-mode .table-card .table-header h5 { color: #e2e8f0; }
.dark-mode .table-card .table-header .record-count {
    background: rgba(255,255,255,.08);
    color: var(--gray-400);
}

.dark-mode .table-modern thead th {
    color: var(--gray-400);
    border-color: rgba(255,255,255,.08);
    background: rgba(255,255,255,.04);
}
.dark-mode .table-modern tbody td {
    color: #cbd5e1;
    border-color: rgba(255,255,255,.06);
}
.dark-mode .table-modern tbody tr:hover { background: rgba(255,255,255,.04); }

.dark-mode .table-footer {
    border-color: rgba(255,255,255,.08);
}

.dark-mode .emp-details .emp-name { color: #e2e8f0; }
.dark-mode .emp-details .emp-dept { color: var(--gray-400); }
.dark-mode .time-cell { color: #cbd5e1; }
.dark-mode .time-cell .time-empty { color: var(--gray-500); }

.dark-mode .page-header h2 { color: #e2e8f0; }
.dark-mode .page-header h2 small { color: var(--gray-400); }

/* ===== RESPONSIVE ===== */
@media (max-width: 1200px) {
    .stats-row { grid-template-columns: repeat(3, 1fr); }
}

@media (max-width: 991px) {
    .stats-row { grid-template-columns: repeat(3, 1fr); }
    .page-container { padding: 20px; }
    .top-bar { padding: 0 20px; }
    .top-bar-date { display: none; }
}

@media (max-width: 768px) {
    .stats-row { grid-template-columns: repeat(2, 1fr); }
    .page-header h2 { font-size: 20px; }
    .page-header h2 small { display: none; }
}

@media (max-width: 480px) {
    .stats-row { grid-template-columns: 1fr 1fr; gap: 10px; }
    .stat-card { padding: 14px; }
    .stat-card .stat-icon { width: 40px; height: 40px; font-size: 16px; }
    .stat-card .stat-info .stat-value { font-size: 20px; }
    .page-container { padding: 14px; }
    .top-bar { padding: 0 14px; height: 60px; }
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
        <?php include("../dark_mode.php"); ?>
        <a href="dashboard.php" class="btn btn-primary btn-sm rounded-pill px-3"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</div>

<!-- Page Container -->
<div class="page-container">

    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="fa-solid fa-clock"></i> Attendance Report <small>/ Track employee attendance</small></h2>
        <a href="export_excel.php" class="btn btn-success rounded-pill px-4"><i class="fa fa-file-excel"></i> Export Excel</a>
    </div>

    <!-- Stats Cards -->
    <div class="stats-row">
        <div class="stat-card sc-primary">
            <div class="stat-icon"><i class="fa-solid fa-list"></i></div>
            <div class="stat-info">
                <div class="stat-label">Total Records</div>
                <div class="stat-value"><?=$totalRecords?></div>
            </div>
        </div>
        <div class="stat-card sc-success">
            <div class="stat-icon"><i class="fa-solid fa-check-circle"></i></div>
            <div class="stat-info">
                <div class="stat-label">Present</div>
                <div class="stat-value"><?=$totalPresent?></div>
            </div>
        </div>
        <div class="stat-card sc-danger">
            <div class="stat-icon"><i class="fa-solid fa-times-circle"></i></div>
            <div class="stat-info">
                <div class="stat-label">Absent</div>
                <div class="stat-value"><?=$totalAbsent?></div>
            </div>
        </div>
        <div class="stat-card sc-warning">
            <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
            <div class="stat-info">
                <div class="stat-label">Late</div>
                <div class="stat-value"><?=$totalLate?></div>
            </div>
        </div>
        <div class="stat-card sc-info">
            <div class="stat-icon"><i class="fa-solid fa-sun-half"></i></div>
            <div class="stat-info">
                <div class="stat-label">Half Day</div>
                <div class="stat-value"><?=$totalHalfDay?></div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="filter-card">
        <div class="filter-title"><i class="fa-solid fa-filter"></i> Filter Records</div>
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Employee Name / ID</label>
                <input type="text" name="search" class="form-control" placeholder="Search by name or ID..." value="<?php echo $search; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">From Date</label>
                <input type="date" name="from" class="form-control" value="<?php echo $from; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">To Date</label>
                <input type="date" name="to" class="form-control" value="<?php echo $to; ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" name="filter" class="btn btn-primary w-100 rounded-pill"><i class="fa fa-search"></i> Filter</button>
                <a href="attendance_report.php" class="btn btn-outline-secondary rounded-pill w-100"><i class="fa fa-undo"></i></a>
            </div>
        </form>
    </div>

    <!-- Table Card -->
    <div class="table-card">
        <div class="table-header">
            <h5><i class="fa-solid fa-table"></i> Attendance Records</h5>
            <span class="record-count"><i class="fa-regular fa-file-lines"></i> <?=$totalRecords?> Records</span>
        </div>
        <div class="table-responsive">
            <table class="table table-modern">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Date</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Working Hours</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if(mysqli_num_rows($result) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($result)){
                        $badgeClass = 'badge-absent';
                        $statusLabel = $row['status'] ?: 'Absent';
                        if($row['status'] == 'Present') $badgeClass = 'badge-present';
                        elseif($row['status'] == 'Late') $badgeClass = 'badge-late';
                        elseif($row['status'] == 'Half Day') $badgeClass = 'badge-halfday';
                        elseif($row['status'] == 'Early Out') $badgeClass = 'badge-earlyout';
                        elseif($row['status'] == 'Absent' || empty($row['status'])) $badgeClass = 'badge-absent';
                    ?>
                    <tr>
                        <td>
                            <div class="emp-info">
                                <div class="emp-avatar"><?php echo strtoupper(substr($row['full_name'], 0, 1)); ?></div>
                                <div class="emp-details">
                                    <div class="emp-name"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                    <div class="emp-dept">ID: <?php echo htmlspecialchars($row['employee_id']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($row['department']); ?></td>
                        <td><span class="fw-medium"><?php echo date("d-m-Y", strtotime($row['attendance_date'])); ?></span></td>
                        <td class="time-cell">
                            <?php if(!empty($row['check_in'])): ?>
                                <?php echo date("h:i A", strtotime($row['check_in'])); ?>
                            <?php else: ?>
                                <span class="time-empty">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="time-cell">
                            <?php if(!empty($row['check_out'])): ?>
                                <?php echo date("h:i A", strtotime($row['check_out'])); ?>
                            <?php else: ?>
                                <span class="time-empty">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="time-cell">
                            <?php if(!empty($row['working_hours'])): ?>
                                <?php echo $row['working_hours']; ?>
                            <?php else: ?>
                                <span class="time-empty">—</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge-modern <?php echo $badgeClass; ?>"><?php echo $statusLabel; ?></span></td>
                    </tr>
                    <?php } ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">
                        <i class="fa-regular fa-calendar" style="font-size: 36px; display: block; margin-bottom: 10px;"></i>
                        No attendance records found
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            <span class="text-muted" style="font-size: 13px;">
                <i class="fa-regular fa-clock"></i> Showing attendance records
            </span>
            <a href="dashboard.php" class="btn btn-primary"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>

</div>

</body>
</html>