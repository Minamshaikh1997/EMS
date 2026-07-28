<?php
/**
 * Permission Management System
 * Provides functions for checking and managing role-based permissions
 * 
 * @package EMS
 * @version 1.0
 */

// Prevent direct access
if (!defined('EMS_ROOT')) {
    define('EMS_ROOT', dirname(__DIR__));
}

/**
 * Check if current user has specific permission on a module
 * 
 * @param mysqli $conn Database connection
 * @param string $module Module name (e.g., 'employee_management', 'attendance')
 * @param string $permission Permission slug (e.g., 'view', 'create', 'edit', 'delete', 'approve', 'export')
 * @return bool True if user has permission, false otherwise
 */
function hasPermission($conn, $module, $permission = 'view') {
    // Super Admin always has all permissions
    if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'Super Admin') {
        return true;
    }

    // Get current user's role
    $admin_id = $_SESSION['admin_id'] ?? 0;
    $admin_email = $_SESSION['admin'] ?? '';
    
    if (!$admin_id && !$admin_email) {
        return false;
    }

    // Get role ID
    $role_id = null;
    if ($admin_id > 0) {
        $stmt = $conn->prepare("SELECT role FROM admin WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $role_name = $row['role'];
        }
        $stmt->close();
    } else {
        $stmt = $conn->prepare("SELECT role FROM admin WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $admin_email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $role_name = $row['role'];
        }
        $stmt->close();
    }

    if (!isset($role_name) || empty($role_name)) {
        return false;
    }

    // Get role ID from roles table
    $stmt = $conn->prepare("SELECT id FROM roles WHERE role_name = ? AND is_active = 1");
    $stmt->bind_param("s", $role_name);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $role_id = $row['id'];
    }
    $stmt->close();

    if (!$role_id) {
        return false;
    }

    // Get permission ID
    $permission_id = null;
    $stmt = $conn->prepare("SELECT id FROM permissions WHERE permission_slug = ?");
    $stmt->bind_param("s", $permission);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $permission_id = $row['id'];
    }
    $stmt->close();

    if (!$permission_id) {
        return false;
    }

    // Check if permission exists
    $stmt = $conn->prepare("
        SELECT id FROM role_permissions 
        WHERE role_id = ? AND permission_id = ? AND module_name = ?
        LIMIT 1
    ");
    $stmt->bind_param("iis", $role_id, $permission_id, $module);
    $stmt->execute();
    $result = $stmt->get_result();
    $has_perm = $result->num_rows > 0;
    $stmt->close();

    return $has_perm;
}

/**
 * Check if user has any of the specified permissions
 * 
 * @param mysqli $conn Database connection
 * @param string $module Module name
 * @param array $permissions Array of permission slugs
 * @return bool True if user has at least one permission
 */
function hasAnyPermission($conn, $module, $permissions = ['view']) {
    foreach ($permissions as $permission) {
        if (hasPermission($conn, $module, $permission)) {
            return true;
        }
    }
    return false;
}

/**
 * Check if user has all of the specified permissions
 * 
 * @param mysqli $conn Database connection
 * @param string $module Module name
 * @param array $permissions Array of permission slugs
 * @return bool True if user has all permissions
 */
function hasAllPermissions($conn, $module, $permissions = ['view']) {
    foreach ($permissions as $permission) {
        if (!hasPermission($conn, $module, $permission)) {
            return false;
        }
    }
    return true;
}

/**
 * Require specific permission - redirects to access denied if not authorized
 * 
 * @param mysqli $conn Database connection
 * @param string $module Module name
 * @param string $permission Permission slug (default: 'view')
 */
function requirePermission($conn, $module, $permission = 'view') {
    if (!hasPermission($conn, $module, $permission)) {
        redirectToAccessDenied();
        exit();
    }
}

/**
 * Require any of the specified permissions
 * 
 * @param mysqli $conn Database connection
 * @param string $module Module name
 * @param array $permissions Array of permission slugs
 */
function requireAnyPermission($conn, $module, $permissions = ['view']) {
    if (!hasAnyPermission($conn, $module, $permissions)) {
        redirectToAccessDenied();
        exit();
    }
}

/**
 * Redirect to access denied page
 */
function redirectToAccessDenied() {
    $_SESSION['access_denied_message'] = "Access Denied - You don't have permission to access this page.";
    header("Location: " . EMS_ROOT . "/admin/access_denied.php");
    exit();
}

/**
 * Get all permissions for current user on a module
 * 
 * @param mysqli $conn Database connection
 * @param string $module Module name
 * @return array Array of permission slugs
 */
function getUserPermissions($conn, $module) {
    $permissions = [];
    
    // Super Admin gets all permissions
    if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'Super Admin') {
        return ['view', 'create', 'edit', 'delete', 'approve', 'export'];
    }

    $admin_id = $_SESSION['admin_id'] ?? 0;
    $admin_email = $_SESSION['admin'] ?? '';
    
    if (!$admin_id && !$admin_email) {
        return [];
    }

    // Get role ID
    $role_id = null;
    if ($admin_id > 0) {
        $stmt = $conn->prepare("SELECT role FROM admin WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $admin_id);
    } else {
        $stmt = $conn->prepare("SELECT role FROM admin WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $admin_email);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $role_name = $row['role'];
    }
    $stmt->close();

    if (!isset($role_name)) {
        return [];
    }

    $stmt = $conn->prepare("SELECT id FROM roles WHERE role_name = ? AND is_active = 1");
    $stmt->bind_param("s", $role_name);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $role_id = $row['id'];
    }
    $stmt->close();

    if (!$role_id) {
        return [];
    }

    // Get all permissions for this role and module
    $stmt = $conn->prepare("
        SELECT p.permission_slug
        FROM role_permissions rp
        JOIN permissions p ON rp.permission_id = p.id
        WHERE rp.role_id = ? AND rp.module_name = ?
    ");
    $stmt->bind_param("is", $role_id, $module);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $permissions[] = $row['permission_slug'];
    }
    $stmt->close();

    return $permissions;
}

