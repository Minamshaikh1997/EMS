<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Admin Login Check
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.html");
    exit();
}

// Current Admin Role
$admin_role = $_SESSION['admin_role'] ?? '';

$admin_name = $_SESSION['admin_name'] ?? '';

if (($admin_name == '' || $admin_role == '') && isset($conn)) {
    $admin_id = isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : 0;
    $admin_email = isset($_SESSION['admin']) ? mysqli_real_escape_string($conn, $_SESSION['admin']) : '';

    if ($admin_id > 0) {
        $adminQuery = mysqli_query($conn, "SELECT name, role FROM admin WHERE id='$admin_id' LIMIT 1");
    } else {
        $adminQuery = mysqli_query($conn, "SELECT name, role FROM admin WHERE email='$admin_email' LIMIT 1");
    }

    if ($adminQuery && mysqli_num_rows($adminQuery) > 0) {
        $adminRow = mysqli_fetch_assoc($adminQuery);
        if (empty($admin_name)) {
            $admin_name = $adminRow['name'];
            $_SESSION['admin_name'] = $admin_name;
        }
        if (empty($admin_role)) {
            $admin_role = $adminRow['role'] ?? 'Admin';
            $_SESSION['admin_role'] = $admin_role;
        }
    }
}

if ($admin_name == '') {
    $admin_name = $_SESSION['admin'] ?? 'Admin';
}
if ($admin_role == '') {
    $admin_role = 'Admin';
}

// Include roles helper
include_once("roles_helper.php");
