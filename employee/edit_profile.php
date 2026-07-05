<?php
session_start();

// If user not logged in, redirect to login
if (!isset($_SESSION['employee_id'])) {
    header('Location: login.php');
    exit();
}

// Redirect to the existing edit page
header('Location: employeeedit_profile.php');
exit();
?>