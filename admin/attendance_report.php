<?php
session_start();

if(!isset($_SESSION['admin']))
{
    header("Location: ../index.php");
    exit();
}

include("../config/db.php");
include("admincheck_role.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_status_request'])) {
    ems_verify_csrf();
    $requestId=(int)($_POST['request_id']??0); $decision=$_POST['decision']??''; $comment=trim($_POST['review_comment']??'');
    if (in_array($decision,['Approved','Rejected'],true)) {
        mysqli_begin_transaction($conn);
        $q=mysqli_prepare($conn,"SELECT * FROM attendance_status_requests WHERE id=? AND status='Pending' FOR UPDATE"); mysqli_stmt_bind_param($q,'i',$requestId); mysqli_stmt_execute($q); $request=mysqli_fetch_assoc(mysqli_stmt_get_result($q));
        if ($request && ((int)$request['incharge_id']===(int)($_SESSION['admin_id']??0) || $admin_role==='Super Admin')) {
            if ($decision==='Approved') { $approvedStatus=$request['requested_status'];$allowedStatuses=['Present','Absent','Late','Half Day','Early Out','Off Day','NH'];if(!in_array($approvedStatus,$allowedStatuses,true)){mysqli_rollback($conn);header('Location: attendance_report.php?status_error=invalid');exit();}$reviewerId=(int)($_SESSION['admin_id']??0);$approvedAttendanceId=(int)$request['attendance_id'];$u=mysqli_prepare($conn,'UPDATE attendance SET status=?,status_locked=1,status_updated_by=?,status_updated_at=NOW() WHERE id=? AND status_locked=0'); mysqli_stmt_bind_param($u,'sii',$approvedStatus,$reviewerId,$approvedAttendanceId); mysqli_stmt_execute($u);if(mysqli_stmt_affected_rows($u)!==1){mysqli_rollback($conn);header('Location: attendance_report.php?status_error=locked');exit();} }
            $u=mysqli_prepare($conn,'UPDATE attendance_status_requests SET status=?,review_comment=?,reviewed_at=NOW() WHERE id=?'); mysqli_stmt_bind_param($u,'ssi',$decision,$comment,$requestId); mysqli_stmt_execute($u);
            mysqli_commit($conn);
        } else mysqli_rollback($conn);
    }
    header('Location: attendance_report.php'); exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_day_status'])) {
    ems_verify_csrf();
    $allowedAttendanceStatuses = ['Present','Absent','Late','Half Day','Early Out','Off Day','NH'];
    $updatedBy = (int)($_SESSION['admin_id'] ?? 0);
    $submittedStatuses = $_POST['statuses'] ?? [];
    if (is_array($submittedStatuses)) {
        $stmt = mysqli_prepare($conn, "UPDATE attendance SET status=?,status_locked=1,status_updated_by=?,status_updated_at=NOW() WHERE id=? AND status_locked=0");
        foreach ($submittedStatuses as $attendanceId => $dayStatus) {
            $attendanceId = (int)$attendanceId;
            $dayStatus = trim((string)$dayStatus);
            if ($attendanceId < 1 || !in_array($dayStatus, $allowedAttendanceStatuses, true)) continue;
            if ($admin_role==='Super Admin') {
                mysqli_stmt_bind_param($stmt, 'sii', $dayStatus, $updatedBy, $attendanceId); mysqli_stmt_execute($stmt);
            } else {
                $roleOrder=['Employee','Team Lead','Supervisor','Assistant Manager','Senior Assistant Manager','Operations Manager','VP','Admin','Super Admin'];
                $currentIndex=array_search($admin_role,$roleOrder,true); $candidateRoles=$currentIndex===false?['Super Admin']:array_slice($roleOrder,$currentIndex+1);
                $incharge=null;
                foreach($candidateRoles as $candidateRole){$iq=mysqli_prepare($conn,'SELECT id,name FROM admin WHERE role=? ORDER BY id LIMIT 1');mysqli_stmt_bind_param($iq,'s',$candidateRole);mysqli_stmt_execute($iq);$incharge=mysqli_fetch_assoc(mysqli_stmt_get_result($iq));if($incharge)break;}
                if(!$incharge){$iq=mysqli_query($conn,"SELECT id,name FROM admin WHERE role IN ('CEO','Super Admin','Admin') ORDER BY id LIMIT 1");$incharge=$iq?mysqli_fetch_assoc($iq):null;}
                if($incharge){$requesterName=$_SESSION['admin_name']??$_SESSION['admin']??'User';$inchargeId=(int)$incharge['id'];$inchargeName=$incharge['name'];$rq=mysqli_prepare($conn,"INSERT INTO attendance_status_requests(attendance_id,requested_status,requested_by,requested_by_name,incharge_id,incharge_name) SELECT ?,?,?,?,?,? WHERE NOT EXISTS(SELECT 1 FROM attendance_status_requests WHERE attendance_id=? AND status='Pending')");mysqli_stmt_bind_param($rq,'isiissi',$attendanceId,$dayStatus,$updatedBy,$requesterName,$inchargeId,$inchargeName,$attendanceId);mysqli_stmt_execute($rq);}
            }
        }
    }
    $returnQuery = http_build_query(array_filter([
        'filter' => $_POST['return_filter'] ?? '',
        'search' => $_POST['return_search'] ?? '',
        'from' => $_POST['return_from'] ?? '',
        'to' => $_POST['return_to'] ?? '',
        'department' => $_POST['return_department'] ?? '',
        'employee_id' => $_POST['return_employee_id'] ?? '',
        'month' => $_POST['return_month'] ?? '',
        'year' => $_POST['return_year'] ?? '',
        'inactive' => $_POST['return_inactive'] ?? '',
        'view_mode' => $_POST['return_view_mode'] ?? '',
    ], static fn($value) => $value !== ''));
    header('Location: attendance_report.php' . ($returnQuery ? '?' . $returnQuery : ''));
    exit();
}

