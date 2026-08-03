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

if (!in_array($admin_role, ['Super Admin', 'Admin', 'Operations Manager'], true)) {
    http_response_code(403);
    exit('You do not have permission to change employee status.');
}

if (isset($_GET['id']) || isset($_POST['id'])) {
    $id = (int) ($_POST['id'] ?? $_GET['id']);

    // Check dependent records
    $leaveCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM leave_requests WHERE employee_id='$id'"))['c'];
    $attendanceCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM attendance WHERE employee_id='$id'"))['c'];
    $balanceCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM leave_balance WHERE employee_id='$id'"))['c'];

    $action = (string)($_POST['action'] ?? $_GET['action'] ?? 'confirm');

    if ($action !== 'confirm') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            exit('Method not allowed.');
        }
        ems_verify_csrf();
    }

    if ($action === 'confirm') {
        $employee = mysqli_fetch_assoc(mysqli_query($conn, "SELECT employee_id, full_name FROM employees WHERE id='$id' LIMIT 1"));
        $employeeLabel = $employee ? htmlspecialchars($employee['full_name'] . ' (' . $employee['employee_id'] . ')') : 'Employee #' . htmlspecialchars($id);
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Confirm Disable Employee</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <?php include("../dark_mode.php"); ?>
        <body class="bg-light">
        <div class="container py-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-danger text-white">
                    <h3 class="mb-0">Disable <?php echo $employeeLabel; ?></h3>
                </div>
                <div class="card-body">
                    <p class="lead">Are you sure you want to mark this employee as inactive?</p>
                    <p class="text-muted">This is a soft-delete. The employee's history (leave requests, attendance, etc.) will remain in the system.</p>
                    <div class="d-flex gap-2">
                        <form method="POST" action="delete_employee.php" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(ems_csrf_token()); ?>">
                            <input type="hidden" name="id" value="<?php echo $id; ?>">
                            <input type="hidden" name="action" value="deactivate">
                            <button type="submit" class="btn btn-danger">Confirm Disable</button>
                        </form>
                        <a href="employee.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
<?php include __DIR__ . '/../config/back_dashboard.php'; ?>
</body>
        </html>
        <?php
        exit();
    }

    if ($action === 'deactivate') {
        if (mysqli_query($conn, "UPDATE employees SET is_active=0 WHERE id='$id'")) {
            ems_audit($conn, 'employee.deactivated', 'employee', $id);
            header("Location: employee.php");
            exit();
        } else {
            echo "Error disabling employee: " . mysqli_error($conn);
            echo "<p><a href=\"employee.php\">Back</a></p>";
            exit();
        }
    }

    if ($action === 'activate') {
        if (mysqli_query($conn, "UPDATE employees SET is_active=1 WHERE id='$id'")) {
            ems_audit($conn, 'employee.activated', 'employee', $id);
            header("Location: employee.php");
            exit();
        } else {
            echo "Error activating employee: " . mysqli_error($conn);
            echo "<p><a href=\"employee.php\">Back</a></p>";
            exit();
        }
    }
}
?>
