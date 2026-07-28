<?php
// Simulate a login POST request
$_POST['email'] = 'admin@ems.com';
$_POST['password'] = 'admin123';

// Capture any output/errors
ob_start();
include('login.php');
$output = ob_get_clean();

echo "Login test completed\n";
echo "Check if redirected or if error was shown\n";
?>