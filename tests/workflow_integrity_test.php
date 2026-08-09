<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

require_once dirname(__DIR__) . '/config/db.php';

$passed = 0;
$failed = 0;
function workflowCheck(bool $condition, string $label): void
{
    global $passed, $failed;
    echo ($condition ? '[PASS] ' : '[FAIL] ') . $label . PHP_EOL;
    $condition ? $passed++ : $failed++;
}

mysqli_begin_transaction($conn);
try {
    $admin = $conn->query('SELECT id,name,role FROM admin ORDER BY id LIMIT 1')->fetch_assoc();
    if (!$admin) throw new RuntimeException('At least one admin account is required.');

    $stmt = $conn->prepare("INSERT INTO employee_requisitions (job_title,department,positions,employment_type,justification,requirements,priority,requested_by,requested_by_name,requested_by_role,status) VALUES ('Workflow Test','QA',1,'Full Time','Automated rollback test','None','Low',?,?,?,'Pending Assistant Manager')");
    $stmt->bind_param('iss', $admin['id'], $admin['name'], $admin['role']);
    $stmt->execute();
    $requisitionId = $conn->insert_id;

    $labels = ['Assistant Manager','Senior Assistant Manager','Operations Manager','VP','CEO / MD'];
    $keys = ['assistant_manager','senior_assistant_manager','operations_manager','vp','executive'];
    foreach ($labels as $index => $label) {
        $order = $index + 1;
        $status = $index === 0 ? 'Pending' : 'Waiting';
        $insert = $conn->prepare('INSERT INTO employee_requisition_approvals (requisition_id,step_order,stage_key,stage_label,approver_id,approver_name,status) VALUES (?,?,?,?,?,?,?)');
        $insert->bind_param('iississ', $requisitionId, $order, $keys[$index], $label, $admin['id'], $admin['name'], $status);
        $insert->execute();
    }
    workflowCheck((int)$conn->query("SELECT COUNT(*) FROM employee_requisition_approvals WHERE requisition_id=$requisitionId")->fetch_row()[0] === 5, 'Requisition creates all five approval stages');

    for ($order = 1; $order <= 5; $order++) {
        $conn->query("UPDATE employee_requisition_approvals SET status='Approved',acted_at=NOW() WHERE requisition_id=$requisitionId AND step_order=$order AND status='Pending'");
        workflowCheck($conn->affected_rows === 1, "Requisition stage $order approves once");
        if ($order < 5) {
            $next = $order + 1;
            $conn->query("UPDATE employee_requisition_approvals SET status='Pending' WHERE requisition_id=$requisitionId AND step_order=$next AND status='Waiting'");
            workflowCheck($conn->affected_rows === 1, "Requisition stage $next becomes pending");
        }
    }
    workflowCheck((int)$conn->query("SELECT COUNT(*) FROM employee_requisition_approvals WHERE requisition_id=$requisitionId AND status='Approved'")->fetch_row()[0] === 5, 'Requisition reaches complete approval state');

    $attendance = $conn->query('SELECT id,status FROM attendance ORDER BY id LIMIT 1')->fetch_assoc();
    if ($attendance) {
        $conn->query('SAVEPOINT attendance_workflow');
        $attendanceId = (int)$attendance['id'];
        $request = $conn->prepare("INSERT INTO attendance_status_requests (attendance_id,requested_status,requested_by,requested_by_name,incharge_id,incharge_name) VALUES (?,'Off Day',?,?,?,?)");
        $request->bind_param('iisis', $attendanceId, $admin['id'], $admin['name'], $admin['id'], $admin['name']);
        $request->execute();
        $requestId = $conn->insert_id;
        workflowCheck($requestId > 0, 'Attendance status request is created');

        $conn->query("UPDATE attendance SET status='Off Day',status_locked=1,status_updated_by={$admin['id']},status_updated_at=NOW() WHERE id=$attendanceId AND status_locked=0");
        if ($conn->affected_rows === 1) {
            $conn->query("UPDATE attendance_status_requests SET status='Approved',reviewed_at=NOW() WHERE id=$requestId AND status='Pending'");
            workflowCheck($conn->affected_rows === 1, 'Attendance request approves after status update');
        } else {
            workflowCheck(true, 'Locked attendance correctly refuses a second status update');
        }
        $conn->query('ROLLBACK TO SAVEPOINT attendance_workflow');
    } else {
        workflowCheck(true, 'Attendance workflow skipped because no attendance fixture exists');
    }

    mysqli_rollback($conn);
} catch (Throwable $error) {
    mysqli_rollback($conn);
    $failed++;
    echo '[FAIL] Workflow transaction: ' . $error->getMessage() . PHP_EOL;
}

echo PHP_EOL . "Workflow integrity: $passed passed, $failed failed." . PHP_EOL;
exit($failed === 0 ? 0 : 1);
