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
            <div class="card-header bg-warning text-dark">
                <h2><i class="fas fa-exclamation-triangle"></i> MySQL Service Check</h2>
            </div>
            <div class="card-body">
                <h4>Problem Detected:</h4>
                <div class="alert alert-danger">
                    <strong>MySQL is NOT running!</strong><br>
                    The error "No connection could be made because the target machine actively refused it" means the MySQL service in XAMPP is not started.
                </div>

                <h4>Solution - Start MySQL Service:</h4>
                <div class="alert alert-info">
                    <strong>Follow these steps to start MySQL:</strong>
                    <ol>
                        <li>Open <strong>XAMPP Control Panel</strong> (search for "XAMPP" in Windows Start Menu)</li>
                        <li>Look for <strong>MySQL</strong> in the list of services</li>
                        <li>Click the <strong>"Start"</strong> button next to MySQL</li>
                        <li>Wait for it to turn green and show "Running"</li>
                        <li>Also make sure <strong>Apache</strong> is running (for the web server)</li>
                    </ol>
                </div>

                <h4>Alternative: Start via Command Line</h4>
                <div class="alert alert-secondary">
                    <p>You can also start MySQL using the XAMPP command line:</p>
                    <code>C:\xampp\mysql\bin\mysqld.exe</code>
                </div>

                <h4>Verify MySQL is Running:</h4>
                <div class="alert alert-success">
                    <p>After starting MySQL, test the connection:</p>
                    <a href="http://localhost/phpmyadmin" class="btn btn-primary" target="_blank">Open phpMyAdmin</a>
                    <a href="setup_database.php" class="btn btn-success">Run Database Setup</a>
                </div>

                <hr>
                <h4>Quick Checklist:</h4>
                <ul>
                    <li>☐ XAMPP Control Panel is open</li>
                    <li>☐ MySQL service is started (green)</li>
                    <li>☐ Apache service is started (green)</li>
                    <li>☐ phpMyAdmin opens successfully</li>
                    <li>☐ Database setup script runs without errors</li>
                </ul>

                <div class="mt-4">
                    <a href="setup_database.php" class="btn btn-success btn-lg">Run Database Setup (after starting MySQL)</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
</body>
</html>