/**
 * Get all roles from database
 * 
 * @param mysqli $conn Database connection
 * @param bool $active_only Only return active roles
 * @return array Array of roles
 */
function getAllRoles($conn, $active_only = true) {
    $roles = [];
    $query = "SELECT id, role_name, role_slug, description, hierarchy_level, is_active 
              FROM roles";
    
    if ($active_only) {
        $query .= " WHERE is_active = 1";
    }
    $query .= " ORDER BY hierarchy_level ASC";
    
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $roles[] = $row;
        }
    }
    return $roles;
}

/**
 * Get all permissions from database
 * 
 * @param mysqli $conn Database connection
 * @return array Array of permissions
 */
function getAllPermissions($conn) {
    $permissions = [];
    $result = mysqli_query($conn, "SELECT id, permission_name, permission_slug, description 
                                    FROM permissions 
                                    ORDER BY id ASC");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $permissions[] = $row;
        }
    }
    return $permissions;
}

/**
 * Get all modules
 * 
 * @return array Array of modules with names
 */
function getAllModules() {
    return [
        'dashboard' => 'Dashboard',
        'employee_management' => 'Employee Management',
        'attendance' => 'Attendance',
        'leave_management' => 'Leave Management',
        'payroll' => 'Payroll',
        'department' => 'Department',
        'reports' => 'Reports',
        'notifications' => 'Notifications',
        'settings' => 'Settings',
        'user_management' => 'User Management',
        'role_permission' => 'Role & Permission'
    ];
}

/**
 * Get permissions for a specific role
 * 
 * @param mysqli $conn Database connection
 * @param int $role_id Role ID
 * @return array Array of permissions grouped by module
 */
function getRolePermissions($conn, $role_id) {
    $permissions = [];
    $result = mysqli_query($conn, "
        SELECT rp.module_name, p.permission_slug
        FROM role_permissions rp
        JOIN permissions p ON rp.permission_id = p.id
        WHERE rp.role_id = '$role_id'
        ORDER BY rp.module_name, p.permission_slug
    ");
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $permissions[$row['module_name']][] = $row['permission_slug'];
        }
    }
    return $permissions;
}

/**
 * Save permissions for a role
 * 
 * @param mysqli $conn Database connection
 * @param int $role_id Role ID
 * @param array $permissions Array of [module => [permission_slugs]]
 * @return bool True on success, false on failure
 */
function saveRolePermissions($conn, $role_id, $permissions) {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Delete existing permissions for this role
        $stmt = $conn->prepare("DELETE FROM role_permissions WHERE role_id = ?");
        $stmt->bind_param("i", $role_id);
        $stmt->execute();
        $stmt->close();
        
        // Insert new permissions
        $stmt = $conn->prepare("
            INSERT INTO role_permissions (role_id, permission_id, module_name)
            SELECT ?, p.id, ?
            FROM permissions p
            WHERE p.permission_slug = ?
        ");
        
        foreach ($permissions as $module => $perms) {
            foreach ($perms as $permission_slug) {
                $stmt->bind_param("iss", $role_id, $module, $permission_slug);
                $stmt->execute();
            }
        }
        
        $stmt->close();
        
        // Commit transaction
        mysqli_commit($conn);
        return true;
        
    } catch (Exception $e) {
        // Rollback on error
        mysqli_rollback($conn);
        return false;
    }
}

/**
 * Check if current user is Super Admin
 * 
 * @return bool True if Super Admin, false otherwise
 */
function isSuperAdmin() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'Super Admin';
}

/**
 * Check if current user is Admin or higher
 * 
 * @return bool True if Admin or Super Admin, false otherwise
 */
function isAdminOrHigher() {
    if (!isset($_SESSION['admin_role'])) {
        return false;
    }
    return in_array($_SESSION['admin_role'], ['Super Admin', 'Admin']);
}

/**
 * Get current user's role
 * 
 * @param mysqli $conn Database connection
 * @return string Role name
 */
function getCurrentUserRole($conn) {
    return $_SESSION['admin_role'] ?? 'Admin';
}

/**
 * Log permission access (for audit trail)
 * 
 * @param mysqli $conn Database connection
 * @param string $module Module name
 * @param string $permission Permission slug
 * @param bool $granted Whether access was granted or denied
 */
function logPermissionAccess($conn, $module, $permission, $granted = true) {
    $admin_id = $_SESSION['admin_id'] ?? 0;
    $admin_email = $_SESSION['admin'] ?? '';
    $role = $_SESSION['admin_role'] ?? 'Unknown';
    
    $admin_id_escaped = intval($admin_id);
    $module_escaped = mysqli_real_escape_string($conn, $module);
    $permission_escaped = mysqli_real_escape_string($conn, $permission);
    $role_escaped = mysqli_real_escape_string($conn, $role);
    $granted_int = $granted ? 1 : 0;
    
    mysqli_query($conn, "
        INSERT INTO permission_logs (admin_id, admin_email, role, module, permission, granted, ip_address, user_agent)
        VALUES ('$admin_id_escaped', '$admin_email', '$role_escaped', '$module_escaped', '$permission_escaped', '$granted_int', 
                '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}')
    ");
}

?>