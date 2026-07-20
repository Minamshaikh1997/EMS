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
            $_SESSION['admin_role'] = isset($admin['role']) && !empty($admin['role']) ? $admin['role'] : 'Admin';

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
<title>Employee Leave System - Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    background: linear-gradient(135deg, #0f172a 0%, #2563eb 50%, #7c3aed 100%);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    padding: 20px;
}

.login-container {
    width: 100%;
    max-width: 420px;
}

.login-card {
    background: rgba(255, 255, 255, 0.97);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    padding: 44px 36px;
    border-radius: 20px;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.25);
    border: 1px solid rgba(255, 255, 255, 0.3);
    animation: slideUp 0.5s ease-out;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.logo-section {
    text-align: center;
    margin-bottom: 28px;
}

.logo-icon {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #2563eb, #7c3aed);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    box-shadow: 0 8px 24px rgba(37, 99, 235, 0.3);
}

.logo-icon i {
    color: white;
    font-size: 28px;
}

.login-card h2 {
    text-align: center;
    margin-bottom: 8px;
    color: #1e293b;
    font-weight: 800;
    font-size: 26px;
    letter-spacing: -0.5px;
}

.subtitle {
    text-align: center;
    color: #94a3b8;
    font-size: 14px;
    margin-bottom: 28px;
    font-weight: 500;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    font-weight: 600;
    color: #334155;
    font-size: 13px;
    margin-bottom: 8px;
    display: block;
}

.form-control {
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 12px 16px;
    font-size: 14px;
    transition: all 0.25s ease;
    background: #f8fafc;
    font-family: 'Inter', sans-serif;
}

.form-control:focus {
    border-color: #2563eb;
    background: white;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    outline: none;
}

.btn-login {
    width: 100%;
    padding: 13px 20px;
    background: linear-gradient(135deg, #2563eb, #7c3aed);
    border: none;
    border-radius: 12px;
    color: white;
    font-weight: 700;
    font-size: 15px;
    margin-top: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(37, 99, 235, 0.3);
    cursor: pointer;
    font-family: 'Inter', sans-serif;
}

.btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 28px rgba(37, 99, 235, 0.4);
    color: white;
}

.btn-login:active {
    transform: translateY(0);
}

.error-alert {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
    padding: 12px 16px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.error-alert i { font-size: 16px; }

.divider {
    text-align: center;
    margin: 24px 0;
    position: relative;
}

.divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: #e2e8f0;
}

.divider-text {
    background: white;
    padding: 0 12px;
    color: #94a3b8;
    font-size: 12px;
    font-weight: 600;
    position: relative;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.admin-link {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-align: center;
    color: #2563eb;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s ease;
    padding: 10px;
    border-radius: 10px;
    background: rgba(37, 99, 235, 0.06);
}

.admin-link:hover {
    background: rgba(37, 99, 235, 0.12);
    color: #1d4ed8;
    text-decoration: none;
}

.footer-text {
    text-align: center;
    margin-top: 24px;
    color: #94a3b8;
    font-size: 12px;
    font-weight: 500;
}

@media (max-width: 480px) {
    .login-card {
        padding: 32px 24px;
    }
    .login-card h2 {
        font-size: 22px;
    }
    .logo-icon {
        width: 54px;
        height: 54px;
    }
    .logo-icon i {
        font-size: 24px;
    }
}

body.dark-mode {
    background: linear-gradient(135deg, #000 0%, #0f172a 50%, #1e1b4b 100%);
}

.dark-mode .login-card {
    background: rgba(30, 41, 59, 0.97);
    border-color: rgba(255, 255, 255, 0.08);
}

.dark-mode .login-card h2 { color: #f1f5f9; }
.dark-mode .subtitle { color: #64748b; }
.dark-mode .form-label { color: #cbd5e1; }
.dark-mode .form-control {
    background: rgba(255, 255, 255, 0.06);
    border-color: rgba(255, 255, 255, 0.1);
    color: #e2e8f0;
}
.dark-mode .form-control:focus {
    background: rgba(255, 255, 255, 0.1);
    border-color: #3b82f6;
}
.dark-mode .divider::before { background: rgba(255, 255, 255, 0.1); }
.dark-mode .divider-text { background: rgba(30, 41, 59, 0.97); color: #64748b; }
.dark-mode .admin-link { background: rgba(59, 130, 246, 0.1); color: #60a5fa; }
.dark-mode .admin-link:hover { background: rgba(59, 130, 246, 0.2); }
.dark-mode .footer-text { color: #475569; }
</style>
</head>
<body>

<?php include("dark_mode.php"); ?>

<div class="login-container">
    <div class="login-card">

        <div class="logo-section">
            <div class="logo-icon">
                <i class="fas fa-briefcase"></i>
            </div>
            <h2>Welcome Back</h2>
            <p class="subtitle">Sign in to your account</p>
        </div>

        <?php if (!empty($error)) { ?>
            <div class="error-alert">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php } ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-envelope" style="margin-right: 6px;"></i>Email
                </label>
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-lock" style="margin-right: 6px;"></i>Password
                </label>
                <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i>Sign In
            </button>
        </form>

        <div class="divider">
            <span class="divider-text">Administrator</span>
        </div>

        <a href="admin/login.php" class="admin-link">
            <i class="fas fa-user-shield"></i>
            Admin Login
        </a>

        <div class="footer-text">
            &copy; 2024 Employee Leave System. All rights reserved.
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>