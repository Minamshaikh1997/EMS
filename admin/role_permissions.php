<?php
session_start();
include("admincheck_role.php");
include("../config/db.php");
include_once("../config/permissions.php");

// Only Super Admin can modify permissions
if (!isSuperAdmin()) {
    header("Location: dashboard.php");
    exit();
}

// Save permissions
if (isset($_POST['save_permissions'])) {
    ems_verify_csrf();
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Get all roles
        $roles = getAllRoles($conn);
        $permissions = getAllPermissions($conn);
        $modules = getAllModules();
        
        // Clear all existing permissions
        mysqli_query($conn, "DELETE FROM role_permissions");
        
        // Insert new permissions
        $stmt = $conn->prepare("
            INSERT INTO role_permissions (role_id, permission_id, module_name)
            SELECT ?, p.id, ?
            FROM permissions p
            WHERE p.permission_slug = ?
        ");
        
        $total_inserted = 0;
        
        foreach ($roles as $role) {
            $role_id = $role['id'];
            $role_slug = strtolower(str_replace(' ', '_', $role['role_name']));
            
            foreach ($modules as $module_key => $module_name) {
                foreach ($permissions as $permission) {
                    $perm_slug = $permission['permission_slug'];
                    $checkbox_name = "perm[{$role_slug}][{$module_key}][{$perm_slug}]";
                    
                    if (isset($_POST['perm'][$role_slug][$module_key][$perm_slug])) {
                        $stmt->bind_param("iss", $role_id, $module_key, $perm_slug);
                        $stmt->execute();
                        $total_inserted++;
                    }
                }
            }
        }
        
        $stmt->close();
        
        // Commit transaction
        mysqli_commit($conn);
        
        $message = "✅ Permissions saved successfully! ($total_inserted permissions configured)";
        
        // Log activity
        logPermissionAccess($conn, 'role_permission', 'edit', true);
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $message = "❌ Error saving permissions: " . $e->getMessage();
    }
}

// Generate CSRF token
$csrf_token = ems_csrf_token();

// Get all roles from database
$allRoles = getAllRoles($conn);

// Get all modules
$allModules = getAllModules();

// Get all permissions
$allPermissions = getAllPermissions($conn);

// Get saved permissions
$savedPerms = [];
$result = mysqli_query($conn, "
    SELECT r.role_name, rp.module_name, p.permission_slug
    FROM role_permissions rp
    JOIN roles r ON rp.role_id = r.id
    JOIN permissions p ON rp.permission_id = p.id
");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $role_slug = strtolower(str_replace(' ', '_', $row['role_name']));
        $savedPerms[$role_slug][$row['module_name']][$row['permission_slug']] = true;
    }
}

