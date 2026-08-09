<?php
require_once __DIR__ . '/../config/db.php';

$isEmployeeView = isset($_SESSION['employee_id']) && !isset($_SESSION['admin']);
$isAdminView = isset($_SESSION['admin']);
$adminRole = ems_canonical_admin_role((string)($_SESSION['admin_role'] ?? ''));
if ((!$isEmployeeView && !$isAdminView) || ($isAdminView && !in_array($adminRole, ['Super Admin','Admin','Finance Manager','Accountant'], true))) {
    http_response_code(403); exit('Access denied.');
}

$backUrl = $isEmployeeView ? '../employee/my_payroll.php' : 'salary_slips.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) { header('Location: ' . $backUrl); exit; }

$sql = 'SELECT ss.*,e.employee_id AS emp_code,e.full_name,e.department,e.designation,e.cnic,e.joining_date FROM salary_slips ss INNER JOIN employees e ON e.id=ss.employee_id WHERE ss.id=?';
if ($isEmployeeView) $sql .= ' AND ss.employee_id=?';
$sql .= ' LIMIT 1';
$stmt = $conn->prepare($sql);
if ($isEmployeeView) { $employeeId=(int)$_SESSION['employee_id']; $stmt->bind_param('ii',$id,$employeeId); }
else $stmt->bind_param('i',$id);
$stmt->execute(); $row=$stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$row) { http_response_code(404); exit('Salary slip not found.'); }

$company=['company_name'=>'Employee Management System','email'=>'','phone'=>'','website'=>'','address'=>'','city'=>'','country'=>'','logo_path'=>''];
try { $result=$conn->query('SELECT * FROM company_settings WHERE id=1 LIMIT 1'); if($result&&$result->num_rows)$company=array_merge($company,$result->fetch_assoc()); } catch(Throwable $e) { error_log($e->getMessage()); }

