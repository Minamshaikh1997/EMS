<?php

// Set timezone to Pakistan Standard Time
date_default_timezone_set('Asia/Karachi');

// Determine environment - check if running on localhost
$is_localhost = false;
if (isset($_SERVER['HTTP_HOST'])) {
    $is_localhost = (
        $_SERVER['HTTP_HOST'] == 'localhost' ||
        $_SERVER['HTTP_HOST'] == '127.0.0.1' ||
        $_SERVER['HTTP_HOST'] == '::1'
    );
}

// Also check if we're running from CLI or if localhost connection works
if ($is_localhost || !isset($_SERVER['HTTP_HOST'])) {
    // Try localhost first
    $host = "localhost";
    $user = "root";
    $password = "";  // XAMPP default: no password for root user
    $database = "employee_leave_system";
} else {
    // InfinityFree
    $host = "sql202.infinityfree.com";
    $user = "YOUR_MYSQL_USERNAME";   // Panel se copy karein
    $password = "Roman1250";
    $database = "if0_42402211_EMS";
}

// Create connection with improved settings to prevent "MySQL server has gone away"
$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

// Set MySQL connection options to prevent timeout issues
mysqli_set_charset($conn, "utf8mb4");

// Increase timeout settings to prevent "MySQL server has gone away" error
mysqli_query($conn, "SET SESSION wait_timeout = 28800");  // 8 hours
mysqli_query($conn, "SET SESSION interactive_timeout = 28800");  // 8 hours
mysqli_query($conn, "SET SESSION net_read_timeout = 120");
mysqli_query($conn, "SET SESSION net_write_timeout = 120");

// Enable MySQLi exceptions for better error handling
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
?>
