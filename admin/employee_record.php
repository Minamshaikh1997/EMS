<?php
session_start();
include('admincheck_role.php');
include('../config/db.php');
include('../config/employee_management.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_POST['employee_id'] ?? 0);
if ($id < 1) { header('Location: employee.php'); exit(); }

$schemaReady = employeeManagementSchemaReady($conn);
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$schemaReady) {
        $error = 'Run the Employee Management database upgrade before saving data.';
    } elseif (!verifyEmployeeManagementCsrf()) {
        $error = 'Security token expired. Refresh the page and try again.';
    } elseif (isset($_POST['save_profile'])) {
        $cnic = normalizeCnic($_POST['cnic'] ?? '');
        if ($cnic === false) {
            $error = 'CNIC must contain exactly 13 digits.';
        } else {
            $fields = [
                'date_of_birth' => normalizeOptionalDate($_POST['date_of_birth'] ?? ''),
                'probation_end_date' => normalizeOptionalDate($_POST['probation_end_date'] ?? ''),
                'confirmation_date' => normalizeOptionalDate($_POST['confirmation_date'] ?? ''),
                'separation_date' => normalizeOptionalDate($_POST['separation_date'] ?? ''),
            ];
            $allowedGender = ['Male','Female','Other','Prefer not to say'];
            $allowedMarital = ['Single','Married','Divorced','Widowed'];
            $allowedEmployment = ['Permanent','Contract','Probation','Intern','Part-time','Consultant'];
            $gender = in_array($_POST['gender'] ?? '', $allowedGender, true) ? $_POST['gender'] : null;
            $marital = in_array($_POST['marital_status'] ?? '', $allowedMarital, true) ? $_POST['marital_status'] : null;
            $employment = in_array($_POST['employment_type'] ?? '', $allowedEmployment, true) ? $_POST['employment_type'] : 'Permanent';
            $values = [];
            foreach (['phone','address','city','emergency_contact_name','emergency_contact_relation','emergency_contact_phone','bank_name','bank_account_title','bank_account_number','iban','separation_reason'] as $key) {
                $values[$key] = trim($_POST[$key] ?? '');
            }
            $stmt = $conn->prepare("UPDATE employees SET cnic=?, date_of_birth=?, gender=?, marital_status=?, phone=?, address=?, city=?, emergency_contact_name=?, emergency_contact_relation=?, emergency_contact_phone=?, bank_name=?, bank_account_title=?, bank_account_number=?, iban=?, employment_type=?, probation_end_date=?, confirmation_date=?, separation_date=?, separation_reason=? WHERE id=?");
            $stmt->bind_param('sssssssssssssssssssi', $cnic, $fields['date_of_birth'], $gender, $marital, $values['phone'], $values['address'], $values['city'], $values['emergency_contact_name'], $values['emergency_contact_relation'], $values['emergency_contact_phone'], $values['bank_name'], $values['bank_account_title'], $values['bank_account_number'], $values['iban'], $employment, $fields['probation_end_date'], $fields['confirmation_date'], $fields['separation_date'], $values['separation_reason'], $id);
            try {
                $stmt->execute();
                logEmployeeHistory($conn, $id, 'Profile Updated', null, null, 'Personal, emergency, bank or employment details updated.');
                $message = 'Employee record updated successfully.';
            } catch (Throwable $e) {
                $error = str_contains($e->getMessage(), 'unique_employee_cnic') ? 'This CNIC is already assigned to another employee.' : 'Employee record could not be updated.';
            }
            $stmt->close();
        }
    } elseif (isset($_POST['upload_document'])) {
        $types = ['CNIC Front','CNIC Back','CV','Degree','Certificate','Contract','Offer Letter','Other'];
        $type = in_array($_POST['document_type'] ?? '', $types, true) ? $_POST['document_type'] : 'Other';
        $title = trim($_POST['document_title'] ?? '');
        if ($title === '') {
            $error = 'Document title is required.';
        } else {
            try {
                $saved = uploadEmployeeDocument($_FILES['document_file'] ?? [], $id);
                $expiry = normalizeOptionalDate($_POST['expiry_date'] ?? '');
                $notes = trim($_POST['notes'] ?? '');
                $original = basename($_FILES['document_file']['name']);
                $adminId = (int)($_SESSION['admin_id'] ?? 0);
                $stmt = $conn->prepare("INSERT INTO employee_documents (employee_id, document_type, document_title, file_name, original_name, mime_type, file_size, expiry_date, notes, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('isssssissi', $id, $type, $title, $saved['file_name'], $original, $saved['mime_type'], $saved['file_size'], $expiry, $notes, $adminId);
                $stmt->execute(); $stmt->close();
                logEmployeeHistory($conn, $id, 'Document Added', null, $title, $type);
                $message = 'Document uploaded successfully.';
            } catch (Throwable $e) { $error = $e->getMessage(); }
        }
    } elseif (isset($_POST['delete_document'])) {
        $documentId = (int)$_POST['document_id'];
        $stmt = $conn->prepare('SELECT file_name, document_title FROM employee_documents WHERE id=? AND employee_id=?');
        $stmt->bind_param('ii', $documentId, $id); $stmt->execute(); $document = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if ($document) {
            $stmt = $conn->prepare('DELETE FROM employee_documents WHERE id=? AND employee_id=?');
            $stmt->bind_param('ii', $documentId, $id); $stmt->execute(); $stmt->close();
            deleteStoredEmployeeDocument($document['file_name']);
            logEmployeeHistory($conn, $id, 'Document Removed', $document['document_title'], null, null);
            $message = 'Document removed.';
        }
    }
}

