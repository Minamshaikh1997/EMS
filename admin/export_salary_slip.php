<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/audit.php';

$isAdminExport = isset($_SESSION['admin']);
$isEmployeeExport = isset($_SESSION['employee_id']);
$adminRole = ems_canonical_admin_role((string)($_SESSION['admin_role'] ?? ''));
if ((!$isAdminExport && !$isEmployeeExport)
    || ($isAdminExport && !in_array($adminRole, ['Super Admin', 'Admin', 'Finance Manager', 'Accountant'], true))) {
    http_response_code(403);
    exit('Access denied.');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(422);
    exit('Invalid salary slip.');
}

if ($isEmployeeExport) {
    $employeeId = (int)$_SESSION['employee_id'];
    $stmt = $conn->prepare('SELECT ss.*,e.employee_id AS emp_code,e.full_name,e.department,e.designation FROM salary_slips ss INNER JOIN employees e ON e.id=ss.employee_id WHERE ss.id=? AND ss.employee_id=? AND e.can_view_payroll=1 LIMIT 1');
    $stmt->bind_param('ii', $id, $employeeId);
} else {
    $stmt = $conn->prepare('SELECT ss.*,e.employee_id AS emp_code,e.full_name,e.department,e.designation FROM salary_slips ss INNER JOIN employees e ON e.id=ss.employee_id WHERE ss.id=? LIMIT 1');
    $stmt->bind_param('i', $id);
}
$stmt->execute();
$slip = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$slip) {
    http_response_code(404);
    exit('Salary slip not found.');
}

$companyName = 'Employee Management System';
$companyEmail = '';
$companyAddress = '';
try {
    $companyResult = $conn->query('SELECT company_name,email,address,city,country FROM company_settings WHERE id=1 LIMIT 1');
    if ($companyResult && ($company = $companyResult->fetch_assoc())) {
        $companyName = trim((string)$company['company_name']) ?: $companyName;
        $companyEmail = trim((string)$company['email']);
        $companyAddress = implode(', ', array_filter([
            trim((string)$company['address']), trim((string)$company['city']), trim((string)$company['country'])
        ]));
    }
} catch (Throwable $error) {
    error_log('Salary slip company lookup failed: ' . $error->getMessage());
}

function pdfText(string $text): string
{
    $text = preg_replace('/[^\x20-\x7E]/', '', $text) ?? '';
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}

function pdfLine(string $text, int $x, int $y, int $size = 11, bool $bold = false): string
{
    $font = $bold ? 'F2' : 'F1';
    return "BT /$font $size Tf 1 0 0 1 $x $y Tm (" . pdfText($text) . ") Tj ET\n";
}

$month = date('F Y', strtotime($slip['salary_month'] . '-01'));
$content = "0.15 0.28 0.55 rg 40 755 515 62 re f\n";
$content .= "1 1 1 rg" . "\n" . pdfLine($companyName, 60, 790, 20, true);
$content .= pdfLine('SALARY SLIP - ' . strtoupper($month), 60, 770, 11, true);
$content .= "0 0 0 rg\n";
if ($companyAddress !== '') $content .= pdfLine($companyAddress, 60, 735, 9);
if ($companyEmail !== '') $content .= pdfLine($companyEmail, 60, 721, 9);

$content .= "0.92 0.95 0.99 rg 40 650 515 52 re f\n0 0 0 rg\n";
$content .= pdfLine('Employee ID: ' . $slip['emp_code'], 55, 682, 10, true);
$content .= pdfLine('Employee Name: ' . $slip['full_name'], 265, 682, 10, true);
$content .= pdfLine('Department: ' . ($slip['department'] ?: '-'), 55, 663, 10);
$content .= pdfLine('Designation: ' . ($slip['designation'] ?: '-'), 265, 663, 10);

$content .= pdfLine('SALARY DETAILS', 55, 620, 13, true);
$rows = [
    ['Basic Salary', (float)$slip['basic_salary']],
    ['Allowances', (float)$slip['allowance']],
    ['Gross Salary', (float)$slip['gross_salary']],
    ['Deductions', (float)$slip['deduction']],
];
$y = 590;
foreach ($rows as [$label, $amount]) {
    $content .= "0.85 0.87 0.90 RG 50 " . ($y - 8) . " 495 28 re S\n";
    $content .= pdfLine($label, 65, $y, 11, $label === 'Gross Salary');
    $content .= pdfLine('Rs. ' . number_format($amount, 2), 420, $y, 11, $label === 'Gross Salary');
    $y -= 36;
}
$content .= "0.12 0.65 0.45 rg 50 " . ($y - 10) . " 495 42 re f\n1 1 1 rg\n";
$content .= pdfLine('NET SALARY', 68, $y + 4, 14, true);
$content .= pdfLine('Rs. ' . number_format((float)$slip['net_salary'], 2), 400, $y + 4, 14, true);
$content .= "0 0 0 rg\n";
$content .= pdfLine('Employee Signature', 65, 180, 10);
$content .= pdfLine('Authorized Signature', 390, 180, 10);
$content .= "0.3 0.3 0.3 RG 55 195 m 205 195 l S 380 195 m 530 195 l S\n";
$content .= pdfLine('Generated on ' . date('d M Y, h:i A'), 205, 80, 8);

$objects = [];
$objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
$objects[2] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
$objects[3] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>';
$objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
$objects[5] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';
$objects[6] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "endstream";

$pdf = "%PDF-1.4\n";
$offsets = [0];
foreach ($objects as $number => $object) {
    $offsets[$number] = strlen($pdf);
    $pdf .= "$number 0 obj\n$object\nendobj\n";
}
$xref = strlen($pdf);
$pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
for ($i = 1; $i <= count($objects); $i++) $pdf .= sprintf('%010d 00000 n ', $offsets[$i]) . "\n";
$pdf .= 'trailer << /Size ' . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";

$safeCode = preg_replace('/[^a-zA-Z0-9_-]/', '-', (string)$slip['emp_code']);
$safeMonth = preg_replace('/[^0-9-]/', '', (string)$slip['salary_month']);
ems_audit($conn, 'salary_slip.exported', 'salary_slip', (int)$id, ['format' => 'PDF']);
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="salary-slip-' . $safeCode . '-' . $safeMonth . '.pdf"');
header('Content-Length: ' . strlen($pdf));
header('X-Content-Type-Options: nosniff');
echo $pdf;
