<?php
session_start();
include('admincheck_role.php');
include('../config/db.php');
include('../config/employee_management.php');

if (!employeeManagementSchemaReady($conn)) {
    http_response_code(409);
    exit('Run the Employee Management database upgrade first.');
}

$search = trim($_GET['search'] ?? '');
$department = trim($_GET['department'] ?? '');
$status = trim($_GET['status'] ?? '');
$conditions = [];
$params = [];
$types = '';
if ($search !== '') { $conditions[] = '(employee_id LIKE ? OR full_name LIKE ? OR email LIKE ? OR cnic LIKE ?)'; $like="%$search%"; array_push($params,$like,$like,$like,$like); $types.='ssss'; }
if ($department !== '') { $conditions[] = 'department=?'; $params[]=$department; $types.='s'; }
if ($status !== '') { $conditions[] = 'status=?'; $params[]=$status; $types.='s'; }
$sql = 'SELECT employee_id,full_name,email,cnic,date_of_birth,gender,marital_status,phone,address,city,department,designation,role,employment_type,joining_date,probation_end_date,confirmation_date,status,emergency_contact_name,emergency_contact_relation,emergency_contact_phone,bank_name,bank_account_title,bank_account_number,iban FROM employees' . ($conditions ? ' WHERE '.implode(' AND ',$conditions) : '') . ' ORDER BY full_name';
$stmt=$conn->prepare($sql); if($params){$stmt->bind_param($types,...$params);} $stmt->execute(); $result=$stmt->get_result();

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="employee-records-'.date('Y-m-d').'.csv"');
echo "\xEF\xBB\xBF";
$out=fopen('php://output','w');
$headers=['Employee ID','Full Name','Email','CNIC','Date of Birth','Gender','Marital Status','Phone','Address','City','Department','Designation','Role','Employment Type','Joining Date','Probation End','Confirmation Date','Status','Emergency Contact','Relationship','Emergency Phone','Bank','Account Title','Account Number','IBAN'];
fputcsv($out,$headers);
while($row=$result->fetch_assoc()){fputcsv($out,array_values($row));}
fclose($out); $stmt->close(); exit;
