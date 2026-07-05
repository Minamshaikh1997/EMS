<?php
// Backup and remove ALL employees and their dependent records.
// ADMIN ONLY. Use with extreme caution. Remove this file after use.

session_start();
include('../config/db.php');

if (!isset($_SESSION['admin'])) {
    echo "Access denied. Log in as admin to continue.";
    exit;
}

if (!isset($_GET['confirm'])) {
    echo "<h3>Delete ALL Employees (with backup)</h3>";
    echo "<p>This operation will create backups of the following tables and then delete ALL rows from them:</p>";
    echo "<ul><li>employees</li><li>leave_requests</li><li>attendance</li><li>leave_balance</li></ul>";
    echo "<p>If you are sure, click: <a href=\"clear_employees.php?confirm=1\">Confirm and delete all employees</a></p>";
    echo "<p>Important: this cannot be undone unless you restore the backups. The backup tables will be created in the same database.</p>";
    echo "<p><a href=\"employee.php\">Cancel</a></p>";
    exit;
}

$ts = date('Ymd_His');
$backup_employees = "employees_backup_" . $ts;
$backup_leave_requests = "leave_requests_backup_" . $ts;
$backup_attendance = "attendance_backup_" . $ts;
$backup_leave_balance = "leave_balance_backup_" . $ts;

$errors = [];

// Create backups
if (!mysqli_query($conn, "CREATE TABLE `$backup_employees` AS SELECT * FROM employees")) $errors[] = mysqli_error($conn);
if (!mysqli_query($conn, "CREATE TABLE `$backup_leave_requests` AS SELECT * FROM leave_requests")) $errors[] = mysqli_error($conn);
if (!mysqli_query($conn, "CREATE TABLE `$backup_attendance` AS SELECT * FROM attendance")) $errors[] = mysqli_error($conn);
if (!mysqli_query($conn, "CREATE TABLE `$backup_leave_balance` AS SELECT * FROM leave_balance")) $errors[] = mysqli_error($conn);

if (!empty($errors)) {
    echo "Error creating backups:<br>" . implode('<br>', array_map('htmlspecialchars', $errors));
    echo "<p>Aborting.</p>";
    exit;
}

// Proceed to delete dependent rows and employees
mysqli_query($conn, "START TRANSACTION");
$errs = [];

if (!mysqli_query($conn, "DELETE FROM leave_requests")) $errs[] = mysqli_error($conn);
if (!mysqli_query($conn, "DELETE FROM attendance")) $errs[] = mysqli_error($conn);
if (!mysqli_query($conn, "DELETE FROM leave_balance")) $errs[] = mysqli_error($conn);
if (!mysqli_query($conn, "DELETE FROM employees")) $errs[] = mysqli_error($conn);

if (empty($errs)) {
    mysqli_query($conn, "COMMIT");
    echo "All employees and dependent records deleted. Backups created:<ul>";
    echo "<li>employees =&gt; " . htmlspecialchars($backup_employees) . "</li>";
    echo "<li>leave_requests =&gt; " . htmlspecialchars($backup_leave_requests) . "</li>";
    echo "<li>attendance =&gt; " . htmlspecialchars($backup_attendance) . "</li>";
    echo "<li>leave_balance =&gt; " . htmlspecialchars($backup_leave_balance) . "</li>";
    echo "</ul>";
    echo "<p><a href=\"employee.php\">Go to Employee list</a></p>";
    echo "<p>Remove this file now: admin/clear_employees.php</p>";
} else {
    mysqli_query($conn, "ROLLBACK");
    echo "Error deleting records:<br>" . implode('<br>', array_map('htmlspecialchars', $errs));
    echo "<p>Backups exist; you can investigate and restore manually if needed.</p>";
}

?>
