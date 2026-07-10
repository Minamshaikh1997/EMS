<?php

session_start();
include("config/db.php");

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = trim($_POST['password']);

    // ==========================
    // ADMIN LOGIN
    // ==========================
    $adminQuery = mysqli_query($conn, "SELECT * FROM admin WHERE email='$email' LIMIT 1");

    if ($adminQuery && mysqli_num_rows($adminQuery) > 0) {

        $admin = mysqli_fetch_assoc($adminQuery);

        if (password_verify($password, $admin['password']) || $password == $admin['password']) {

            if ($password == $admin['password']) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                mysqli_query($conn, "UPDATE admin SET password='$hash' WHERE id='".$admin['id']."'");
            }

            $_SESSION['admin'] = $admin['email'];
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_role'] = "Admin";

            

            header("Location: admin/dashboard.php");
            exit();
        }
    }

    // ==========================
    // EMPLOYEE LOGIN
    // ==========================
    $empQuery = mysqli_query($conn, "SELECT * FROM employees WHERE email='$email' LIMIT 1");

    if ($empQuery && mysqli_num_rows($empQuery) > 0) {

        $emp = mysqli_fetch_assoc($empQuery);

        if (password_verify($password, $emp['password']) || $password == $emp['password']) {

            if ($password == $emp['password']) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                mysqli_query($conn, "UPDATE employees SET password='$hash' WHERE id='".$emp['id']."'");
            }

            $_SESSION['employee'] = $emp['email'];
            $_SESSION['employee_id'] = $emp['id'];
            $_SESSION['employee_name'] = $emp['full_name'];
            $_SESSION['employee_role'] = $emp['role'];

            header("Location: employee/dashboard.php");
            exit();
        }
    }

    $error = "Invalid Email or Password";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Employee Leave System</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

<style>

body{
    background:linear-gradient(135deg,#0f172a,#2563eb);
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    font-family:Arial,Helvetica,sans-serif;
}

.login-card{
    width:420
