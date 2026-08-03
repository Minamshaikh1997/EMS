<?php
session_start();
include('admincheck_role.php');
include('../config/db.php');
include('../config/employee_management.php');

if (isset($_GET['template'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="employee-import-template.csv"');
    echo "\xEF\xBB\xBF";
    $out=fopen('php://output','w');
    fputcsv($out,['employee_id','full_name','email','password','department','designation','role','joining_date','status','phone','cnic','employment_type']);
    fputcsv($out,['00001','Ali Khan','ali@example.com','ChangeMe123!','IT','Software Engineer','Employee',date('Y-m-d'),'Active','03001234567','35202-1234567-1','Permanent']);
    fclose($out); exit;
}

$errors=[]; $imported=0;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!employeeManagementSchemaReady($conn)) $errors[]='Run the Employee Management database upgrade first.';
    elseif (!verifyEmployeeManagementCsrf()) $errors[]='Security token expired. Refresh and try again.';
    elseif (!isset($_FILES['import_file']) || $_FILES['import_file']['error']!==UPLOAD_ERR_OK) $errors[]='Select a CSV file.';
    elseif ($_FILES['import_file']['size']>2*1024*1024) $errors[]='CSV file must be 2 MB or smaller.';
    else {
        $handle=fopen($_FILES['import_file']['tmp_name'],'r');
        $header=fgetcsv($handle);
        if ($header) $header[0]=preg_replace('/^\xEF\xBB\xBF/','',$header[0]);
        $required=['employee_id','full_name','email','password','department','designation','role','joining_date','status','phone','cnic','employment_type'];
        if (!$header || array_diff($required,$header)) $errors[]='CSV columns do not match the provided template.';
        else {
            $map=array_flip($header); $rowNumber=1;
            $allowedStatus=['Active','Inactive','Suspended','Terminated'];
            $allowedEmployment=['Permanent','Contract','Probation','Intern','Part-time','Consultant'];
            $conn->begin_transaction();
            try {
                $stmt=$conn->prepare("INSERT INTO employees (employee_id,full_name,email,password,department,designation,role,joining_date,status,is_active,phone,cnic,employment_type) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
                while(($data=fgetcsv($handle))!==false){
                    $rowNumber++; if(count(array_filter($data,fn($v)=>trim((string)$v)!==''))===0) continue;
                    $v=[]; foreach($required as $key)$v[$key]=trim($data[$map[$key]]??'');
                    if($v['employee_id']===''||$v['full_name']===''||!filter_var($v['email'],FILTER_VALIDATE_EMAIL)||strlen($v['password'])<8){$errors[]="Row $rowNumber: employee ID, name, valid email and 8+ character password are required."; continue;}
                    $cnic=normalizeCnic($v['cnic']); if($cnic===false){$errors[]="Row $rowNumber: invalid CNIC."; continue;}
                    $status=in_array($v['status'],$allowedStatus,true)?$v['status']:'Active';
                    $employment=in_array($v['employment_type'],$allowedEmployment,true)?$v['employment_type']:'Permanent';
                    $joining=normalizeOptionalDate($v['joining_date']); $active=$status==='Active'?1:0; $hash=password_hash($v['password'],PASSWORD_DEFAULT);
                    try{$stmt->bind_param('sssssssssisss',$v['employee_id'],$v['full_name'],$v['email'],$hash,$v['department'],$v['designation'],$v['role'],$joining,$status,$active,$v['phone'],$cnic,$employment);$stmt->execute();$newId=$conn->insert_id;logEmployeeHistory($conn,$newId,'Created',null,$v['employee_id'],'Employee created through CSV import.');$imported++;}
                    catch(Throwable $e){$errors[]="Row $rowNumber: duplicate employee ID, email or CNIC.";}
                }
                $stmt->close(); $conn->commit();
            } catch(Throwable $e){$conn->rollback();$errors[]='Import stopped because of a database error.';$imported=0;}
        }
        if(is_resource($handle))fclose($handle);
    }
}
$csrf=employeeManagementCsrfToken();
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Import Employees</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><main class="container py-5"><div class="card shadow-sm border-0"><div class="card-body p-4"><div class="d-flex justify-content-between"><div><h2>Import Employees</h2><p class="text-muted">Upload an Excel-compatible CSV file. Existing employees are never overwritten.</p></div><a href="employee.php" class="btn btn-outline-secondary align-self-start">Back</a></div>
<?php if($imported):?><div class="alert alert-success"><?=$imported?> employee(s) imported successfully.</div><?php endif;?><?php if($errors):?><div class="alert alert-warning"><strong>Import notes</strong><ul class="mb-0"><?php foreach($errors as $e):?><li><?=htmlspecialchars($e)?></li><?php endforeach;?></ul></div><?php endif;?>
<div class="alert alert-info">First download the template, complete it in Excel, then save/upload it as CSV UTF-8.</div><a href="?template=1" class="btn btn-outline-primary mb-3">Download CSV Template</a>
<form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><label class="form-label fw-bold">Employee CSV</label><input type="file" name="import_file" accept=".csv,text/csv" class="form-control mb-3" required><button class="btn btn-success">Validate & Import</button></form>
</div></div></main></body></html>