// Group modules by category
$module_categories = [
    'Core' => ['dashboard', 'employee_management'],
    'Operations' => ['attendance', 'leave_management'],
    'Finance' => ['payroll'],
    'Administration' => ['department', 'user_management', 'role_permission'],
    'Analytics' => ['reports'],
    'Communication' => ['notifications'],
    'System' => ['settings']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role & Permission Management - EMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            background: #f4f7fc; 
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }
        .container-fluid { padding: 30px; }
        
        .page-header {
            background: linear-gradient(135deg, #1e293b, #334155);
            color: white;
            padding: 25px 30px;
            border-radius: 16px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 20px rgba(0,0,0,.1);
        }
        .page-header h2 { 
            margin: 0; 
            font-weight: 700; 
            font-size: 28px;
        }
        .page-header h2 i { 
            margin-right: 12px; 
            color: #60a5fa; 
        }
        .page-header p {
            margin: 8px 0 0 0;
            color: #94a3b8;
            font-size: 14px;
        }
        
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,.08);
            overflow: hidden;
            margin-bottom: 20px;
        }
        .card-header {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: white;
            padding: 18px 25px;
            font-weight: 600;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-header i { margin-right: 10px; }
        
        .category-header {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .category-header i {
            margin-right: 8px;
        }
        
        .table th {
            background: #f8fafc;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            padding: 15px 10px;
            border-bottom: 2px solid #e2e8f0;
            vertical-align: middle;
            text-align: center;
            white-space: nowrap;
        }
        .table td {
            padding: 12px 10px;
            vertical-align: middle;
            text-align: center;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }
        .table td:first-child { 
            text-align: left; 
            font-weight: 600;
            color: #1e293b;
            min-width: 200px;
        }
        .table tbody tr:hover { 
            background: #f8fafc; 
            transition: all 0.2s ease;
        }
        
        .role-header {
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 4px;
        }
        
        .form-check-input {
            width: 20px;
            height: 20px;
            cursor: pointer;
            border-radius: 4px;
            border: 2px solid #cbd5e1;
            transition: all 0.2s ease;
        }
        .form-check-input:checked {
            background-color: #2563eb;
            border-color: #2563eb;
            transform: scale(1.1);
        }
        .form-check-input:focus {
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            border-color: #2563eb;
        }
        .form-check-input:hover {
            border-color: #2563eb;
            transform: scale(1.15);
        }
        
        .level-badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 4px;
        }
        .level-1 { background: #fee2e2; color: #dc2626; }
        .level-2 { background: #fef3c7; color: #d97706; }
        .level-3 { background: #dbeafe; color: #2563eb; }
        .level-4 { background: #e0e7ff; color: #4f46e5; }
        .level-5 { background: #f3e8ff; color: #7c3aed; }
        .level-6 { background: #f1f5f9; color: #64748b; }
        
        .btn-save {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            padding: 14px 50px;
            font-weight: 600;
            border-radius: 12px;
            font-size: 16px;
            color: white;
            transition: all 0.3s ease;
        }
        .btn-save:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 25px rgba(16,185,129,.4);
            color: white;
        }
        
        .btn-reset {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border: none;
            padding: 14px 40px;
            font-weight: 600;
            border-radius: 12px;
            font-size: 16px;
            color: white;
            transition: all 0.3s ease;
        }
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(239, 68, 68, .4);
            color: white;
        }
        
        .module-name {
            font-weight: 600;
            color: #1e293b;
        }
        .module-icon {
            margin-right: 8px;
            color: #6366f1;
            width: 18px;
            text-align: center;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
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
        .stat-card .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            margin: 8px 0 4px 0;
        }
        .stat-card .stat-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        .stat-card .stat-icon {
            font-size: 24px;
            color: #2563eb;
        }
        
        .info-box {
            background: #e0e7ff;
            border-left: 4px solid #4f46e5;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .info-box i {
            color: #4f46e5;
            margin-right: 8px;
        }
        
        .table-responsive {
            max-height: 600px;
            overflow-y: auto;
        }
        
        .sticky-header {
            position: sticky;
            top: 0;
            background: #f8fafc;
            z-index: 10;
        }
        
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            .stats-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="container-fluid">

    <!-- Header -->
    <div class="page-header">
        <div>
            <h2><i class="fa-solid fa-shield-halved"></i> Role & Permission Management</h2>
            <p class="mb-0">
                <i class="fa fa-user-shield me-1"></i> 
                <strong><?= htmlspecialchars($admin_name) ?></strong> 
                <span class="badge bg-primary ms-1"><?= htmlspecialchars($admin_role) ?></span>
                <span class="badge bg-secondary ms-1">ID: <?= intval($_SESSION['admin_id'] ?? 0) ?></span>
                — Configure access controls for all roles
            </p>
        </div>
        <a href="dashboard.php" class="btn btn-light rounded-pill px-4">
            <i class="fa fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <?php if (isset($message)): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 px-4" role="alert">
            <i class="fa fa-check-circle me-2"></i> <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Info Box -->
    <div class="info-box">
        <i class="fa-solid fa-circle-info"></i>
        <strong>Instructions:</strong> 
        Tick the checkboxes to grant permissions to each role. 
        Super Admin automatically has access to all modules. 
        Changes take effect immediately after saving.
    </div>

    <!-- Statistics Cards -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
            <div class="stat-label">Total Roles</div>
            <div class="stat-value"><?= count($allRoles) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-key"></i></div>
            <div class="stat-label">Permissions</div>
            <div class="stat-value"><?= count($allPermissions) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-cubes"></i></div>
            <div class="stat-label">Modules</div>
            <div class="stat-value"><?= count($allModules) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-check-double"></i></div>
            <div class="stat-label">Total Permissions</div>
            <div class="stat-value"><?= count($allRoles) * count($allModules) * count($allPermissions) ?></div>
        </div>
    </div>

    <!-- Permissions Card -->
    <div class="card">
        <div class="card-header">
            <div>
                <i class="fa fa-check-double"></i> Permission Matrix
                <small style="opacity: 0.9; margin-left: 10px;">Configure role-based access control</small>
            </div>
            <div>
                <button type="button" class="btn btn-light btn-sm me-2" onclick="selectAll()">
                    <i class="fa fa-check-double"></i> Select All
                </button>
                <button type="button" class="btn btn-warning btn-sm" onclick="deselectAll()">
                    <i class="fa fa-times"></i> Clear All
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <form method="POST" id="permissionsForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead class="sticky-header">
                            <tr>
                                <th style="min-width: 220px; text-align: left;">
                                    <i class="fa fa-cube me-2"></i>Module / Feature
                                </th>
                                <?php foreach ($allRoles as $role): 
                                    $level = $role['hierarchy_level'];
                                    $role_slug = strtolower(str_replace(' ', '_', $role['role_name']));
                                ?>
                                    <th style="min-width: 140px;">
                                        <div class="role-header"><?= htmlspecialchars($role['role_name']) ?></div>
                                        <span class="level-badge level-<?= $level ?>">Level <?= $level ?></span>
                                        <br>
                                        <small class="text-muted" style="font-size: 10px;">
                                            <?= htmlspecialchars($role['role_slug']) ?>
                                        </small>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                            <tr>
                                <th colspan="<?= count($allRoles) + 1 ?>" style="background: #e0e7ff; color: #4f46e5; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">
                                    <i class="fa fa-key me-2"></i>Permissions: 
                                    <?php foreach ($allPermissions as $perm): ?>
                                        <span class="badge bg-primary me-1"><?= ucfirst($perm['permission_slug']) ?></span>
                                    <?php endforeach; ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            foreach ($module_categories as $category => $modules):
                            ?>
                                <tr class="category-header">
                                    <td colspan="<?= count($allRoles) + 1 ?>">
                                        <i class="fa fa-folder-open"></i> <?= $category ?>
                                    </td>
                                </tr>
                                <?php 
                                foreach ($modules as $module_key):
                                    if (!isset($allModules[$module_key])) continue;
                                    $module_name = $allModules[$module_key];
                                    
                                    // Get module icon
                                    $module_icons = [
                                        'dashboard' => 'fa-gauge-high',
                                        'employee_management' => 'fa-users',
                                        'attendance' => 'fa-clock',
                                        'leave_management' => 'fa-calendar-check',
                                        'payroll' => 'fa-money-bill-wave',
                                        'department' => 'fa-building',
                                        'reports' => 'fa-chart-column',
                                        'notifications' => 'fa-bell',
                                        'settings' => 'fa-gear',
                                        'user_management' => 'fa-user-shield',
                                        'role_permission' => 'fa-key'
                                    ];
                                    $icon = $module_icons[$module_key] ?? 'fa-cube';
                                ?>
                                    <tr>
                                        <td style="text-align: left; padding-left: 25px;">
                                            <i class="fa-solid <?= $icon ?> module-icon"></i>
                                            <?= htmlspecialchars($module_name) ?>
                                        </td>
                                        <?php foreach ($allRoles as $role): 
                                            $role_slug = strtolower(str_replace(' ', '_', $role['role_name']));
                                        ?>
                                            <td>
                                                <div class="d-flex flex-wrap gap-1 justify-content-center">
                                                    <?php foreach ($allPermissions as $perm): 
                                                        $perm_slug = $perm['permission_slug'];
                                                        $checked = isset($savedPerms[$role_slug][$module_key][$perm_slug]) ? 'checked' : '';
                                                        $checkbox_name = "perm[{$role_slug}][{$module_key}][{$perm_slug}]";
                                                        
                                                        // Color code permissions
                                                        $perm_colors = [
                                                            'view' => 'primary',
                                                            'create' => 'success',
                                                            'edit' => 'warning',
                                                            'delete' => 'danger',
                                                            'approve' => 'info',
                                                            'export' => 'secondary'
                                                        ];
                                                        $color = $perm_colors[$perm_slug] ?? 'primary';
                                                    ?>
                                                        <input class="form-check-input" type="checkbox" 
                                                               name="<?= $checkbox_name ?>" 
                                                               value="1" 
                                                               <?= $checked ?>
                                                               title="<?= ucfirst($perm_slug) ?>"
                                                               style="border-color: var(--bs-<?= $color ?>);">
                                                    <?php endforeach; ?>
                                                </div>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; 
                            endforeach; 
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="p-4 text-center bg-light border-top">
                    <button type="submit" name="save_permissions" class="btn btn-save btn-lg me-3">
                        <i class="fa fa-floppy-disk me-2"></i> Save Permissions
                    </button>
                    <button type="reset" class="btn btn-reset btn-lg">
                        <i class="fa fa-rotate-left me-2"></i> Reset Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Legend -->
    <div class="card mt-4">
        <div class="card-header" style="background: linear-gradient(135deg, #64748b, #475569);">
            <i class="fa fa-circle-info"></i> Permission Legend
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="mb-3"><i class="fa fa-key me-2"></i>Permission Types:</h6>
                    <div class="d-flex flex-wrap gap-3">
                        <div>
                            <span class="badge bg-primary">View</span>
                            <small class="text-muted ms-1">- View/Read data</small>
                        </div>
                        <div>
                            <span class="badge bg-success">Create</span>
                            <small class="text-muted ms-1">- Add new records</small>
                        </div>
                        <div>
                            <span class="badge bg-warning">Edit</span>
                            <small class="text-muted ms-1">- Modify existing data</small>
                        </div>
                        <div>
                            <span class="badge bg-danger">Delete</span>
                            <small class="text-muted ms-1">- Remove records</small>
                        </div>
                        <div>
                            <span class="badge bg-info">Approve</span>
                            <small class="text-muted ms-1">- Approve requests</small>
                        </div>
                        <div>
                            <span class="badge bg-secondary">Export</span>
                            <small class="text-muted ms-1">- Export to Excel/PDF</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="mb-3"><i class="fa fa-layer-group me-2"></i>Role Hierarchy:</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($allRoles as $role): 
                            $level = $role['hierarchy_level'];
                        ?>
                            <span class="level-badge level-<?= $level ?>">
                                Lv<?= $level ?> <?= htmlspecialchars($role['role_name']) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-4 text-muted" style="font-size: 13px;">
        <i class="fa fa-info-circle me-1"></i> 
        Only <strong>Super Admin</strong> can manage permissions. 
        Changes are logged for security audit.
    </div>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Select all checkboxes
    function selectAll() {
        if (confirm('Are you sure you want to grant ALL permissions to ALL roles? This may create security risks.')) {
            document.querySelectorAll('#permissionsForm input[type="checkbox"]').forEach(function(checkbox) {
                checkbox.checked = true;
            });
        }
    }

    // Deselect all checkboxes
    function deselectAll() {
        if (confirm('Are you sure you want to remove ALL permissions from ALL roles? Users will lose access to all modules.')) {
            document.querySelectorAll('#permissionsForm input[type="checkbox"]').forEach(function(checkbox) {
                checkbox.checked = false;
            });
        }
    }

    // Confirm before leaving page if form is modified
    let formModified = false;
    document.querySelectorAll('#permissionsForm input[type="checkbox"]').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            formModified = true;
        });
    });

    window.addEventListener('beforeunload', function(e) {
        if (formModified) {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            return e.returnValue;
        }
    });

    // Reset formModified on submit
    document.getElementById('permissionsForm').addEventListener('submit', function() {
        formModified = false;
    });

    // Highlight row on hover
    document.querySelectorAll('.table tbody tr').forEach(function(row) {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(5px)';
            this.style.transition = 'all 0.2s ease';
        });
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });
</script>

</body>
</html>
<?php
// Close database connection
mysqli_close($conn);
?>
