```php
<?php
session_start();
include("config/db.php");

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];

    // Try employee login first.
    $query = "SELECT * FROM employees WHERE email='$email' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        $validPassword = false;

        if (password_verify($password, $user['password'])) {
            $validPassword = true;
        } elseif ($password === $user['password']) {
            $validPassword = true;
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE employees SET password='$newHash' WHERE id='" . $user['id'] . "'");
        }

        if ($validPassword) {
            if (isset($user['role']) && $user['role'] === 'Admin') {
                $_SESSION['admin'] = $user['email'];
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_role'] = 'Admin';
                header("Location: admin/dashboard.php");
                exit();
            }

            $_SESSION['employee'] = $user['email'];
            $_SESSION['employee_id'] = $user['id'];
            $_SESSION['employee_role'] = isset($user['role']) ? $user['role'] : 'Employee';
            $_SESSION['employee_name'] = $user['full_name'];
            header("Location: employee/dashboard.php");
            exit();
        }
    }

    // Fallback: allow legacy admin table login.
    $query = "SELECT * FROM admin WHERE email='$email' LIMIT 1";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        $validPassword = false;

        if (password_verify($password, $user['password'])) {
            $validPassword = true;
        } elseif ($password === $user['password']) {
            $validPassword = true;
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE admin SET password='$newHash' WHERE id='" . $user['id'] . "'");
        }

        if ($validPassword) {
            $_SESSION['admin'] = $user['email'];
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_role'] = isset($user['role']) ? $user['role'] : 'Admin';
            header("Location: admin/dashboard.php");
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
    <title>Login - Employee Leave System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .login-box { max-width: 420px; margin: 80px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,.1); }
        .login-box h2 { text-align: center; margin-bottom: 25px; }
    </style>
</head>
<body>
<?php include("dark_mode.php"); ?>
<div class="login-box">
    <h2>Employee Leave System</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>
    <div class="mt-3 text-center">
        <a href="admin/login.php">Admin Login</a>
    </div>
</div>
</body>
</html>

```
