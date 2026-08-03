<?php
ini_set('display_errors', '0');
ini_set('log_errors', '1');

include("admincheck_role.php");
include("../config/db.php");
include_once("../config/audit.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}
ems_verify_csrf();

// Only Managers (Super Admin, Admin, Operations Manager) can change employee status
if (!in_array($admin_role, ['Super Admin', 'Admin', 'Operations Manager'])) {
    $_SESSION['status_error'] = "You do not have permission to change employee status!";
    header("Location: dashboard.php");
    exit();
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($id <= 0 || !in_array($action, ['activate', 'deactivate'])) {
    $_SESSION['status_error'] = "Invalid request!";
    header("Location: dashboard.php");
    exit();
}

// Get current employee info
$empQuery = mysqli_query($conn, "SELECT full_name FROM employees WHERE id = $id");
if (!$empQuery || mysqli_num_rows($empQuery) == 0) {
    $_SESSION['status_error'] = "Employee not found!";
    header("Location: dashboard.php");
    exit();
}

$empData = mysqli_fetch_assoc($empQuery);
$employeeName = $empData['full_name'];

if ($action == 'activate') {
    // Activate: Set status = 'Active' and is_active = 1
    $updateQuery = "UPDATE employees SET status = 'Active', is_active = 1 WHERE id = $id";
    $message = "Employee '$employeeName' has been activated successfully!";
    $errorMsg = "Failed to activate employee '$employeeName'!";
} else {
    // Deactivate: Set status = 'Inactive' and is_active = 0
    $updateQuery = "UPDATE employees SET status = 'Inactive', is_active = 0 WHERE id = $id";
    $message = "Employee '$employeeName' has been deactivated successfully!";
    $errorMsg = "Failed to deactivate employee '$employeeName'!";
}

if (mysqli_query($conn, $updateQuery)) {
    ems_audit($conn, 'employee.status_changed', 'employee', $id, ['status' => $action === 'activate' ? 'Active' : 'Inactive']);
    $_SESSION['status_success'] = $message;
} else {
    $_SESSION['status_error'] = $errorMsg . " MySQL Error: " . mysqli_error($conn);
}

header("Location: dashboard.php");
exit();
?>
