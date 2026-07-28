<?php
session_start();
include("admincheck_role.php");
include("../config/db.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - EMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .access-denied-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 50px 40px;
            max-width: 600px;
            width: 90%;
            text-align: center;
            animation: slideIn 0.5s ease-out;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .error-icon {
            font-size: 80px;
            color: #dc3545;
            margin-bottom: 20px;
            animation: shake 0.5s ease-in-out;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        .error-title {
            font-size: 32px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 15px;
        }
        .error-message {
            font-size: 16px;
            color: #64748b;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .user-info {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid #dc3545;
        }
        .user-info-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .user-info-value {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 10px;
        }
        .btn-home {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 14px 40px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .help-text {
            margin-top: 25px;
            font-size: 13px;
            color: #94a3b8;
        }
        .help-text i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="access-denied-container">
        <div class="error-icon">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
        
        <h1 class="error-title">Access Denied</h1>
        
        <p class="error-message">
            <?php echo htmlspecialchars($_SESSION['access_denied_message'] ?? "You don't have permission to access this page."); ?>
        </p>
        
        <div class="user-info">
            <div class="row">
                <div class="col-md-6">
                    <div class="user-info-label">Logged in as</div>
                    <div class="user-info-value">
                        <i class="fa fa-user me-2"></i><?php echo htmlspecialchars($admin_name); ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="user-info-label">Your Role</div>
                    <div class="user-info-value">
                        <i class="fa fa-user-shield me-2"></i><?php echo htmlspecialchars($admin_role); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <a href="dashboard.php" class="btn-home">
            <i class="fa fa-home"></i> Back to Dashboard
        </a>
        
        <div class="help-text">
            <i class="fa fa-info-circle"></i>
            If you believe this is an error, please contact your system administrator.
        </div>
    </div>
    
    <script>
        // Auto redirect to dashboard after 10 seconds
        setTimeout(function() {
            window.location.href = 'dashboard.php';
        }, 10000);
    </script>
</body>
</html>
<?php
// Clear the message after displaying
unset($_SESSION['access_denied_message']);
?>