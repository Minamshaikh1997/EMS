<?php
/**
 * Permission System Test Page
 * 
 * This page allows you to test the permission system.
 * Only Super Admin can access this page.
 * 
 * @package EMS
 * @version 1.0
 */

session_start();
include("admincheck_role.php");
include("../config/db.php");
include("../config/permissions.php");

// Only allow Super Admin to test
if (!isSuperAdmin()) {
    header("Location: dashboard.php");
    exit();
}

// Get all modules and permissions
$modules = getAllModules();
$permissions = getAllPermissions();
$user_role = getCurrentUserRole($conn);

// Handle AJAX request for permission check
if (isset($_POST['ajax_check'])) {
    header('Content-Type: application/json');
    $module = $_POST['module'] ?? '';
    $permission = $_POST['permission'] ?? 'view';
    
    $has_permission = hasPermission($conn, $module, $permission);
    $user_perms = getUserPermissions($conn, $module);
    
    echo json_encode([
        'has_permission' => $has_permission,
        'module' => $module,
        'permission' => $permission,
        'user_permissions' => $user_perms
    ]);
    exit();
}

// Get statistics
$stats = [];
$stats['total_roles'] = count(getAllRoles($conn));
$stats['total_permissions'] = count($permissions);
$stats['total_modules'] = count($modules);
$stats['total_combinations'] = $stats['total_roles'] * $stats['total_modules'] * $stats['total_permissions'];

// Count configured permissions
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM role_permissions");
$stats['configured_permissions'] = mysqli_fetch_assoc($result)['count'];

// Get role-wise permission count
$role_stats = [];
$result = mysqli_query($conn, "
    SELECT r.role_name, r.hierarchy_level, 
           COUNT(DISTINCT rp.module_name) as modules,
           COUNT(rp.id) as permissions
    FROM roles r
    LEFT JOIN role_permissions rp ON r.id = rp.role_id
    GROUP BY r.id, r.role_name, r.hierarchy_level
    ORDER BY r.hierarchy_level ASC
");
while ($row = mysqli_fetch_assoc($result)) {
    $role_stats[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permission System Test - EMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f7fc;
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }
        .container-fluid {
            padding: 30px;
        }
        .page-header {
            background: linear-gradient(135deg, #1e293b, #334155);
            color: white;
            padding: 25px 30px;
            border-radius: 16px;
            margin-bottom: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,.1);
        }
        .page-header h2 {
            margin: 0;
            font-weight: 700;
        }
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,.08);
            margin-bottom: 20px;
        }
        .card-header {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: white;
            padding: 18px 25px;
            font-weight: 600;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,.05);
            border-left: 4px solid #2563eb;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0,0,0,.1);
        }
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #1e293b;
            margin: 10px 0 5px 0;
        }
        .stat-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        .badge-success-custom {
            background: #10b981;
            color: white;
        }
        .badge-danger-custom {
            background: #ef4444;
            color: white;
        }
        .test-result {
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            font-weight: 600;
        }
        .test-result.success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        .test-result.error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        .permission-grid {
            display: grid;
            grid-template-columnons: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }
        .module-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,.05);
        }
        .module-card h6 {
            color: #1e293b;
            font-weight: 700;
            margin-bottom: 15px;
        }
        .permission-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin: 3px;
        }
    </style>
</head>
<body>

