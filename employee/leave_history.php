<?php
session_start();

if (!isset($_SESSION['employee_id'])) {
    header('Location: login.php');
    exit();
}

// Redirect to the actual leave history page
header('Location: my_leaves.php');
exit();
?>