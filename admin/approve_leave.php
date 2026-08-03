<?php
require_once("../config/security.php");
ems_start_secure_session();

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

include("../config/db.php");
include_once("../config/audit.php");
include_once("admincheck_role.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}
ems_verify_csrf();

if (!in_array($admin_role, ['Super Admin', 'Admin', 'Operations Manager', 'Supervisor', 'Team Lead'], true)) {
    http_response_code(403);
    exit('You do not have permission to approve leave requests.');
}

if(isset($_POST['id']))
{
    $id = intval($_POST['id']);
    mysqli_begin_transaction($conn);

    $stmt = $conn->prepare("SELECT employee_id,leave_type,total_days FROM leave_requests WHERE id=? AND status='Pending' FOR UPDATE");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $leave = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$leave) {
        mysqli_rollback($conn);
        header("Location: leave_requests.php");
        exit();
    }

    $employee_id = (int)$leave['employee_id'];
    $days = (int)$leave['total_days'];
    $type = trim((string)$leave['leave_type']);
    $balanceColumns = ['Annual' => 'annual_leave', 'Sick' => 'sick_leave', 'Casual' => 'casual_leave'];
    if (!isset($balanceColumns[$type]) || $days <= 0) {
        mysqli_rollback($conn);
        http_response_code(422);
        exit('Invalid leave request.');
    }

    $balanceColumn = $balanceColumns[$type];
    $stmt = $conn->prepare("UPDATE employees SET {$balanceColumn}={$balanceColumn}-? WHERE id=? AND {$balanceColumn}>=?");
    $stmt->bind_param('iii', $days, $employee_id, $days);
    $stmt->execute();
    $balanceUpdated = $stmt->affected_rows === 1;
    $stmt->close();
    if (!$balanceUpdated) {
        mysqli_rollback($conn);
        echo "<script>alert('Employee does not have enough " . htmlspecialchars($type, ENT_QUOTES) . " Leave.');window.location='leave_requests.php';</script>";
        exit();
    }

    $stmt = $conn->prepare("UPDATE leave_requests SET status='Approved' WHERE id=? AND status='Pending'");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $approved = $stmt->affected_rows === 1;
    $stmt->close();
    if (!$approved) {
        mysqli_rollback($conn);
        header("Location: leave_requests.php");
        exit();
    }

    ems_audit($conn, 'leave.approved', 'leave_request', $id, [
        'employee_id' => $employee_id, 'leave_type' => $type, 'days' => $days,
    ]);
    mysqli_commit($conn);

    echo "<script>
    alert('Leave Approved Successfully.');
    window.location='leave_requests.php';
    </script>";

    exit();

}

header("Location: leave_requests.php");
exit();

?>
