<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySQL Status Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h2><i class="fas fa-heartbeat"></i> MySQL Connection Test</h2>
            </div>
            <div class="card-body">
                <h4>Testing MySQL Connection...</h4>
                
                <?php
                $host = "localhost";
                $user = "root";
                $password = "";
                $database = "employee_leave_system";
                
                // Test 1: Check if port 3306 is open
                echo "<h5>Test 1: Check if MySQL port (3306) is accessible</h5>";
                $connection = @fsockopen($host, 3306, $errno, $errstr, 5);
                if ($connection) {
                    echo "<div class='alert alert-success'><i class='fas fa-check'></i> Port 3306 is OPEN - MySQL is running!</div>";
                    fclose($connection);
                } else {
                    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> Port 3306 is CLOSED - MySQL is NOT running or blocked by firewall</div>";
                    echo "<div class='alert alert-warning'>Error: $errstr (Code: $errno)</div>";
                }
                
                // Test 2: Try to connect with mysqli
                echo "<h5>Test 2: Try MySQLi Connection</h5>";
                $conn = @mysqli_connect($host, $user, $password, $database);
                
                if ($conn) {
                    echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i> <strong>SUCCESS!</strong> Connected to MySQL database</div>";
                    echo "<div class='alert alert-info'>";
                    echo "<strong>Database:</strong> $database<br>";
                    echo "<strong>Server:</strong> " . mysqli_get_server_info($conn) . "<br>";
                    echo "<strong>Host:</strong> " . mysqli_get_host_info($conn) . "<br>";
                    echo "</div>";
                    
                    // Show tables
                    $tables = mysqli_query($conn, "SHOW TABLES");
                    echo "<div class='alert alert-secondary'><strong>Tables in database:</strong><ul>";
                    while ($table = mysqli_fetch_row($tables)) {
                        echo "<li>" . htmlspecialchars($table[0]) . "</li>";
                    }
                    echo "</ul></div>";
                    
                    mysqli_close($conn);
                } else {
                    echo "<div class='alert alert-danger'><i class='fas fa-times-circle'></i> <strong>FAILED!</strong> Cannot connect to MySQL</div>";
                    echo "<div class='alert alert-warning'>Error: " . mysqli_connect_error() . "</div>";
                }
                
                // Test 3: Check if MySQL service exists
                echo "<h5>Test 3: Check XAMPP MySQL Service</h5>";
                exec('tasklist /FI "IMAGENAME eq mysqld.exe" 2>&1', $output);
                $mysql_running = false;
                foreach ($output as $line) {
                    if (stripos($line, 'mysqld.exe') !== false) {
                        $mysql_running = true;
                        break;
                    }
                }
                
                if ($mysql_running) {
                    echo "<div class='alert alert-success'><i class='fas fa-check'></i> MySQL process (mysqld.exe) is running</div>";
                } else {
                    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> MySQL process (mysqld.exe) is NOT running</div>";
                }
                ?>
                
                <hr>
                
                <h4>Solutions if MySQL is not running:</h4>
                <div class="alert alert-info">
                    <strong>Option 1: Start from XAMPP Control Panel</strong>
                    <ol>
                        <li>Open XAMPP Control Panel</li>
                        <li>Find MySQL in the list</li>
                        <li>Click "Start" button</li>
                        <li>Wait for it to turn green</li>
                    </ol>
                </div>
                
                <div class="alert alert-secondary">
                    <strong>Option 2: Check for port conflicts</strong>
                    <p>If MySQL won't start, another program might be using port 3306. Try:</p>
                    <ul>
                        <li>Stop other MySQL installations</li>
                        <li>Change MySQL port in XAMPP config (my.ini)</li>
                        <li>Restart XAMPP</li>
                    </ul>
                </div>
                
                <div class="alert alert-warning">
                    <strong>Option 3: Check XAMPP logs</strong>
                    <p>Look at the XAMPP Control Panel log for MySQL errors. Common issues:</p>
                    <ul>
                        <li>InnoDB corruption</li>
                        <li>Missing data directory</li>
                        <li>Permission issues</li>
                    </ul>
                </div>
                
                <hr>
                <div class="mt-4">
                    <a href="setup_database.php" class="btn btn-success btn-lg">Run Database Setup (if MySQL is running)</a>
                    <a href="check_mysql.php" class="btn btn-secondary">Back to Instructions</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
</body>
</html>