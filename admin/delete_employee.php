<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

include("../config/db.php");

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    // Check dependent records
    $leaveCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM leave_requests WHERE employee_id='$id'"))['c'];
    $attendanceCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM attendance WHERE employee_id='$id'"))['c'];
    $balanceCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM leave_balance WHERE employee_id='$id'"))['c'];

    // Support soft-delete: add is_active column if missing, then mark inactive/active
    mysqli_query($conn, "ALTER TABLE employees ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1");

    $action = isset($_GET['action']) ? $_GET['action'] : 'deactivate';

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
                        <a href="delete_employee.php?id=<?php echo $id; ?>&action=deactivate" class="btn btn-danger">Confirm Disable</a>
                        <a href="employee.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
        </body>
        </html>
        <?php
        exit();
    }

    if ($action === 'deactivate') {
        if (mysqli_query($conn, "UPDATE employees SET is_active=0 WHERE id='$id'")) {
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