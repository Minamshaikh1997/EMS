<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

require_once dirname(__DIR__) . '/config/db.php';

$passed = 0;
$failed = 0;

function checkResult(bool $condition, string $label, string $details = ''): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "[PASS] $label\n";
    } else {
        $failed++;
        echo "[FAIL] $label" . ($details !== '' ? ": $details" : '') . "\n";
    }
}

function scalar(mysqli $conn, string $sql): int
{
    $result = $conn->query($sql);
    return (int)$result->fetch_row()[0];
}

$root = dirname(__DIR__);
foreach (['config/security.php', 'config/audit.php', 'config/page_permissions.php', '.htaccess', 'uploads/.htaccess', 'storage/backups/.htaccess', 'tools/backup.ps1'] as $file) {
    checkResult(is_file($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file)), "Required security file: $file");
}

$requiredTables = [
    'admin', 'employees', 'attendance', 'attendance_adjustments', 'attendance_status_requests',
    'leave_requests', 'payroll', 'employee_requisitions', 'employee_requisition_approvals',
    'performance_campaigns', 'campaign_kpis', 'employee_campaign_assignments',
    'campaign_performance', 'campaign_performance_scores', 'employee_kpi_performance', 'mis_adjustments',
    'security_audit_log', 'shift_history', 'auth_login_attempts', 'roles', 'permissions', 'role_permissions',
];
foreach ($requiredTables as $table) {
    $stmt = $conn->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?');
    $stmt->bind_param('s', $table);
    $stmt->execute();
    checkResult((int)$stmt->get_result()->fetch_row()[0] === 1, "Required table: $table");
    $stmt->close();
}

$requiredEmployeeColumns = [
    'status', 'reporting_manager_id', 'reporting_supervisor_id', 'reporting_team_lead_id',
    'can_view_payroll', 'can_apply_leave', 'can_view_attendance', 'can_submit_adjustment',
    'can_edit_profile', 'can_view_reports', 'can_change_password',
];
foreach ($requiredEmployeeColumns as $column) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='employees' AND column_name=?");
    $stmt->bind_param('s', $column);
    $stmt->execute();
    checkResult((int)$stmt->get_result()->fetch_row()[0] === 1, "Employee column: $column");
    $stmt->close();
}

$requiredAdjustmentColumns = [
    'request_no', 'attendance_id', 'attendance_date', 'adjustment_type',
    'requested_check_in', 'requested_check_out', 'attachment',
    'supervisor_comment', 'supervisor_date', 'admin_comment', 'admin_id', 'admin_date',
];
foreach ($requiredAdjustmentColumns as $column) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='attendance_adjustments' AND column_name=?");
    $stmt->bind_param('s', $column);
    $stmt->execute();
    checkResult((int)$stmt->get_result()->fetch_row()[0] === 1, "Adjustment column: $column");
    $stmt->close();
}

$requiredAttendanceColumns = ['status_locked', 'status_updated_by', 'status_updated_at'];
foreach ($requiredAttendanceColumns as $column) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='attendance' AND column_name=?");
    $stmt->bind_param('s', $column);
    $stmt->execute();
    checkResult((int)$stmt->get_result()->fetch_row()[0] === 1, "Attendance status column: $column");
    $stmt->close();
}

$attendanceStatusType = $conn->query("SELECT column_type FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='attendance' AND column_name='status'")->fetch_row()[0] ?? '';
checkResult(str_contains($attendanceStatusType, "'Off Day'") && str_contains($attendanceStatusType, "'NH'"), 'Attendance day statuses installed');

$knownAdminRoles = ['Super Admin','Admin','Operations Manager','VP','Senior Assistant Manager','Assistant Manager','WFM Executive','Finance Manager','Accountant','Supervisor','Team Lead'];
$unknownAdminRoles = [];
$adminRoles = $conn->query('SELECT DISTINCT role FROM admin');
while ($adminRole = $adminRoles->fetch_assoc()) {
    $canonicalRole = ems_canonical_admin_role((string)$adminRole['role']);
    if (!in_array($canonicalRole, $knownAdminRoles, true)) $unknownAdminRoles[] = (string)$adminRole['role'];
}
checkResult($unknownAdminRoles === [], 'All admin roles map to known canonical roles', implode(', ', $unknownAdminRoles));

