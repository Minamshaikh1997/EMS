<?php

// Set timezone to Pakistan Standard Time
date_default_timezone_set('Asia/Karachi');

if (
    $_SERVER['HTTP_HOST'] == 'localhost' ||
    $_SERVER['HTTP_HOST'] == '127.0.0.1'
) {
    // Localhost
    $host = "localhost";
    $user = "root";
    $password = "";
    $database = "employee_leave_system";
} else {
    // InfinityFree
    $host = "sql202.infinityfree.com";
    $user = "YOUR_MYSQL_USERNAME";   // Panel se copy karein
    $password = "Roman1250";
    $database = "if0_42402211_EMS";
}

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}
?>