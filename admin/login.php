<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login - EMS</title>
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

.footer-text {
    text-align: center;
    margin-top: 24px;
    color: #94a3b8;
    font-size: 12px;
}

.back-link {
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

.back-link:hover {
    background: rgba(37, 99, 235, 0.12);
    color: #1d4ed8;
    text-decoration: none;
}

@media (max-width: 480px) {
    .login-card { padding: 32px 24px; }
    .login-card h2 { font-size: 22px; }
    .logo-icon { width: 54px; height: 54px; }
    .logo-icon i { font-size: 24px; }
}
</style>
</head>
<body>
<div class="login-container">
    <div class="login-card">
        <div class="logo-section">
            <div class="logo-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <h2>Admin Login</h2>
            <p class="subtitle">Sign in to admin panel</p>
        </div>

        <form method="POST" action="../login.php">
            <div class="form-group">
                <label class="form-label"><i class="fas fa-envelope" style="margin-right: 6px;"></i>Email</label>
                <input type="email" name="email" class="form-control" placeholder="Admin email" required>
            </div>
            <div class="form-group">
                <label class="form-label"><i class="fas fa-lock" style="margin-right: 6px;"></i>Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter password" required>
            </div>
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i>Sign In
            </button>
        </form>

        <div class="footer-text">
            <a href="../index.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Back to Employee Login
            </a>
        </div>
    </div>
</div>
</body>
</html>