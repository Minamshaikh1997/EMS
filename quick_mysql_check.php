<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick MySQL Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h2><i class="fas fa-exclamation-circle"></i> MySQL Problem Detected</h2>
            </div>
            <div class="card-body">
                <div class="alert alert-danger">
                    <h4>The page is loading/hanging because MySQL is not responding.</h4>
                    <p><strong>This confirms MySQL is NOT running properly.</strong></p>
                </div>

                <h4>Immediate Steps to Fix:</h4>
                
                <div class="alert alert-info">
                    <strong>Step 1: Close the hanging tab</strong><br>
                    Close the browser tab that's loading to free up resources.
                </div>

                <div class="alert alert-warning">
                    <strong>Step 2: Open XAMPP Control Panel</strong><br>
                    1. Look for XAMPP in Windows Start Menu<br>
                    2. Open "XAMPP Control Panel"<br>
                    3. Look at the MySQL row - what does it show?
                </div>

                <div class="alert alert-secondary">
                    <strong>Step 3: Check MySQL Status</strong><br>
                    In XAMPP Control Panel, look for MySQL:<br>
                    - <strong>Red "Start" button</strong> = MySQL is stopped<br>
                    - <strong>Green "Stop" button</strong> = MySQL is running<br>
                    - <strong>Yellow/Orange</strong> = MySQL is starting/trying to start<br>
                    - <strong>Error message in log</strong> = There's a problem
                </div>

                <div class="alert alert-success">
                    <strong>Step 4: Start MySQL</strong><br>
                    If MySQL is not running:<br>
                    1. Click the <strong>"Start"</strong> button next to MySQL<br>
                    2. Wait 10-30 seconds<br>
                    3. It should turn green<br>
                    4. If it shows an error, write down the error message
                </div>

                <hr>
                
                <h4>Common MySQL Problems in XAMPP:</h4>
                
                <div class="card mb-3">
                    <div class="card-header">
                        <strong>Problem 1: Port 3306 already in use</strong>
                    </div>
                    <div class="card-body">
                        <p><strong>Solution:</strong></p>
                        <ol>
                            <li>In XAMPP, click "Config" next to MySQL</li>
                            <li>Open "my.ini"</li>
                            <li>Find "port=3306" and change to "port=3307"</li>
                            <li>Save and restart MySQL</li>
                        </ol>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header">
                        <strong>Problem 2: MySQL data directory corrupted</strong>
                    </div>
                    <div class="card-body">
                        <p><strong>Solution:</strong></p>
                        <ol>
                            <li>Stop MySQL in XAMPP</li>
                            <li>Go to C:\xampp\mysql\data</li>
                            <li>Rename "data" folder to "data_old"</li>
                            <li>Copy "backup" folder and rename it to "data"</li>
                            <li>Copy ibdata1 from data_old to new data folder</li>
                            <li>Start MySQL again</li>
                        </ol>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header">
                        <strong>Problem 3: MySQL service not installed</strong>
                    </div>
                    <div class="card-body">
                        <p><strong>Solution:</strong></p>
                        <ol>
                            <li>In XAMPP Control Panel, click "Shell"</li>
                            <li>Type: <code>mysql\install_service.exe</code></li>
                            <li>Press Enter</li>
                            <li>Try starting MySQL again</li>
                        </ol>
                    </div>
                </div>

                <hr>
                
                <h4>After MySQL is Running:</h4>
                <div class="alert alert-success">
                    <p>Once MySQL starts successfully (green button):</p>
                    <ol>
                        <li>Go to: <a href="http://localhost/phpmyadmin" target="_blank">http://localhost/phpmyadmin</a></li>
                        <li>If phpMyAdmin opens, MySQL is working!</li>
                        <li>Then go to: <a href="setup_database.php">http://localhost/EMS/setup_database.php</a></li>
                        <li>This will create the database and users</li>
                        <li>Then login at: <a href="login.php">http://localhost/EMS/login.php</a></li>
                    </ol>
                </div>

                <div class="mt-4">
                    <a href="login.php" class="btn btn-primary btn-lg">Try Login Page</a>
                    <a href="setup_database.php" class="btn btn-success btn-lg">Setup Database</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
</body>
</html>