<?php
session_start();
if (!isset($_SESSION['admin'])) { header('Location: ../index.php'); exit; }
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/admincheck_role.php';

$message = ''; $messageType = 'success'; $importErrors = [];
if (isset($_GET['template'])) {
    requirePermission($conn, 'reports', 'export');
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="KPI-Performance-Bulk-Template.csv"');
    echo "\xEF\xBB\xBF"; $out = fopen('php://output', 'w');
    fputcsv($out, ['employee_code','period_month','period_year','designation','team','team_leader','login_5','withdraw_5','aht_10','attendance_15','adherence_5','call_quality_30','gc_conversion_10','complaint_10','quiz_10','remarks']);
    fputcsv($out, ['00001',date('n'),date('Y'),'FT','Team Concord','Team Leader Name',4.97,5,10,15,5,26.86,10,10,10,'Good overall performance']);
    fclose($out); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_import'])) {
    requirePermission($conn, 'reports', 'edit');
    ems_verify_csrf();
    if (!isset($_FILES['bulk_file']) || $_FILES['bulk_file']['error'] !== UPLOAD_ERR_OK) {
        $importErrors[] = 'Please select the completed KPI CSV file.';
    } elseif ($_FILES['bulk_file']['size'] > 5 * 1024 * 1024 || strtolower(pathinfo($_FILES['bulk_file']['name'], PATHINFO_EXTENSION)) !== 'csv') {
        $importErrors[] = 'Bulk file must be a CSV file and maximum 5 MB.';
    } else {
        $handle = fopen($_FILES['bulk_file']['tmp_name'], 'r'); $header = fgetcsv($handle);
        if ($header) $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$header[0]);
        $required = ['employee_code','period_month','period_year','designation','team','team_leader','login_5','withdraw_5','aht_10','attendance_15','adherence_5','call_quality_30','gc_conversion_10','complaint_10','quiz_10','remarks'];
        if (!$header || array_diff($required, $header)) $importErrors[] = 'CSV columns do not match the downloaded template.';
        else {
            $map = array_flip($header); $rowNo = 1; $imported = 0;
            $allowedRatings = ['Outstanding','Exceeds Expectations','Meets Expectations','Needs Improvement','Unsatisfactory'];
            $findEmployee = $conn->prepare('SELECT id FROM employees WHERE employee_id=? OR CAST(employee_id AS UNSIGNED)=CAST(? AS UNSIGNED) LIMIT 1');
            $findRecord = $conn->prepare('SELECT id FROM employee_kpi_performance WHERE employee_id=? AND period_month=? AND period_year=? AND kpi_title=? LIMIT 1');
            $insertRecord = $conn->prepare('INSERT INTO employee_kpi_performance(employee_id,period_month,period_year,kpi_title,target_score,achieved_score,performance_rating,remarks,uploaded_by) VALUES(?,?,?,?,?,?,?,?,?)');
            $updateRecord = $conn->prepare('UPDATE employee_kpi_performance SET target_score=?,achieved_score=?,performance_rating=?,remarks=?,uploaded_by=?,created_at=CURRENT_TIMESTAMP WHERE id=?');
            while (($data = fgetcsv($handle)) !== false) {
                $rowNo++; if (count(array_filter($data, static fn($v) => trim((string)$v) !== '')) === 0) continue;
                $value = []; foreach ($required as $column) $value[$column] = trim((string)($data[$map[$column]] ?? ''));
                $code = $value['employee_code']; $month = (int)$value['period_month']; $year = (int)$value['period_year'];
                $title = 'Monthly Performance Scorecard'; $remarks = $value['remarks'];
                $scores = [(float)$value['login_5'],(float)$value['withdraw_5'],(float)$value['aht_10'],(float)$value['attendance_15'],(float)$value['adherence_5'],(float)$value['call_quality_30'],(float)$value['gc_conversion_10'],(float)$value['complaint_10'],(float)$value['quiz_10']];
                $limits = [5,5,10,15,5,30,10,10,10]; $scoreValid = true; foreach($scores as $i=>$score) if($score<0 || $score>$limits[$i]) $scoreValid=false;
                $target = 100; $achieved = round(array_sum($scores),2); $rating = $achieved>=95?'Outstanding':($achieved>=85?'Exceeds Expectations':($achieved>=70?'Meets Expectations':($achieved>=50?'Needs Improvement':'Unsatisfactory')));
                $grade = $achieved>=95?'A+':($achieved>=90?'A':($achieved>=85?'B+':($achieved>=80?'B':($achieved>=70?'C':($achieved>=60?'D':'F')))));
                $findEmployee->bind_param('ss', $code, $code); $findEmployee->execute(); $employeeMatch = $findEmployee->get_result()->fetch_assoc();
                if (!$employeeMatch) { $importErrors[] = "Row {$rowNo}: HRMS Employee Code {$code} not found."; continue; }
                if ($month < 1 || $month > 12 || $year < 2020 || !$scoreValid) { $importErrors[] = "Row {$rowNo}: invalid month/year or a score exceeds its allowed weight."; continue; }
                $empId = (int)$employeeMatch['id']; $uploadedBy = (int)($_SESSION['admin_id'] ?? 0);
                $findRecord->bind_param('iiis', $empId, $month, $year, $title); $findRecord->execute(); $existing = $findRecord->get_result()->fetch_assoc();
                if ($existing) { $recordId=(int)$existing['id']; $updateRecord->bind_param('ddssii',$target,$achieved,$rating,$remarks,$uploadedBy,$recordId); $updateRecord->execute(); }
                else { $insertRecord->bind_param('iiisddssi',$empId,$month,$year,$title,$target,$achieved,$rating,$remarks,$uploadedBy); $insertRecord->execute(); $recordId=(int)$conn->insert_id; }
                $designation=$value['designation']; $team=$value['team']; $teamLeader=$value['team_leader'];
                $metricUpdate=$conn->prepare('UPDATE employee_kpi_performance SET login_score=?,withdraw_score=?,aht_score=?,attendance_score=?,adherence_score=?,call_quality_score=?,gc_conversion_score=?,complaint_score=?,quiz_score=?,total_score=?,grade=?,designation_snapshot=?,team_snapshot=?,team_leader_snapshot=? WHERE id=?');
                $metricUpdate->bind_param('ddddddddddssssi',$scores[0],$scores[1],$scores[2],$scores[3],$scores[4],$scores[5],$scores[6],$scores[7],$scores[8],$achieved,$grade,$designation,$team,$teamLeader,$recordId); $metricUpdate->execute();
                $imported++;
            }
            fclose($handle);
            if ($imported > 0) $message = "{$imported} employee KPI/performance record(s) imported successfully by HRMS Employee Code.";
            if ($importErrors) $messageType = $imported > 0 ? 'warning' : 'danger';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_kpi'])) {
    ems_verify_csrf();
    $employeeId = (int)($_POST['employee_id'] ?? 0);
    $month = min(12, max(1, (int)($_POST['period_month'] ?? date('n'))));
    $year = min((int)date('Y') + 1, max(2020, (int)($_POST['period_year'] ?? date('Y'))));
    $title = trim((string)($_POST['kpi_title'] ?? ''));
    $target = max(0, (float)($_POST['target_score'] ?? 100));
    $achieved = max(0, (float)($_POST['achieved_score'] ?? 0));
    $rating = (string)($_POST['performance_rating'] ?? 'Meets Expectations');
    $remarks = trim((string)($_POST['remarks'] ?? ''));
    $allowedRatings = ['Outstanding','Exceeds Expectations','Meets Expectations','Needs Improvement','Unsatisfactory'];
    $employeeExists = $conn->prepare('SELECT id FROM employees WHERE id=?'); $employeeExists->bind_param('i',$employeeId); $employeeExists->execute();
    if (!$employeeExists->get_result()->fetch_assoc() || $title === '' || !in_array($rating, $allowedRatings, true)) {
        $message = 'Please complete all required KPI fields.'; $messageType = 'danger';
    } else {
        $storedFile = null; $originalFile = null;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['attachment'];
            $extension = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['pdf','xlsx','xls','docx','csv','jpg','jpeg','png'];
            if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024 || !in_array($extension, $allowedExtensions, true)) {
                $message = 'Attachment must be PDF, Excel, Word, CSV or image and maximum 5 MB.'; $messageType = 'danger';
            } else {
                $uploadDir = __DIR__ . '/../uploads/kpi';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $storedFile = bin2hex(random_bytes(16)) . '.' . $extension;
                $originalFile = basename((string)$file['name']);
                if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $storedFile)) {
                    $message = 'Attachment could not be uploaded.'; $messageType = 'danger'; $storedFile = null;
                }
            }
        }
        if ($message === '') {
            $uploadedBy = (int)($_SESSION['admin_id'] ?? 0);
            $stmt = $conn->prepare('INSERT INTO employee_kpi_performance(employee_id,period_month,period_year,kpi_title,target_score,achieved_score,performance_rating,remarks,attachment,original_filename,uploaded_by) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->bind_param('iiisddssssi',$employeeId,$month,$year,$title,$target,$achieved,$rating,$remarks,$storedFile,$originalFile,$uploadedBy);
            $stmt->execute();
            $message = 'Employee KPI and performance record uploaded successfully.';
        }
    }
}

