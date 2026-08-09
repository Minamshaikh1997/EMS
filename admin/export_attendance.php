<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/admincheck_role.php';

$department = trim((string)($_GET['department'] ?? ''));
$employeeId = max(0, (int)($_GET['employee_id'] ?? 0));
$month = min(12, max(1, (int)($_GET['month'] ?? date('n'))));
$year = min((int)date('Y') + 1, max(2020, (int)($_GET['year'] ?? date('Y'))));
$includeInactive = (string)($_GET['inactive'] ?? '0') === '1';
$search = trim((string)($_GET['search'] ?? ''));
$from = sprintf('%04d-%02d-01', $year, $month);
$to = date('Y-m-t', strtotime($from));

$sql = "SELECT e.employee_id,e.full_name,e.department,a.attendance_date,a.check_in,a.check_out,a.working_hours,a.status
        FROM attendance a INNER JOIN employees e ON e.id=a.employee_id
        WHERE a.attendance_date BETWEEN ? AND ?";
$types = 'ss';
$params = [$from, $to];
if (!$includeInactive) $sql .= " AND e.status='Active' AND e.is_active=1";
if ($department !== '') { $sql .= ' AND e.department=?'; $types .= 's'; $params[] = $department; }
if ($employeeId > 0) { $sql .= ' AND e.id=?'; $types .= 'i'; $params[] = $employeeId; }
if ($search !== '') {
    $like = '%' . $search . '%';
    $sql .= ' AND (e.full_name LIKE ? OR e.employee_id LIKE ?)';
    $types .= 'ss'; $params[] = $like; $params[] = $like;
}
$sql .= ' ORDER BY a.attendance_date,e.full_name';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

function attendanceExcelCell($value): string {
    $value = (string)$value;
    if (preg_match('/^[=+\-@]/', $value)) $value = "'" . $value;
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$filename = sprintf('Attendance_Report_%04d_%02d.xls', $year, $month);
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename=' . $filename);
header('Cache-Control: no-store, no-cache, must-revalidate');

echo "\xEF\xBB\xBF";
echo '<table border="1">';
echo '<tr><th colspan="9" style="font-size:16px">Attendance Report - ' . attendanceExcelCell(date('F Y', strtotime($from))) . '</th></tr>';
echo '<tr style="font-weight:bold;background:#dff5f2"><th>Sr. No.</th><th>Employee ID</th><th>Employee Name</th><th>Department</th><th>Date</th><th>Time In</th><th>Time Out</th><th>Working Hours</th><th>Status</th></tr>';
$serial = 0;
while ($row = $result->fetch_assoc()) {
    echo '<tr>';
    echo '<td>' . (++$serial) . '</td>';
    echo '<td>' . attendanceExcelCell($row['employee_id']) . '</td>';
    echo '<td>' . attendanceExcelCell($row['full_name']) . '</td>';
    echo '<td>' . attendanceExcelCell($row['department']) . '</td>';
    echo '<td>' . attendanceExcelCell(date('d-M-Y D', strtotime($row['attendance_date']))) . '</td>';
    echo '<td>' . attendanceExcelCell($row['check_in'] ?: '-') . '</td>';
    echo '<td>' . attendanceExcelCell($row['check_out'] ?: '-') . '</td>';
    echo '<td>' . attendanceExcelCell($row['working_hours'] ?: '-') . '</td>';
    echo '<td>' . attendanceExcelCell($row['status'] ?: 'Absent') . '</td>';
    echo '</tr>';
}
if ($serial === 0) echo '<tr><td colspan="9">No attendance records found</td></tr>';
echo '</table>';
