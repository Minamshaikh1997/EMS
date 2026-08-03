<?php
require_once __DIR__ . '/../config/back_dashboard_register.php';
session_start();
include('admincheck_role.php');
include('../config/db.php');
include('../config/attendance.php');

$month = preg_match('/^\d{4}-\d{2}$/', $_GET['month'] ?? '') ? $_GET['month'] : date('Y-m');
$department = trim($_GET['department'] ?? '');
$message = $error = '';
$csrf = attendanceCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $month = preg_match('/^\d{4}-\d{2}$/', $_POST['month'] ?? '') ? $_POST['month'] : date('Y-m');
    if (!verifyAttendanceCsrf()) { $error = 'Security token expired. Refresh and try again.'; }
    elseif (isset($_POST['lock_period']) || isset($_POST['unlock_period'])) {
        $locked = isset($_POST['lock_period']) ? 1 : 0;
        $admin = (int)($_SESSION['admin_id'] ?? 0);
        $notes = trim($_POST['lock_notes'] ?? '');
        $stmt = $conn->prepare("INSERT INTO attendance_period_locks(period_month,is_locked,locked_by,notes,unlocked_at) VALUES(?,?,?,?,IF(?=0,NOW(),NULL)) ON DUPLICATE KEY UPDATE is_locked=VALUES(is_locked),locked_by=VALUES(locked_by),notes=VALUES(notes),locked_at=IF(VALUES(is_locked)=1,NOW(),locked_at),unlocked_at=IF(VALUES(is_locked)=0,NOW(),NULL)");
        $stmt->bind_param('siisi', $month, $locked, $admin, $notes, $locked); $stmt->execute(); $stmt->close();
        $stmt = $conn->prepare("UPDATE attendance SET is_locked=? WHERE DATE_FORMAT(attendance_date,'%Y-%m')=?");
        $stmt->bind_param('is', $locked, $month); $stmt->execute(); $stmt->close();
        $message = $locked ? "Attendance for $month locked." : "Attendance for $month unlocked.";
    } elseif (isset($_POST['save_attendance'])) {
        $attendanceId = (int)($_POST['attendance_id'] ?? 0);
        $stmt = $conn->prepare('SELECT a.*,e.shift_start_time,e.shift_end_time FROM attendance a JOIN employees e ON e.id=a.employee_id WHERE a.id=?');
        $stmt->bind_param('i',$attendanceId); $stmt->execute(); $record=$stmt->get_result()->fetch_assoc(); $stmt->close();
        if (!$record) $error='Attendance record not found.';
        elseif (!empty($record['is_locked']) || attendancePeriodLocked($conn,$record['attendance_date'])) $error='Unlock this month before editing attendance.';
        else {
            $checkIn = trim($_POST['check_in'] ?? ''); $checkOut = trim($_POST['check_out'] ?? ''); $remarks = trim($_POST['remarks'] ?? '');
            if (!preg_match('/^\d{2}:\d{2}$/',$checkIn) || !preg_match('/^\d{2}:\d{2}$/',$checkOut)) $error='Valid check-in and check-out times are required.';
            else {
                [$shiftStart,$shiftEnd]=shiftWindow($record['attendance_date'],$record['shift_start_time']?:'09:00:00',$record['shift_end_time']?:'17:00:00');
                $in=new DateTime($record['attendance_date'].' '.$checkIn); $out=new DateTime($record['attendance_date'].' '.$checkOut); if($out<=$in)$out->modify('+1 day');
                $metrics=attendanceMetrics($in,$out,$shiftStart,$shiftEnd,attendancePolicy($conn)); $working=intdiv($metrics['worked'],60).' Hours '.($metrics['worked']%60).' Minutes';
                $inAt=$in->format('Y-m-d H:i:s');$outAt=$out->format('Y-m-d H:i:s');$inTime=$in->format('H:i:s');$outTime=$out->format('H:i:s');
                $stmt=$conn->prepare('UPDATE attendance SET check_in=?,check_out=?,check_in_at=?,check_out_at=?,working_hours=?,work_minutes=?,late_minutes=?,early_out_minutes=?,overtime_minutes=?,status=?,remarks=?,source=? WHERE id=? AND is_locked=0');
                $source='Admin Correction';$stmt->bind_param('sssssiiiisssi',$inTime,$outTime,$inAt,$outAt,$working,$metrics['worked'],$metrics['late'],$metrics['early'],$metrics['overtime'],$metrics['status'],$remarks,$source,$attendanceId);$stmt->execute();$stmt->close();$message='Attendance corrected and recalculated.';
            }
        }
    }
}

