<?php
require_once __DIR__ . '/permissions.php';

function ems_enforce_admin_page_permission(mysqli $conn): void
{
    if (!isset($_SESSION['admin'])) return;
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    if (!str_contains($script, '/admin/')) return;

    $page = basename($script);
    $map = [
        'dashboard.php'=>['dashboard','view'], 'mis_adjustment.php'=>['dashboard','view'],
        'employee.php'=>['employee_management','view'], 'employee_record.php'=>['employee_management','view'], 'employeeprofile.php'=>['employee_management','view'],
        'add_employee.php'=>['employee_management','create'], 'import_employees.php'=>['employee_management','create'],
        'edit_employee.php'=>['employee_management','edit'], 'employee_rights_management.php'=>['employee_management','edit'],
        'delete_employee.php'=>['employee_management','delete'], 'toggle_employee_status.php'=>['employee_management','delete'],
        'export_employee_records.php'=>['employee_management','export'],
        'attendance_report.php'=>['attendance','view'], 'export_attendance.php'=>['attendance','export'], 'attendance_control.php'=>['attendance','edit'], 'attendance_policy.php'=>['attendance','edit'], 'manage_shifts.php'=>['attendance','edit'],
        'admin_adjustments.php'=>['attendance','approve'], 'supervisor_adjustments.php'=>['attendance','approve'],
        'leave_requests.php'=>['leave_management','view'], 'approve_leave.php'=>['leave_management','approve'], 'reject_leave.php'=>['leave_management','approve'],
        'requisitions.php'=>['requisitions','view'],
        'payroll_dashboard.php'=>['payroll','view'], 'payroll_history.php'=>['payroll','view'], 'payroll_reports.php'=>['payroll','view'], 'monthly_payroll.php'=>['payroll','view'], 'print_salary_slip.php'=>['payroll','view'], 'export_salary_slip.php'=>['payroll','export'],
        'generate_payroll.php'=>['payroll','create'], 'salary_slips.php'=>['payroll','create'],
        'salary_structure.php'=>['payroll','edit'], 'edit_salary.php'=>['payroll','edit'], 'salary_components.php'=>['payroll','edit'],
        'reports.php'=>['reports','view'], 'mis_adjustment.php'=>['reports','view'], 'psm_kpi.php'=>['reports','view'], 'campaign_kpi_setup.php'=>['reports','edit'], 'campaign_performance.php'=>['reports','edit'], 'export_excel.php'=>['reports','export'],
        'add_notice.php'=>['notifications','create'], 'add_holiday.php'=>['notifications','create'], 'send_email.php'=>['notifications','create'],
        'role_permissions.php'=>['role_permission','edit'], 'security_audit.php'=>['settings','view'], 'company_details.php'=>['settings','edit'],
        'access_control.php'=>['role_permission','edit'],
    ];
    if (isset($map[$page])) requirePermission($conn, $map[$page][0], $map[$page][1]);
}