$search = trim((string)($_GET['search'] ?? ''));
$department = trim((string)($_GET['department'] ?? ''));
$selectedEmployee = max(0, (int)($_GET['employee_id'] ?? 0));
$selectedMonth = min(12, max(1, (int)($_GET['month'] ?? date('n'))));
$selectedYear = min((int)date('Y') + 1, max(2020, (int)($_GET['year'] ?? date('Y'))));
$includeInactive = isset($_GET['inactive']) && $_GET['inactive'] === '1';
$requestedViewMode = (string)($_GET['view_mode'] ?? 'payroll');
$viewMode = in_array($requestedViewMode, ['payroll','month'], true) ? $requestedViewMode : 'payroll';
$from = sprintf('%04d-%02d-01', $selectedYear, $selectedMonth);
$to = date('Y-m-t', strtotime($from));

$companyName = 'Employee Management System';
try { $companyQuery=$conn->query('SELECT company_name FROM company_settings WHERE id=1 LIMIT 1'); if($companyQuery&&($companyRow=$companyQuery->fetch_assoc()))$companyName=trim((string)$companyRow['company_name'])?:$companyName; } catch(Throwable $ignored) {}
$departmentsList=[]; $departmentQuery=$conn->query("SELECT DISTINCT department FROM employees WHERE department IS NOT NULL AND department<>'' ORDER BY department"); while($departmentQuery&&($d=$departmentQuery->fetch_assoc()))$departmentsList[]=$d['department'];
$employeeList=[]; $employeeQuery=$conn->query('SELECT id,employee_id,full_name,department,status,is_active FROM employees ORDER BY full_name'); while($employeeQuery&&($e=$employeeQuery->fetch_assoc()))$employeeList[]=$e;

$sql = "SELECT attendance.*,employees.employee_id AS emp_code,employees.full_name,employees.department,employees.shift_start_time,employees.shift_end_time FROM attendance JOIN employees ON attendance.employee_id=employees.id WHERE attendance.attendance_date BETWEEN ? AND ?";
$types='ss'; $params=[$from,$to];
if(!$includeInactive){$sql.=" AND employees.status='Active' AND employees.is_active=1";}
if($department!==''){$sql.=' AND employees.department=?';$types.='s';$params[]=$department;}
if($selectedEmployee>0){$sql.=' AND employees.id=?';$types.='i';$params[]=$selectedEmployee;}
if($search!==''){$like='%'.$search.'%';$sql.=' AND (employees.full_name LIKE ? OR employees.employee_id LIKE ?)';$types.='ss';$params[]=$like;$params[]=$like;}
$sql .= $viewMode==='month' ? ' ORDER BY employees.full_name,attendance.attendance_date' : ' ORDER BY attendance.attendance_date,employees.full_name';
$reportStmt=$conn->prepare($sql);$reportStmt->bind_param($types,...$params);$reportStmt->execute();$result=$reportStmt->get_result();

