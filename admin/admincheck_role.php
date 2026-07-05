```php
<?php
session_start();

// Admin Login Check
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

// Current Admin Role
$admin_role = $_SESSION['admin_role'];
?>
```
