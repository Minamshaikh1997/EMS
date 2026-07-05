<?php
// Clear all departments (admin-only). Use with caution.
// Visit this page and confirm to delete all rows from `departments`.

session_start();
include('../config/db.php');

if (!isset($_SESSION['admin'])) {
    echo "Access denied. Log in as admin to continue.";
    exit;
}

if (!isset($_GET['confirm'])) {
    echo "<h3>Delete All Departments</h3>";
    echo "<p>This will permanently remove all rows from the <strong>departments</strong> table.</p>";
    echo "<p>If you are sure, click: <a href=\"clear_departments.php?confirm=1\">Confirm Delete All Departments</a></p>";
    echo "<p>Important: delete this file after use for safety.</p>";
    echo "<p><a href=\"check_departments.php\">Back</a></p>";
    exit;
}

// Perform deletion
mysqli_query($conn, "START TRANSACTION");
$res = mysqli_query($conn, "DELETE FROM departments");
if ($res) {
    mysqli_query($conn, "COMMIT");
    echo "All departments deleted successfully.";
} else {
    mysqli_query($conn, "ROLLBACK");
    echo "Error deleting departments: " . mysqli_error($conn);
}

echo "<p><a href=\"check_departments.php\">Check Departments</a></p>";
echo "<p>Remove this file when finished: admin/clear_departments.php</p>";

?>