$reportRows = $result->fetch_all(MYSQLI_ASSOC);
$existingDates = [];
foreach ($reportRows as $attendanceIndex => &$attendanceRow) {
    $attendanceRow['is_leave'] = false;
    $existingDates[$attendanceRow['employee_id'] . '|' . $attendanceRow['attendance_date']] = $attendanceIndex;
}
unset($attendanceRow);

// Include each applied leave date in the attendance report, without duplicating an attendance row.
$leaveSql = "SELECT lr.id AS leave_id,lr.employee_id,lr.leave_type,lr.start_date,lr.end_date,lr.status AS leave_status,
                    e.employee_id AS emp_code,e.full_name,e.department,e.shift_start_time,e.shift_end_time
             FROM leave_requests lr JOIN employees e ON e.id=lr.employee_id
             WHERE lr.status<>'Rejected' AND lr.start_date<=? AND lr.end_date>=?";
$leaveTypes = 'ss'; $leaveParams = [$to, $from];
if (!$includeInactive) $leaveSql .= " AND e.status='Active' AND e.is_active=1";
if ($department !== '') { $leaveSql .= ' AND e.department=?'; $leaveTypes .= 's'; $leaveParams[] = $department; }
if ($selectedEmployee > 0) { $leaveSql .= ' AND e.id=?'; $leaveTypes .= 'i'; $leaveParams[] = $selectedEmployee; }
if ($search !== '') { $like = '%' . $search . '%'; $leaveSql .= ' AND (e.full_name LIKE ? OR e.employee_id LIKE ?)'; $leaveTypes .= 'ss'; $leaveParams[] = $like; $leaveParams[] = $like; }
$leaveStmt = $conn->prepare($leaveSql); $leaveStmt->bind_param($leaveTypes, ...$leaveParams); $leaveStmt->execute();
$leaveResult = $leaveStmt->get_result();
while ($leave = $leaveResult->fetch_assoc()) {
    $leaveStart = max(strtotime($from), strtotime($leave['start_date']));
    $leaveEnd = min(strtotime($to), strtotime($leave['end_date']));
    for ($day = $leaveStart; $day <= $leaveEnd; $day = strtotime('+1 day', $day)) {
        $leaveDate = date('Y-m-d', $day);
        $key = $leave['employee_id'] . '|' . $leaveDate;
        if (isset($existingDates[$key])) {
            $existingIndex = $existingDates[$key];
            $reportRows[$existingIndex]['is_leave'] = true;
            $reportRows[$existingIndex]['leave_type'] = $leave['leave_type'];
            $reportRows[$existingIndex]['leave_status'] = $leave['leave_status'];
            continue;
        }
        $reportRows[] = array_merge($leave, [
            'id'=>0, 'attendance_date'=>$leaveDate, 'check_in'=>null, 'check_out'=>null,
            'working_hours'=>null, 'status'=>'Leave', 'status_locked'=>1, 'is_leave'=>true
        ]);
        $existingDates[$key] = array_key_last($reportRows);
    }
}
usort($reportRows, static function(array $a, array $b) use ($viewMode): int {
    $left = $viewMode === 'month' ? $a['full_name'].'|'.$a['attendance_date'] : $a['attendance_date'].'|'.$a['full_name'];
    $right = $viewMode === 'month' ? $b['full_name'].'|'.$b['attendance_date'] : $b['attendance_date'].'|'.$b['full_name'];
    return strcasecmp($left, $right);
});

