<?php

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/ui_enhancements.php';
if (PHP_SAPI !== 'cli') {
    ems_start_secure_session();
    ems_enable_ui_enhancements();
}

// Set timezone to Pakistan Standard Time
date_default_timezone_set('Asia/Karachi');

$appEnvironment = strtolower(trim((string)(getenv('EMS_APP_ENV') ?: '')));
$requestHost = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
$requestHost = preg_replace('/:\d+$/', '', $requestHost);
$isLocalHost = in_array($requestHost, ['localhost', '127.0.0.1', '::1', '[::1]'], true);
$serverAddress = (string)($_SERVER['SERVER_ADDR'] ?? '');
$isLoopbackServer = in_array($serverAddress, ['127.0.0.1', '::1'], true);
$isProduction = $appEnvironment === 'production';
// A Cloudflare quick tunnel preserves its public Host header while proxying to
// this local Apache instance. The loopback server address proves the request
// reached local XAMPP; production mode still always requires EMS_DB_* values.
$useLocalDatabase = !$isProduction && (PHP_SAPI === 'cli' || $isLocalHost || $isLoopbackServer);

if ($isProduction) {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
} elseif ($appEnvironment === 'development') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

if ($useLocalDatabase) {
    // Try localhost first
    $host = "localhost";
    $user = "root";
    $password = "";  // XAMPP default: no password for root user
    $database = "employee_leave_system";
} else {
    $host = getenv('EMS_DB_HOST') ?: '';
    $user = getenv('EMS_DB_USER') ?: '';
    $password = getenv('EMS_DB_PASSWORD') ?: '';
    $database = getenv('EMS_DB_NAME') ?: '';

    if ($host === '' || $user === '' || $database === '') {
        http_response_code(500);
        exit('Database configuration is missing.');
    }
}

// Create connection with improved settings to prevent "MySQL server has gone away"
$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    error_log('EMS database connection failed: ' . mysqli_connect_error());
    http_response_code(500);
    exit('Database connection is temporarily unavailable.');
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

require_once __DIR__ . '/page_permissions.php';
ems_enforce_admin_page_permission($conn);
?>