$employees = $conn->query("SELECT id,employee_id,full_name,department FROM employees WHERE status='Active' AND is_active=1 ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);
$records = $conn->query("SELECT k.*,e.employee_id AS employee_code,e.full_name,e.department FROM employee_kpi_performance k JOIN employees e ON e.id=k.employee_id ORDER BY k.period_year DESC,k.period_month DESC,k.id DESC")->fetch_all(MYSQLI_ASSOC);
$employeeCount = count($employees);
$departmentCount = (int)($conn->query("SELECT COUNT(DISTINCT department) c FROM employees WHERE status='Active' AND is_active=1 AND department<>''")->fetch_assoc()['c'] ?? 0);
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>PSM/KPI - EMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<style>body{background:#f4f7fb;font-family:Inter,Arial,sans-serif;color:#172033}.top{background:linear-gradient(135deg,#173a70,#2463b5);color:#fff;padding:25px 0}.card{border:0;border-radius:18px;box-shadow:0 8px 24px rgba(27,49,86,.08)}.metric{font-size:30px;font-weight:800}.icon{width:48px;height:48px;border-radius:14px;display:grid;place-items:center;background:#e8f1ff;color:#2563eb;font-size:20px}.form-label{font-weight:650}.table th{font-size:12px;text-transform:uppercase;color:#58708f;background:#f7f9fc}.rating{font-size:12px;border-radius:99px;padding:6px 10px;background:#e8f1ff;color:#1d4ed8;font-weight:700}main.container-fluid.p-4>.card{display:none!important}</style></head><body>
<header class="top"><div class="container-fluid px-4 d-flex justify-content-between align-items-center"><div><h2 class="mb-1"><i class="fa fa-bullseye me-2"></i>PSM / KPI Management</h2><div class="opacity-75">Upload employee KPI scores and performance reviews</div></div><div><a href="campaign_kpi_setup.php" class="btn btn-warning me-2"><i class="fa fa-layer-group"></i> Campaigns & KPI Setup</a><a href="campaign_performance.php" class="btn btn-success me-2"><i class="fa fa-upload"></i> Campaign Bulk Upload</a><a href="dashboard.php" class="btn btn-light"><i class="fa fa-arrow-left me-1"></i> Back</a></div></div></header>
<main class="container-fluid p-4">
<?php if($message): ?><div class="alert alert-<?=$messageType?>"><?=htmlspecialchars($message)?></div><?php endif; ?>
<?php if($importErrors): ?><div class="alert alert-warning"><strong>Bulk import notes:</strong><ul class="mb-0"><?php foreach($importErrors as $error): ?><li><?=htmlspecialchars($error)?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="row g-3 mb-4"><div class="col-md-4"><div class="card p-3 d-flex flex-row gap-3 align-items-center"><div class="icon"><i class="fa fa-users"></i></div><div><div class="text-muted">Active Employees</div><div class="metric"><?=$employeeCount?></div></div></div></div><div class="col-md-4"><div class="card p-3 d-flex flex-row gap-3 align-items-center"><div class="icon"><i class="fa fa-building"></i></div><div><div class="text-muted">Departments</div><div class="metric"><?=$departmentCount?></div></div></div></div><div class="col-md-4"><div class="card p-3 d-flex flex-row gap-3 align-items-center"><div class="icon"><i class="fa fa-file-arrow-up"></i></div><div><div class="text-muted">KPI Records</div><div class="metric"><?=count($records)?></div></div></div></div></div>
<div class="card p-4 mb-4"><div class="row align-items-center g-3"><div class="col-lg-7"><h4><i class="fa fa-file-excel text-success me-2"></i>Bulk KPI / Performance Upload</h4><p class="text-muted mb-0">Template ko Excel mein fill karein. Employee matching <strong>HRMS Employee Code</strong> se hogi, phir CSV format mein upload karein.</p></div><div class="col-lg-5"><a href="?template=1" class="btn btn-outline-success mb-2"><i class="fa fa-download me-1"></i> Download Template</a><form method="post" enctype="multipart/form-data" class="d-flex gap-2"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars(ems_csrf_token())?>"><input type="hidden" name="bulk_import" value="1"><input type="file" name="bulk_file" accept=".csv,text/csv" class="form-control" required><button class="btn btn-success text-nowrap"><i class="fa fa-upload me-1"></i> Upload All</button></form></div></div></div>
<div class="card p-4 mb-4"><h4 class="mb-4"><i class="fa fa-upload text-primary me-2"></i>Upload Employee KPI / Performance</h4>
<form method="post" enctype="multipart/form-data" class="row g-3"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars(ems_csrf_token())?>"><input type="hidden" name="save_kpi" value="1">
<div class="col-lg-4"><label class="form-label">Employee *</label><select name="employee_id" class="form-select" required><option value="">Select Employee</option><?php foreach($employees as $e): ?><option value="<?=$e['id']?>"><?=htmlspecialchars($e['employee_id'].' - '.$e['full_name'].' ('.$e['department'].')')?></option><?php endforeach; ?></select></div>
<div class="col-lg-2"><label class="form-label">Month *</label><select name="period_month" class="form-select"><?php for($m=1;$m<=12;$m++): ?><option value="<?=$m?>" <?=$m==date('n')?'selected':''?>><?=date('F',mktime(0,0,0,$m,1))?></option><?php endfor; ?></select></div>
<div class="col-lg-2"><label class="form-label">Year *</label><select name="period_year" class="form-select"><?php for($y=date('Y')+1;$y>=2020;$y--): ?><option <?=$y==date('Y')?'selected':''?>><?=$y?></option><?php endfor; ?></select></div>
<div class="col-lg-4"><label class="form-label">KPI Title *</label><input name="kpi_title" class="form-control" placeholder="e.g. Monthly Sales Target" required maxlength="180"></div>
<div class="col-md-3"><label class="form-label">Target Score *</label><input type="number" step="0.01" min="0" name="target_score" value="100" class="form-control" required></div><div class="col-md-3"><label class="form-label">Achieved Score *</label><input type="number" step="0.01" min="0" name="achieved_score" class="form-control" required></div>
<div class="col-md-3"><label class="form-label">Performance Rating *</label><select name="performance_rating" class="form-select" required><?php foreach(['Outstanding','Exceeds Expectations','Meets Expectations','Needs Improvement','Unsatisfactory'] as $r): ?><option><?=$r?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><label class="form-label">Supporting File</label><input type="file" name="attachment" class="form-control" accept=".pdf,.xlsx,.xls,.docx,.csv,.jpg,.jpeg,.png"><small class="text-muted">Maximum 5 MB</small></div>
<div class="col-12"><label class="form-label">Performance Remarks</label><textarea name="remarks" class="form-control" rows="3" placeholder="Manager remarks, achievements and improvement areas"></textarea></div><div class="col-12 text-end"><button class="btn btn-primary px-4"><i class="fa fa-cloud-arrow-up me-1"></i> Upload KPI / Performance</button></div></form></div>
<div class="card overflow-hidden"><div class="p-4 pb-2"><h4><i class="fa fa-chart-line text-primary me-2"></i>Uploaded KPI Records</h4></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Employee</th><th>Period</th><th>KPI</th><th>Score</th><th>Rating</th><th>File</th><th>Uploaded</th></tr></thead><tbody><?php if(!$records): ?><tr><td colspan="7" class="text-center text-muted py-5">No KPI records uploaded yet.</td></tr><?php endif; ?><?php foreach($records as $row): ?><tr><td><strong><?=htmlspecialchars($row['full_name'])?></strong><small class="d-block text-muted"><?=htmlspecialchars($row['employee_code'].' · '.$row['department'])?></small></td><td><?=date('F',mktime(0,0,0,$row['period_month'],1)).' '.$row['period_year']?></td><td><?=htmlspecialchars($row['kpi_title'])?></td><td><strong><?=htmlspecialchars($row['achieved_score'])?></strong> / <?=htmlspecialchars($row['target_score'])?></td><td><span class="rating"><?=htmlspecialchars($row['performance_rating'])?></span></td><td><?php if($row['attachment']): ?><a href="../uploads/kpi/<?=rawurlencode($row['attachment'])?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fa fa-download"></i> <?=htmlspecialchars($row['original_filename'])?></a><?php else: ?>—<?php endif; ?></td><td><?=date('d-m-Y',strtotime($row['created_at']))?></td></tr><?php endforeach; ?></tbody></table></div></div>
</main></body></html>