$locked = attendancePeriodLocked($conn,$month.'-01');
$where="DATE_FORMAT(a.attendance_date,'%Y-%m')=?";$types='s';$params=[$month];
if($department!==''){$where.=' AND e.department=?';$types.='s';$params[]=$department;}
$stmt=$conn->prepare("SELECT a.*,e.employee_id employee_code,e.full_name,e.department,e.shift_start_time,e.shift_end_time FROM attendance a JOIN employees e ON e.id=a.employee_id WHERE $where ORDER BY a.attendance_date DESC,e.full_name");$stmt->bind_param($types,...$params);$stmt->execute();$records=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);$stmt->close();
$departments=$conn->query("SELECT DISTINCT department FROM employees WHERE department IS NOT NULL AND department<>'' ORDER BY department");
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Attendance Control</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"></head>
<body class="bg-light"><main class="container-fluid py-4 px-lg-5"><div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><div><h2 class="mb-1">Attendance Control</h2><div class="text-muted">Manual correction and monthly closing</div></div><div><a href="attendance_policy.php" class="btn btn-outline-primary">Policy</a> <a href="attendance_report.php" class="btn btn-outline-secondary">Report</a></div></div>
<?php if($message):?><div class="alert alert-success"><?=htmlspecialchars($message)?></div><?php endif;?><?php if($error):?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif;?>
<div class="card mb-3"><div class="card-body"><form method="get" class="row g-2 align-items-end"><div class="col-md-3"><label class="form-label">Month</label><input type="month" name="month" value="<?=htmlspecialchars($month)?>" class="form-control"></div><div class="col-md-3"><label class="form-label">Department</label><select name="department" class="form-select"><option value="">All Departments</option><?php while($d=$departments->fetch_assoc()):?><option <?=($department===$d['department'])?'selected':''?>><?=htmlspecialchars($d['department'])?></option><?php endwhile;?></select></div><div class="col-md-2"><button class="btn btn-primary w-100">Apply</button></div></form></div></div>
<div class="card mb-3 border-<?=$locked?'danger':'success'?>"><div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3"><div><strong><?=$month?> is <?=$locked?'LOCKED':'OPEN'?></strong><div class="small text-muted">Locked records cannot be changed by employees or admins.</div></div><form method="post" class="d-flex gap-2"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="month" value="<?=htmlspecialchars($month)?>"><input name="lock_notes" class="form-control" placeholder="Closing note"><?php if($locked):?><button name="unlock_period" class="btn btn-warning">Unlock Month</button><?php else:?><button name="lock_period" class="btn btn-danger" onclick="return confirm('Lock all attendance for this month?')">Lock Month</button><?php endif;?></form></div></div>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-dark"><tr><th>Date</th><th>Employee</th><th>Shift</th><th>Check In</th><th>Check Out</th><th>Worked</th><th>Late</th><th>Early</th><th>OT</th><th>Status</th><th>Correction</th></tr></thead><tbody><?php foreach($records as $r):?><tr><td><?=htmlspecialchars($r['attendance_date'])?></td><td><strong><?=htmlspecialchars($r['full_name'])?></strong><div class="small text-muted"><?=htmlspecialchars($r['employee_code'].' · '.$r['department'])?></div></td><td><?=htmlspecialchars(substr($r['shift_start_time'],0,5).'–'.substr($r['shift_end_time'],0,5))?></td><td><?=htmlspecialchars(substr($r['check_in']??'',0,5))?></td><td><?=htmlspecialchars(substr($r['check_out']??'',0,5))?></td><td><?=(int)$r['work_minutes']?>m</td><td><?=(int)$r['late_minutes']?>m</td><td><?=(int)$r['early_out_minutes']?>m</td><td><?=(int)$r['overtime_minutes']?>m</td><td><span class="badge bg-secondary"><?=htmlspecialchars($r['status'])?></span></td><td><form method="post" class="d-flex gap-1"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="month" value="<?=htmlspecialchars($month)?>"><input type="hidden" name="attendance_id" value="<?=(int)$r['id']?>"><input type="time" name="check_in" value="<?=htmlspecialchars(substr($r['check_in']??'',0,5))?>" class="form-control form-control-sm" required><input type="time" name="check_out" value="<?=htmlspecialchars(substr($r['check_out']??'',0,5))?>" class="form-control form-control-sm" required><input name="remarks" value="<?=htmlspecialchars($r['remarks']??'')?>" class="form-control form-control-sm" placeholder="Reason" required><button name="save_attendance" class="btn btn-sm btn-success" <?=$locked?'disabled':''?>><i class="fa fa-save"></i></button></form></td></tr><?php endforeach;?><?php if(!$records):?><tr><td colspan="11" class="text-center text-muted py-4">No attendance records for this filter.</td></tr><?php endif;?></tbody></table></div></div></main></body></html>