$allowanceRows=[]; $deductionRows=[];
$structureStmt=$conn->prepare('SELECT id FROM salary_structure WHERE employee_id=? LIMIT 1');
$structureStmt->bind_param('i',$row['employee_id']); $structureStmt->execute(); $structure=$structureStmt->get_result()->fetch_assoc(); $structureStmt->close();
if($structure){
    $componentStmt=$conn->prepare("SELECT sc.component_name,sc.component_type,ssc.amount FROM salary_structure_components ssc JOIN salary_components sc ON sc.id=ssc.component_id WHERE ssc.salary_structure_id=? AND ssc.amount>0 ORDER BY sc.component_type,sc.component_name");
    $componentStmt->bind_param('i',$structure['id']); $componentStmt->execute(); $components=$componentStmt->get_result();
    while($component=$components->fetch_assoc()){ if($component['component_type']==='Allowance')$allowanceRows[]=$component; else $deductionRows[]=$component; }
    $componentStmt->close();
}
if(!$allowanceRows && (float)$row['allowance']>0)$allowanceRows[]=['component_name'=>'Total Allowances','amount'=>$row['allowance']];
if(!$deductionRows && (float)$row['deduction']>0)$deductionRows[]=['component_name'=>'Total Deductions','amount'=>$row['deduction']];
$earningRows=array_merge([['component_name'=>'Basic Salary','amount'=>$row['basic_salary']]],$allowanceRows);
$maxRows=max(count($earningRows),count($deductionRows),1);
$month=date('F Y',strtotime($row['salary_month'].'-01'));
$companyAddress=implode(', ',array_filter([trim((string)$company['address']),trim((string)$company['city']),trim((string)$company['country'])]));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Salary Slip - <?=htmlspecialchars($row['emp_code'].' - '.$month)?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<style>
:root{--ink:#172033;--muted:#64748b;--line:#dce3ed;--brand:#2356d8;--brand2:#6d3bce;--soft:#f5f8ff;--green:#0d9f6e}
*{box-sizing:border-box}body{margin:0;background:#e9eef6;color:var(--ink);font-family:Inter,Arial,sans-serif}.toolbar{max-width:900px;margin:20px auto 12px;display:flex;justify-content:flex-end;gap:10px}.toolbar .btn{border-radius:9px;font-weight:650}.payslip{width:900px;min-height:1160px;margin:0 auto 35px;background:#fff;box-shadow:0 15px 50px rgba(15,23,42,.14);position:relative;overflow:hidden}.top-accent{height:9px;background:linear-gradient(90deg,var(--brand),var(--brand2))}.sheet{padding:42px 48px}.company-header{display:flex;align-items:center;justify-content:space-between;padding-bottom:28px;border-bottom:2px solid var(--ink)}.company-block{display:flex;align-items:center;gap:18px;min-width:0}.company-logo{width:82px;height:82px;border:1px solid var(--line);border-radius:15px;object-fit:contain;padding:6px;background:#fff}.logo-fallback{display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--brand),var(--brand2));color:#fff;font-size:32px}.company-copy h1{font-size:25px;font-weight:800;margin:0 0 7px}.company-copy p{font-size:12px;color:var(--muted);margin:2px 0}.slip-heading{text-align:right}.slip-heading .eyebrow{font-size:12px;letter-spacing:1.6px;font-weight:800;color:var(--brand)}.slip-heading h2{font-size:27px;font-weight:850;margin:5px 0}.slip-heading .number{font-size:12px;color:var(--muted)}.employee-panel{margin:28px 0;background:var(--soft);border:1px solid #dbe6ff;border-radius:14px;padding:20px 22px}.panel-title{font-size:11px;letter-spacing:1.4px;text-transform:uppercase;color:var(--brand);font-weight:800;margin-bottom:14px}.employee-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px 24px}.data-label{font-size:10px;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);font-weight:700;margin-bottom:4px}.data-value{font-size:13px;font-weight:700;word-break:break-word}.salary-wrap{border:1px solid var(--line);border-radius:14px;overflow:hidden}.salary-table{width:100%;border-collapse:collapse}.salary-table th{background:#172033;color:#fff;padding:13px 16px;font-size:12px;letter-spacing:.4px}.salary-table td{padding:13px 16px;border-bottom:1px solid var(--line);font-size:13px}.salary-table th:nth-child(2),.salary-table th:nth-child(4),.salary-table td:nth-child(2),.salary-table td:nth-child(4){text-align:right}.salary-table th:nth-child(3),.salary-table td:nth-child(3){border-left:1px solid var(--line)}.amount{font-variant-numeric:tabular-nums}.summary{display:grid;grid-template-columns:1fr 1fr 1fr;margin-top:22px;border:1px solid var(--line);border-radius:14px;overflow:hidden}.summary-item{padding:18px 20px;border-right:1px solid var(--line)}.summary-item:last-child{border:0;background:linear-gradient(135deg,#087f5b,var(--green));color:#fff}.summary-label{font-size:10px;text-transform:uppercase;letter-spacing:.9px;font-weight:800;opacity:.75}.summary-value{font-size:19px;font-weight:850;margin-top:5px}.words{margin-top:16px;padding:13px 17px;border-left:4px solid var(--brand);background:#f8fafc;font-size:12px;color:var(--muted)}.signature-row{display:flex;justify-content:space-between;margin-top:100px}.signature{width:220px;text-align:center;border-top:1px solid #64748b;padding-top:9px;font-size:11px;color:var(--muted);font-weight:700}.footer{position:absolute;bottom:30px;left:48px;right:48px;border-top:1px solid var(--line);padding-top:12px;display:flex;justify-content:space-between;color:#94a3b8;font-size:10px}.confidential{font-weight:800;color:var(--brand)}
@media(max-width:930px){.payslip{width:calc(100% - 24px);min-height:auto}.sheet{padding:25px}.company-header{align-items:flex-start;gap:20px}.employee-grid{grid-template-columns:1fr 1fr}.footer{position:static;margin:70px 25px 25px}.summary{grid-template-columns:1fr}.summary-item{border-right:0;border-bottom:1px solid var(--line)}}
@page{size:A4;margin:0}@media print{body{background:#fff}.toolbar{display:none!important}.payslip{width:210mm;min-height:297mm;margin:0;box-shadow:none}.sheet{padding:12mm}.footer{left:12mm;right:12mm;bottom:9mm}.company-logo{print-color-adjust:exact;-webkit-print-color-adjust:exact}.top-accent,.salary-table th,.summary-item:last-child,.employee-panel{print-color-adjust:exact;-webkit-print-color-adjust:exact}}
</style>
</head>
<body>
<div class="toolbar"><a href="<?=htmlspecialchars($backUrl)?>" class="btn btn-light border"><i class="fa fa-arrow-left me-1"></i> Back</a><a href="export_salary_slip.php?id=<?=(int)$row['id']?>" class="btn btn-success"><i class="fa fa-file-arrow-down me-1"></i> Export PDF</a><button onclick="window.print()" class="btn btn-primary"><i class="fa fa-print me-1"></i> Print</button></div>
<article class="payslip"><div class="top-accent"></div><div class="sheet">
 <header class="company-header"><div class="company-block">
  <?php if(!empty($company['logo_path'])): ?><img class="company-logo" src="../<?=htmlspecialchars($company['logo_path'])?>" alt="Company logo"><?php else: ?><div class="company-logo logo-fallback"><i class="fa fa-building"></i></div><?php endif; ?>
  <div class="company-copy"><h1><?=htmlspecialchars($company['company_name'])?></h1><?php if($companyAddress):?><p><i class="fa fa-location-dot me-1"></i><?=htmlspecialchars($companyAddress)?></p><?php endif;?><?php if($company['email']||$company['phone']):?><p><?=htmlspecialchars(implode('  |  ',array_filter([$company['email'],$company['phone']])))?></p><?php endif;?></div></div>
  <div class="slip-heading"><div class="eyebrow">PAYROLL DOCUMENT</div><h2>Salary Slip</h2><div class="number"><?=htmlspecialchars($month)?> &nbsp; • &nbsp; Slip #<?=str_pad((string)$row['id'],5,'0',STR_PAD_LEFT)?></div></div>
 </header>
 <section class="employee-panel"><div class="panel-title">Employee Information</div><div class="employee-grid">
  <div><div class="data-label">Employee ID</div><div class="data-value"><?=htmlspecialchars($row['emp_code'])?></div></div><div><div class="data-label">Employee Name</div><div class="data-value"><?=htmlspecialchars($row['full_name'])?></div></div><div><div class="data-label">Pay Period</div><div class="data-value"><?=htmlspecialchars($month)?></div></div>
  <div><div class="data-label">Department</div><div class="data-value"><?=htmlspecialchars($row['department']?:'—')?></div></div><div><div class="data-label">Designation</div><div class="data-value"><?=htmlspecialchars($row['designation']?:'—')?></div></div><div><div class="data-label">Issue Date</div><div class="data-value"><?=date('d M Y',strtotime($row['created_at']))?></div></div>
 </div></section>
 <section class="salary-wrap"><table class="salary-table"><thead><tr><th>Earnings</th><th>Amount (Rs.)</th><th>Deductions</th><th>Amount (Rs.)</th></tr></thead><tbody>
  <?php for($i=0;$i<$maxRows;$i++): ?><tr><td><?=htmlspecialchars($earningRows[$i]['component_name']??'—')?></td><td class="amount"><?=isset($earningRows[$i])?number_format((float)$earningRows[$i]['amount'],2):'—'?></td><td><?=htmlspecialchars($deductionRows[$i]['component_name']??'—')?></td><td class="amount"><?=isset($deductionRows[$i])?number_format((float)$deductionRows[$i]['amount'],2):'—'?></td></tr><?php endfor; ?>
 </tbody></table></section>
 <section class="summary"><div class="summary-item"><div class="summary-label">Gross Salary</div><div class="summary-value">Rs. <?=number_format((float)$row['gross_salary'],2)?></div></div><div class="summary-item"><div class="summary-label">Total Deductions</div><div class="summary-value">Rs. <?=number_format((float)$row['deduction'],2)?></div></div><div class="summary-item"><div class="summary-label">Net Payable</div><div class="summary-value">Rs. <?=number_format((float)$row['net_salary'],2)?></div></div></section>
 <div class="words"><strong>Note:</strong> This is a system-generated salary slip. Please contact Payroll if any information requires correction.</div>
 <div class="signature-row"><div class="signature">Employee Signature</div><div class="signature">Authorized Signature</div></div>
 </div><footer class="footer"><span class="confidential"><i class="fa fa-lock me-1"></i>CONFIDENTIAL</span><span>Generated by EMS • <?=date('d M Y, h:i A')?></span></footer></article>
</body></html>
