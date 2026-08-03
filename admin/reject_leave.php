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
    exit('You do not have permission to reject leave requests.');
}

if(isset($_POST['id']))
{
    $id = intval($_POST['id']);

    $stmt = $conn->prepare("UPDATE leave_requests SET status='Rejected' WHERE id=? AND status='Pending'");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        ems_audit($conn, 'leave.rejected', 'leave_request', $id);
    }
    $stmt->close();
}

header("Location: leave_requests.php");
exit();
?>