// Keep summary cards aligned with every selected report filter.
$totalPresent = $totalAbsent = $totalLate = $totalHalfDay = 0;
foreach ($reportRows as $summaryRow) {
    $summaryStatus = strtolower(trim((string)($summaryRow['status'] ?? '')));
    if ($summaryStatus === 'present') $totalPresent++;
    elseif ($summaryStatus === 'absent') $totalAbsent++;
    elseif ($summaryStatus === 'late') $totalLate++;
    elseif ($summaryStatus === 'half day') $totalHalfDay++;
}
$totalRecords = count($reportRows);
$pendingForMe=[]; $pendingStmt=mysqli_prepare($conn,"SELECT sr.*,a.attendance_date,e.full_name,e.department FROM attendance_status_requests sr JOIN attendance a ON a.id=sr.attendance_id JOIN employees e ON e.id=a.employee_id WHERE sr.status='Pending' AND (sr.incharge_id=? OR ?='Super Admin') ORDER BY sr.id DESC"); mysqli_stmt_bind_param($pendingStmt,'is',$adminIdForPending,$admin_role); $adminIdForPending=(int)($_SESSION['admin_id']??0); mysqli_stmt_execute($pendingStmt); $pendingForMe=mysqli_fetch_all(mysqli_stmt_get_result($pendingStmt),MYSQLI_ASSOC);
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

    <?php if(($_GET['status_error'] ?? '') === 'locked'): ?>
        <div class="alert alert-warning">This attendance record was already locked or changed. The request remains pending and was not falsely approved.</div>
    <?php elseif(($_GET['status_error'] ?? '') === 'invalid'): ?>
        <div class="alert alert-danger">The requested attendance status is invalid. No data was changed.</div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="fa-solid fa-clock"></i> Attendance Report <small>/ Track employee attendance</small></h2>
        <div class="d-flex gap-2 flex-wrap">
            <a href="attendance_control.php" class="btn btn-primary rounded-pill px-4"><i class="fa fa-calendar-check"></i> Control & Lock</a>
            <a href="attendance_policy.php" class="btn btn-outline-primary rounded-pill px-4"><i class="fa fa-sliders"></i> Policy</a>
            <?php $exportQuery = http_build_query(['department'=>$department,'employee_id'=>$selectedEmployee,'month'=>$selectedMonth,'year'=>$selectedYear,'inactive'=>$includeInactive?'1':'0','view_mode'=>$viewMode,'search'=>$search]); ?>
            <a href="export_attendance.php?<?=htmlspecialchars($exportQuery, ENT_QUOTES, 'UTF-8')?>" class="btn btn-success rounded-pill px-4"><i class="fa fa-file-excel"></i> Export Excel</a>
        </div>
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

    <?php if($pendingForMe): ?>
    <div class="filter-card border-warning">
        <div class="filter-title"><i class="fa-solid fa-user-check"></i> Status Requests Awaiting Your Approval</div>
        <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Employee</th><th>Date</th><th>Requested Status</th><th>Requested By</th><th>Decision</th></tr></thead><tbody>
        <?php foreach($pendingForMe as $pending): ?><tr><td><strong><?=htmlspecialchars($pending['full_name'])?></strong><small class="d-block text-muted"><?=htmlspecialchars($pending['department'])?></small></td><td><?=date('d-m-Y',strtotime($pending['attendance_date']))?></td><td><span class="badge bg-warning text-dark"><?=htmlspecialchars($pending['requested_status'])?></span></td><td><?=htmlspecialchars($pending['requested_by_name'])?></td><td><form method="post" class="d-flex gap-2"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars(ems_csrf_token())?>"><input type="hidden" name="review_status_request" value="1"><input type="hidden" name="request_id" value="<?=(int)$pending['id']?>"><input name="review_comment" class="form-control form-control-sm" placeholder="Comment"><button name="decision" value="Approved" class="btn btn-sm btn-success">Approve</button><button name="decision" value="Rejected" class="btn btn-sm btn-outline-danger">Reject</button></form></td></tr><?php endforeach; ?>
        </tbody></table></div>
    </div>
    <?php endif; ?>

    <!-- Filter Card -->
    <div class="filter-card" style="background:linear-gradient(135deg,#edfafa,#f7fbff);border-color:#b8e8e4">
        <div class="filter-title"><i class="fa-solid fa-filter"></i> Attendance Report Filters</div>
        <form method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="filter" value="1">
            <div class="col-xl-2 col-md-4"><label class="form-label">Company Name</label><select class="form-select" disabled><option><?=htmlspecialchars($companyName)?></option></select></div>
            <div class="col-xl-2 col-md-4"><label class="form-label">Department Name</label><select name="department" id="departmentFilter" class="form-select"><option value="">-Select-</option><?php foreach($departmentsList as $dept):?><option value="<?=htmlspecialchars($dept)?>" <?=$department===$dept?'selected':''?>><?=htmlspecialchars($dept)?></option><?php endforeach;?></select></div>
            <div class="col-xl-2 col-md-4"><label class="form-label">Employee Name</label><select name="employee_id" id="employeeFilter" class="form-select"><option value="">-Select Employee-</option><?php foreach($employeeList as $employeeOption):?><option value="<?=(int)$employeeOption['id']?>" data-department="<?=htmlspecialchars($employeeOption['department'])?>" <?=($selectedEmployee===(int)$employeeOption['id'])?'selected':''?>><?=htmlspecialchars($employeeOption['employee_id'].' - '.$employeeOption['full_name'])?></option><?php endforeach;?></select></div>
            <div class="col-xl-2 col-md-4"><label class="form-label">Month</label><select name="month" class="form-select"><?php for($monthNo=1;$monthNo<=12;$monthNo++):?><option value="<?=$monthNo?>" <?=$selectedMonth===$monthNo?'selected':''?>><?=date('F',mktime(0,0,0,$monthNo,1))?></option><?php endfor;?></select></div>
            <div class="col-xl-2 col-md-4"><label class="form-label">Year</label><select name="year" class="form-select"><?php for($yearNo=(int)date('Y')+1;$yearNo>=2020;$yearNo--):?><option value="<?=$yearNo?>" <?=$selectedYear===$yearNo?'selected':''?>><?=$yearNo?></option><?php endfor;?></select></div>
            <div class="col-xl-1 col-md-2"><label class="form-label d-block">InActive</label><div class="form-check form-switch py-2"><input class="form-check-input" type="checkbox" name="inactive" value="1" <?=$includeInactive?'checked':''?>></div></div>
            <div class="col-xl-5 col-md-7"><div class="d-flex gap-4 py-2"><label class="form-check-label fw-semibold"><input type="radio" name="view_mode" value="payroll" <?=$viewMode==='payroll'?'checked':''?>> Payroll Wise</label><label class="form-check-label fw-semibold"><input type="radio" name="view_mode" value="month" <?=$viewMode==='month'?'checked':''?>> Month Wise</label></div></div>
            <div class="col-xl-7 col-md-5 text-end"><button type="submit" class="btn btn-success px-4"><i class="fa fa-search me-1"></i> Search</button> <a href="attendance_report.php" class="btn btn-outline-secondary px-3"><i class="fa fa-rotate-left"></i> Reset</a></div>
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
                        <th>Sr. No.</th>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Date</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Working Hours</th>
                        <th>Required Hours</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if($totalRecords > 0): ?>
                    <?php $serialNo = 0; foreach($reportRows as $row){
                        $badgeClass = 'badge-absent';
                        $statusLabel = $row['status'] ?: 'Absent';
                        $requiredMinutes = 480;
                        if (!empty($row['shift_start_time']) && !empty($row['shift_end_time'])) {
                            $shiftStart = strtotime('2000-01-01 ' . $row['shift_start_time']);
                            $shiftEnd = strtotime('2000-01-01 ' . $row['shift_end_time']);
                            if ($shiftEnd <= $shiftStart) $shiftEnd = strtotime('+1 day', $shiftEnd);
                            $requiredMinutes = max(0, (int)(($shiftEnd - $shiftStart) / 60));
                        }
                        $requiredHours = sprintf('%02d:%02d', intdiv($requiredMinutes, 60), $requiredMinutes % 60);
                        if($row['status'] == 'Present') $badgeClass = 'badge-present';
                        elseif($row['status'] == 'Late') $badgeClass = 'badge-late';
                        elseif($row['status'] == 'Half Day') $badgeClass = 'badge-halfday';
                        elseif($row['status'] == 'Early Out') $badgeClass = 'badge-earlyout';
                        elseif(in_array($row['status'], ['Absent','Off Day','NH'], true) || empty($row['status'])) $badgeClass = 'badge-absent';
                    ?>
                    <tr>
                        <td><?=++$serialNo?></td>
                        <td>
                            <div class="emp-info">
                                <div class="emp-avatar"><?php echo strtoupper(substr($row['full_name'], 0, 1)); ?></div>
                                <div class="emp-details">
                                    <div class="emp-name"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                    <div class="emp-dept">ID: <?php echo htmlspecialchars($row['emp_code']); ?></div>
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
                        <td class="time-cell"><?php echo htmlspecialchars($requiredHours); ?></td>
                        <td>
                            <?php if(!empty($row['is_leave'])): ?>
                            <span class="badge-modern badge-present"><?=htmlspecialchars($row['leave_type'])?> Leave</span>
                            <small class="d-block text-muted mt-1"><?=htmlspecialchars($row['leave_status'])?></small>
                            <?php elseif(empty($row['status_locked'])): ?>
                            <div class="d-flex align-items-center gap-2">
                                <select data-field-name="statuses[<?=(int)$row['id']?>]" form="batchStatusForm" class="form-select form-select-sm attendance-status-select" style="min-width:105px" onchange="this.name=this.dataset.fieldName;this.classList.add('border-warning')">
                                    <?php foreach(['Present','Absent','Late','Half Day','Early Out','Off Day','NH'] as $option): ?>
                                    <option value="<?=$option?>" <?=$row['status']===$option?'selected':''?>><?=$option?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php else: ?>
                            <span class="badge-modern <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($statusLabel); ?></span>
                            <small class="d-block text-muted mt-1"><i class="fa fa-lock"></i> Saved</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php } ?>
                <?php else: ?>
                    <tr><td colspan="9" class="text-center py-5 text-muted">
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
            <div class="d-flex gap-2">
                <form method="post" id="batchStatusForm" onsubmit="return confirm('<?= $admin_role==='Super Admin'?'Save and lock the selected statuses?':'Send the selected status changes to your in-charge for approval?' ?>');">
                    <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(ems_csrf_token())?>">
                    <input type="hidden" name="update_day_status" value="1">
                    <input type="hidden" name="return_filter" value="<?=isset($_GET['filter'])?'1':''?>">
                    <input type="hidden" name="return_search" value="<?=htmlspecialchars($search)?>">
                    <input type="hidden" name="return_from" value="<?=htmlspecialchars($from)?>">
                    <input type="hidden" name="return_to" value="<?=htmlspecialchars($to)?>">
                    <input type="hidden" name="return_department" value="<?=htmlspecialchars($department)?>">
                    <input type="hidden" name="return_employee_id" value="<?=$selectedEmployee?>">
                    <input type="hidden" name="return_month" value="<?=$selectedMonth?>">
                    <input type="hidden" name="return_year" value="<?=$selectedYear?>">
                    <input type="hidden" name="return_inactive" value="<?=$includeInactive?'1':''?>">
                    <input type="hidden" name="return_view_mode" value="<?=htmlspecialchars($viewMode)?>">
                    <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> <?= $admin_role==='Super Admin'?'Save Statuses':'Send for Approval' ?></button>
                </form>
                <a href="dashboard.php" class="btn btn-primary"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
            </div>
        </div>
    </div>

</div>
<script>
(function(){
    const department=document.getElementById('departmentFilter');
    const employee=document.getElementById('employeeFilter');
    if(!department||!employee)return;
    const filterEmployees=()=>{
        const selectedDepartment=department.value;
        Array.from(employee.options).forEach((option,index)=>{
            if(index===0)return;
            option.hidden=selectedDepartment!==''&&option.dataset.department!==selectedDepartment;
        });
        const selected=employee.options[employee.selectedIndex];
        if(selected&&selected.hidden)employee.value='';
    };
    department.addEventListener('change',filterEmployees);
    filterEmployees();
})();
</script>
</body>
</html>