$unseededOperationalRoles = scalar($conn, "SELECT COUNT(*) FROM (SELECT r.id FROM roles r LEFT JOIN role_permissions rp ON rp.role_id=r.id WHERE r.role_name<>'Employee' GROUP BY r.id HAVING COUNT(rp.id)=0) unseeded");
checkResult($unseededOperationalRoles === 0, 'Operational roles have permission grants');

$adminPlain = scalar($conn, "SELECT COUNT(*) FROM admin WHERE password NOT LIKE '\$2y\$%' AND password NOT LIKE '\$argon2%'");
$employeePlain = scalar($conn, "SELECT COUNT(*) FROM employees WHERE password NOT LIKE '\$2y\$%' AND password NOT LIKE '\$argon2%'");
checkResult($adminPlain === 0, 'All admin passwords are hashed', "$adminPlain unsafe record(s)");
checkResult($employeePlain === 0, 'All employee passwords are hashed', "$employeePlain unsafe record(s)");

checkResult(scalar($conn, 'SELECT COUNT(*) FROM (SELECT email FROM employees GROUP BY email HAVING COUNT(*)>1) duplicates') === 0, 'No duplicate employee emails');
checkResult(scalar($conn, 'SELECT COUNT(*) FROM (SELECT employee_id FROM employees GROUP BY employee_id HAVING COUNT(*)>1) duplicates') === 0, 'No duplicate employee codes');
checkResult(scalar($conn, 'SELECT COUNT(*) FROM attendance a LEFT JOIN employees e ON e.id=a.employee_id WHERE e.id IS NULL') === 0, 'No orphan attendance records');
checkResult(scalar($conn, 'SELECT COUNT(*) FROM leave_requests l LEFT JOIN employees e ON e.id=l.employee_id WHERE e.id IS NULL') === 0, 'No orphan leave requests');
checkResult(scalar($conn, 'SELECT COUNT(*) FROM leave_balance l LEFT JOIN employees e ON e.id=l.employee_id WHERE e.id IS NULL') === 0, 'No orphan leave balances');
checkResult(scalar($conn, 'SELECT COUNT(*) FROM payroll p LEFT JOIN employees e ON e.id=p.employee_id WHERE e.id IS NULL') === 0, 'No orphan payroll records');
checkResult(scalar($conn, 'SELECT COUNT(*) FROM attendance_adjustments a LEFT JOIN employees e ON e.id=a.employee_id WHERE e.id IS NULL') === 0, 'No orphan attendance adjustment records');
checkResult(scalar($conn, 'SELECT COUNT(*) FROM employee_requisition_approvals a LEFT JOIN employee_requisitions r ON r.id=a.requisition_id WHERE r.id IS NULL') === 0, 'No orphan requisition approvals');
checkResult(scalar($conn, 'SELECT COUNT(*) FROM (SELECT employee_id,attendance_date FROM attendance GROUP BY employee_id,attendance_date HAVING COUNT(*)>1) duplicates') === 0, 'No duplicate daily attendance');

$payrollIndex = scalar($conn, "SELECT COUNT(DISTINCT index_name) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='payroll' AND index_name='uq_payroll_employee_period'");
checkResult($payrollIndex === 1, 'Payroll period unique constraint');

$salarySlipIndex = scalar($conn, "SELECT COUNT(DISTINCT index_name) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='salary_slips' AND index_name='uq_salary_slip_employee_month'");
$salaryComponentIndex = scalar($conn, "SELECT COUNT(DISTINCT index_name) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='salary_structure_components' AND index_name='uq_salary_structure_component'");
checkResult($salarySlipIndex === 1, 'Salary slip period unique constraint');
checkResult($salaryComponentIndex === 1, 'Salary structure component unique constraint');
checkResult(scalar($conn, 'SELECT COUNT(*) FROM salary_slips s LEFT JOIN employees e ON e.id=s.employee_id WHERE e.id IS NULL') === 0, 'No orphan salary slips');
checkResult(scalar($conn, 'SELECT COUNT(*) FROM salary_structure_components s LEFT JOIN salary_structure x ON x.id=s.salary_structure_id WHERE x.id IS NULL') === 0, 'No orphan salary structure components');

echo "\nHealth check: $passed passed, $failed failed.\n";
exit($failed === 0 ? 0 : 1);