$stmt = $conn->prepare('SELECT e.*, m.full_name manager_name, s.full_name supervisor_name, t.full_name team_lead_name FROM employees e LEFT JOIN employees m ON e.reporting_manager_id=m.id LEFT JOIN employees s ON e.reporting_supervisor_id=s.id LEFT JOIN employees t ON e.reporting_team_lead_id=t.id WHERE e.id=?');
$stmt->bind_param('i', $id); $stmt->execute(); $employee = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$employee) { header('Location: employee.php'); exit(); }
$documents = $history = [];
if ($schemaReady) {
    $stmt = $conn->prepare('SELECT * FROM employee_documents WHERE employee_id=? ORDER BY created_at DESC'); $stmt->bind_param('i',$id); $stmt->execute(); $documents=$stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
    $stmt = $conn->prepare('SELECT h.*, a.name changed_by_name FROM employee_history h LEFT JOIN admin a ON h.changed_by=a.id WHERE h.employee_id=? ORDER BY h.created_at DESC LIMIT 50'); $stmt->bind_param('i',$id); $stmt->execute(); $history=$stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
}
function ev($employee, $key) { return htmlspecialchars($employee[$key] ?? '', ENT_QUOTES, 'UTF-8'); }
function selected($actual, $expected) { return $actual === $expected ? 'selected' : ''; }
$csrf = employeeManagementCsrfToken();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=ev($employee,'full_name')?> - Employee Record</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"><link href="admin_panel.css" rel="stylesheet">
<style>.profile-head{background:linear-gradient(135deg,#17365d,#2f75b5);color:#fff;border-radius:16px;padding:24px}.profile-photo{width:84px;height:84px;border-radius:18px;object-fit:cover;background:#fff}.section-card{border:1px solid #e2e8f0;border-radius:14px;margin-bottom:18px}.section-card .card-header{font-weight:700;background:#f8fafc}.form-label{font-size:12px;font-weight:700;color:#475569}.meta{font-size:12px;color:#64748b}.table td,.table th{vertical-align:middle}</style></head>
<body class="bg-light"><main class="container-fluid py-4 px-lg-5">
<div class="d-flex justify-content-between align-items-center mb-3"><a href="employee.php" class="btn btn-outline-secondary"><i class="fa fa-arrow-left"></i> Employees</a><a href="edit_employee.php?id=<?=$id?>" class="btn btn-primary"><i class="fa fa-pen"></i> Core Job Details</a></div>
<section class="profile-head mb-4 d-flex gap-3 align-items-center"><img class="profile-photo" src="<?=!empty($employee['photo'])?'../uploads/'.rawurlencode($employee['photo']):'https://ui-avatars.com/api/?name='.rawurlencode($employee['full_name'])?>" alt="Employee photo"><div><div class="small opacity-75"><?=ev($employee,'employee_id')?></div><h2 class="mb-1"><?=ev($employee,'full_name')?></h2><div><?=ev($employee,'designation')?> · <?=ev($employee,'department')?> · <?=ev($employee,'status')?></div></div></section>
<?php if(!$schemaReady): ?><div class="alert alert-warning">Employee Management upgrade is required. <a href="../database/run_employee_management_upgrade.php" class="alert-link">Run upgrade now</a>.</div><?php endif; ?>
<?php if($message): ?><div class="alert alert-success"><?=htmlspecialchars($message)?></div><?php endif; ?><?php if($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
<ul class="nav nav-tabs mb-3" role="tablist"><li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#details">Employee Details</button></li><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#documents">Documents <span class="badge bg-secondary"><?=count($documents)?></span></button></li><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#history">History</button></li></ul>
<div class="tab-content"><div class="tab-pane fade show active" id="details">
<form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="employee_id" value="<?=$id?>">
<div class="card section-card"><div class="card-header">Personal Information</div><div class="card-body"><div class="row g-3">
<div class="col-md-3"><label class="form-label">CNIC</label><input name="cnic" class="form-control" placeholder="00000-0000000-0" value="<?=ev($employee,'cnic')?>"></div>
<div class="col-md-3"><label class="form-label">Date of Birth</label><input type="date" name="date_of_birth" class="form-control" value="<?=ev($employee,'date_of_birth')?>"></div>
<div class="col-md-3"><label class="form-label">Gender</label><select name="gender" class="form-select"><option value="">Select</option><?php foreach(['Male','Female','Other','Prefer not to say'] as $v): ?><option <?=selected($employee['gender']??'',$v)?>><?=$v?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><label class="form-label">Marital Status</label><select name="marital_status" class="form-select"><option value="">Select</option><?php foreach(['Single','Married','Divorced','Widowed'] as $v): ?><option <?=selected($employee['marital_status']??'',$v)?>><?=$v?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><label class="form-label">Phone</label><input name="phone" class="form-control" value="<?=ev($employee,'phone')?>"></div><div class="col-md-3"><label class="form-label">City</label><input name="city" class="form-control" value="<?=ev($employee,'city')?>"></div><div class="col-md-6"><label class="form-label">Address</label><input name="address" class="form-control" value="<?=ev($employee,'address')?>"></div>
</div></div></div>
<div class="card section-card"><div class="card-header">Emergency Contact</div><div class="card-body"><div class="row g-3"><div class="col-md-4"><label class="form-label">Name</label><input name="emergency_contact_name" class="form-control" value="<?=ev($employee,'emergency_contact_name')?>"></div><div class="col-md-4"><label class="form-label">Relationship</label><input name="emergency_contact_relation" class="form-control" value="<?=ev($employee,'emergency_contact_relation')?>"></div><div class="col-md-4"><label class="form-label">Phone</label><input name="emergency_contact_phone" class="form-control" value="<?=ev($employee,'emergency_contact_phone')?>"></div></div></div></div>
<div class="card section-card"><div class="card-header">Bank & Employment Details</div><div class="card-body"><div class="row g-3"><div class="col-md-3"><label class="form-label">Bank</label><input name="bank_name" class="form-control" value="<?=ev($employee,'bank_name')?>"></div><div class="col-md-3"><label class="form-label">Account Title</label><input name="bank_account_title" class="form-control" value="<?=ev($employee,'bank_account_title')?>"></div><div class="col-md-3"><label class="form-label">Account Number</label><input name="bank_account_number" class="form-control" value="<?=ev($employee,'bank_account_number')?>"></div><div class="col-md-3"><label class="form-label">IBAN</label><input name="iban" class="form-control text-uppercase" maxlength="34" value="<?=ev($employee,'iban')?>"></div>
<div class="col-md-3"><label class="form-label">Employment Type</label><select name="employment_type" class="form-select"><?php foreach(['Permanent','Contract','Probation','Intern','Part-time','Consultant'] as $v): ?><option <?=selected($employee['employment_type']??'Permanent',$v)?>><?=$v?></option><?php endforeach; ?></select></div><div class="col-md-3"><label class="form-label">Probation End</label><input type="date" name="probation_end_date" class="form-control" value="<?=ev($employee,'probation_end_date')?>"></div><div class="col-md-3"><label class="form-label">Confirmation Date</label><input type="date" name="confirmation_date" class="form-control" value="<?=ev($employee,'confirmation_date')?>"></div><div class="col-md-3"><label class="form-label">Separation Date</label><input type="date" name="separation_date" class="form-control" value="<?=ev($employee,'separation_date')?>"></div><div class="col-12"><label class="form-label">Separation Reason</label><textarea name="separation_reason" class="form-control" rows="2"><?=ev($employee,'separation_reason')?></textarea></div></div></div></div>
<button name="save_profile" class="btn btn-success" <?=$schemaReady?'':'disabled'?>>Save Employee Record</button></form></div>
<div class="tab-pane fade" id="documents"><div class="card section-card"><div class="card-header">Upload Document</div><div class="card-body"><form method="post" enctype="multipart/form-data" class="row g-3"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="employee_id" value="<?=$id?>"><div class="col-md-3"><label class="form-label">Type</label><select name="document_type" class="form-select"><?php foreach(['CNIC Front','CNIC Back','CV','Degree','Certificate','Contract','Offer Letter','Other'] as $v): ?><option><?=$v?></option><?php endforeach; ?></select></div><div class="col-md-3"><label class="form-label">Title</label><input name="document_title" class="form-control" required></div><div class="col-md-3"><label class="form-label">Expiry Date</label><input type="date" name="expiry_date" class="form-control"></div><div class="col-md-3"><label class="form-label">File (PDF/JPG/PNG, max 5 MB)</label><input type="file" name="document_file" accept=".pdf,.jpg,.jpeg,.png" class="form-control" required></div><div class="col-md-9"><label class="form-label">Notes</label><input name="notes" class="form-control"></div><div class="col-md-3 d-flex align-items-end"><button name="upload_document" class="btn btn-primary w-100" <?=$schemaReady?'':'disabled'?>>Upload</button></div></form></div></div>
<div class="card section-card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Type / Title</th><th>File</th><th>Expiry</th><th>Uploaded</th><th></th></tr></thead><tbody><?php foreach($documents as $d): ?><tr><td><strong><?=htmlspecialchars($d['document_type'])?></strong><div class="meta"><?=htmlspecialchars($d['document_title'])?></div></td><td><a href="download_employee_document.php?id=<?=(int)$d['id']?>"><?=htmlspecialchars($d['original_name'])?></a><div class="meta"><?=number_format($d['file_size']/1024,1)?> KB</div></td><td><?=htmlspecialchars($d['expiry_date']?:'—')?></td><td><?=htmlspecialchars($d['created_at'])?></td><td><form method="post" onsubmit="return confirm('Remove this document?')"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="employee_id" value="<?=$id?>"><input type="hidden" name="document_id" value="<?=(int)$d['id']?>"><button name="delete_document" class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr><?php endforeach; ?><?php if(!$documents): ?><tr><td colspan="5" class="text-center text-muted py-4">No documents uploaded.</td></tr><?php endif; ?></tbody></table></div></div></div>
<div class="tab-pane fade" id="history"><div class="card section-card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Date</th><th>Event</th><th>Details</th><th>Changed By</th></tr></thead><tbody><?php foreach($history as $h): ?><tr><td><?=htmlspecialchars($h['created_at'])?></td><td><span class="badge bg-secondary"><?=htmlspecialchars($h['event_type'])?></span></td><td><?=htmlspecialchars($h['notes']?:($h['new_value']?:'—'))?></td><td><?=htmlspecialchars($h['changed_by_name']?:'System')?></td></tr><?php endforeach; ?><?php if(!$history): ?><tr><td colspan="4" class="text-center text-muted py-4">No history recorded yet.</td></tr><?php endif; ?></tbody></table></div></div></div></div>
</main><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script></body></html>
