<?php
session_start();
include("config/db.php");

$error = "";
$debug_info = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = trim($_POST['password']);
    
    $debug_info = "Email: $email<br>";
    $debug_info .= "Password length: " . strlen($password) . "<br>";
    
    // Check admin table
    $adminQuery = mysqli_query($conn, "SELECT * FROM admin WHERE email='$email' LIMIT 1");
    $debug_info .= "Admin query result: " . (mysqli_num_rows($adminQuery) > 0 ? "FOUND" : "NOT FOUND") . "<br>";
    
    if ($adminQuery && mysqli_num_rows($adminQuery) > 0) {
        $admin = mysqli_fetch_assoc($adminQuery);
        $debug_info .= "Admin name: " . $admin['name'] . "<br>";
        $debug_info .= "Password hash length: " . strlen($admin['password']) . "<br>";
        
        $verify = password_verify($password, $admin['password']);
        $debug_info .= "password_verify: " . ($verify ? "TRUE" : "FALSE") . "<br>";
        
        $direct = ($password == $admin['password']);
        $debug_info .= "direct compare: " . ($direct ? "TRUE" : "FALSE") . "<br>";
        
        if ($verify || $direct) {
            $debug_info .= "LOGIN SUCCESS!<br>";
            $_SESSION['admin'] = $admin['email'];
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_role'] = isset($admin['role']) && !empty($admin['role']) ? $admin['role'] : 'Admin';
            header("Location: admin/dashboard.php");
            exit();
        } else {
            $error = "Password verification failed";
        }
    } else {
        $error = "Admin not found with email: $email";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Debug</title>
<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
.debug-box { background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 20px 0; }
.error { color: red; }
.success { color: green; }
input { padding: 10px; margin: 5px; width: 300px; }
button { padding: 10px 20px; background: #2563eb; color: white; border: none; cursor: pointer; }
</style>
</head>
<body>
<h1>Login Debug Tool</h1>

<?php if ($error): ?>
<div class="error"><strong>Error:</strong> <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($debug_info): ?>
<div class="debug-box">
    <h3>Debug Information:</h3>
    <?php echo $debug_info; ?>
</div>
<?php endif; ?>

<form method="POST">
    <h3>Test Login:</h3>
    <input type="email" name="email" value="admin@ems.com" placeholder="Email" required><br>
    <input type="text" name="password" value="admin123" placeholder="Password" required><br>
    <button type="submit">Test Login</button>
</form>

<hr>
<h3>Quick Links:</h3>
<ul>
    <li><a href="login.php">Normal Login Page</a></li>
    <li><a href="test_portal.php">Portal Diagnostic</a></li>
</ul>
</body>
</html>