<div class="container-fluid">

    <!-- Header -->
    <div class="page-header">
        <div>
            <h2><i class="fa-solid fa-flask-vial me-2"></i>Permission System Test</h2>
            <p class="mb-0 mt-2">
                <i class="fa fa-user-shield me-1"></i>
                <strong><?= htmlspecialchars($admin_name) ?></strong>
                <span class="badge bg-primary ms-1"><?= htmlspecialchars($admin_role) ?></span>
                — Testing and verifying permission system
            </p>
        </div>
        <a href="dashboard.php" class="btn btn-light rounded-pill px-4">
            <i class="fa fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <!-- Statistics -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-label">Total Roles</div>
                <div class="stat-value"><?= $stats['total_roles'] ?></div>
                <i class="fa-solid fa-users text-primary"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-label">Permissions</div>
                <div class="stat-value"><?= $stats['total_permissions'] ?></div>
                <i class="fa-solid fa-key text-success"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-label">Modules</div>
                <div class="stat-value"><?= $stats['total_modules'] ?></div>
                <i class="fa-solid fa-cubes text-info"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-label">Configured</div>
                <div class="stat-value"><?= $stats['configured_permissions'] ?></div>
                <i class="fa-solid fa-check-double text-warning"></i>
            </div>
        </div>
    </div>

    <!-- User Info -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fa fa-user-circle me-2"></i>Current User Information
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Username:</strong> <?= htmlspecialchars($admin_name) ?></p>
                    <p><strong>Role:</strong> <?= htmlspecialchars($user_role) ?></p>
                    <p><strong>Is Super Admin:</strong> 
                        <?php if (isSuperAdmin()): ?>
                            <span class="badge badge-success-custom">YES - Full Access</span>
                        <?php else: ?>
                            <span class="badge badge-danger-custom">NO</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <p><strong>User ID:</strong> <?= intval($_SESSION['admin_id'] ?? 0) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($_SESSION['admin'] ?? 'N/A') ?></p>
                    <p><strong>Session Status:</strong> 
                        <span class="badge badge-success-custom">Active</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Role Statistics -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fa-solid fa-chart-bar me-2"></i>Role-wise Permission Statistics
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Level</th>
                            <th>Modules</th>
                            <th>Permissions</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($role_stats as $role): 
                            $has_perms = $role['permissions'] > 0;
                            $status_badge = $has_perms 
                                ? '<span class="badge badge-success-custom">Configured</span>' 
                                : '<span class="badge badge-danger-custom">No Permissions</span>';
                        ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($role['role_name']) ?></strong></td>
                                <td>Level <?= $role['hierarchy_level'] ?></td>
                                <td><?= $role['modules'] ?></td>
                                <td><?= $role['permissions'] ?></td>
                                <td><?= $status_badge ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Interactive Permission Tester -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fa-solid fa-vial me-2"></i>Interactive Permission Tester
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label"><strong>Select Module:</strong></label>
                    <select class="form-select" id="moduleSelect">
                        <?php foreach ($modules as $key => $name): ?>
                            <option value="<?= $key ?>"><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label"><strong>Select Permission:</strong></label>
                    <select class="form-select" id="permissionSelect">
                        <?php foreach ($permissions as $perm): ?>
                            <option value="<?= $perm['permission_slug'] ?>">
                                <?= ucfirst($perm['permission_slug']) ?> - <?= $perm['description'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button class="btn btn-primary w-100" onclick="checkPermission()">
                        <i class="fa fa-search"></i> Check
                    </button>
                </div>
            </div>
            
            <div id="testResult"></div>
        </div>
    </div>

    <!-- Current User Permissions Grid -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fa-solid fa-grid-2 me-2"></i>Your Permissions by Module
        </div>
        <div class="card-body">
            <div class="permission-grid">
                <?php foreach ($modules as $module_key => $module_name): 
                    $user_perms = getUserPermissions($conn, $module_key);
                ?>
                    <div class="module-card">
                        <h6>
                            <i class="fa-solid fa-cube me-2 text-primary"></i>
                            <?= htmlspecialchars($module_name) ?>
                        </h6>
                        <?php if (!empty($user_perms)): ?>
                            <?php foreach ($user_perms as $perm): 
                                $perm_colors = [
                                    'view' => 'primary',
                                    'create' => 'success',
                                    'edit' => 'warning',
                                    'delete' => 'danger',
                                    'approve' => 'info',
                                    'export' => 'secondary'
                                ];
                                $color = $perm_colors[$perm] ?? 'primary';
                            ?>
                                <span class="permission-badge bg-<?= $color ?>">
                                    <?= ucfirst($perm) ?>
                                </span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-muted">No permissions</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="card">
        <div class="card-header">
            <i class="fa-solid fa-link me-2"></i>Quick Links
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                <a href="role_permissions.php" class="btn btn-primary">
                    <i class="fa fa-key"></i> Manage Permissions
                </a>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fa fa-gauge"></i> Dashboard
                </a>
                <button class="btn btn-info" onclick="window.location.reload()">
                    <i class="fa fa-refresh"></i> Refresh Test
                </button>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function checkPermission() {
    const module = document.getElementById('moduleSelect').value;
    const permission = document.getElementById('permissionSelect').value;
    const resultDiv = document.getElementById('testResult');
    
    // Show loading
    resultDiv.innerHTML = '<div class="test-result"><i class="fa fa-spinner fa-spin"></i> Checking permission...</div>';
    
    // AJAX request
    const formData = new FormData();
    formData.append('ajax_check', '1');
    formData.append('module', module);
    formData.append('permission', permission);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const hasPermission = data.has_permission;
        const userPerms = data.user_permissions.join(', ') || 'None';
        
        resultDiv.innerHTML = `
            <div class="test-result ${hasPermission ? 'success' : 'error'}">
                <i class="fa fa-${hasPermission ? 'check-circle' : 'times-circle'} me-2"></i>
                <strong>Result:</strong> ${hasPermission ? 'ACCESS GRANTED' : 'ACCESS DENIED'}
                <br><br>
                <strong>Module:</strong> ${module}<br>
                <strong>Permission:</strong> ${permission}<br>
                <strong>Your Permissions:</strong> ${userPerms}
            </div>
        `;
    })
    .catch(error => {
        resultDiv.innerHTML = '<div class="test-result error"><i class="fa fa-exclamation-circle me-2"></i>Error checking permission</div>';
    });
}

// Auto-check on page load
document.addEventListener('DOMContentLoaded', function() {
    // Check dashboard permission by default
    document.getElementById('moduleSelect').value = 'dashboard';
    document.getElementById('permissionSelect').value = 'view';
    checkPermission();
});
</script>

</body>
</html>

<?php
mysqli_close($conn);
?>