<?php
/**
 * Roles Helper - Hierarchy-based access control
 * Include this after admincheck_role.php
 */

// Role hierarchy levels (lower = higher authority)
$ROLE_HIERARCHY = [
    'Super Admin'        => 1,
    'Admin'              => 2,
    'Operations Manager' => 3,
    'WFM Executive'      => 3,
    'Finance Manager'    => 3,
    'Accountant'         => 4,
    'Supervisor'         => 4,
    'Team Lead'          => 5,
    'Employee'           => 6,
];

$ROLE_DESCRIPTIONS = [
    'Super Admin'        => 'Full system access. Can manage all users, settings, and data.',
    'Admin'              => 'Managing Director. Manages departments, managers, employees. Cannot modify Super Admin.',
    'Operations Manager' => 'Manages Supervisors and Team Leads under assigned departments.',
    'WFM Executive'      => 'Workforce Management. Manages attendance, shifts, schedules.',
    'Finance Manager'    => 'Manages payroll and salary. Cannot manage users or attendance.',
    'Accountant'         => 'Assists with payroll processing.',
    'Supervisor'         => 'Manages Team Leads. Approves attendance adjustments and leave requests.',
    'Team Lead'          => 'Manages employees in their team. Approves daily attendance.',
    'Employee'           => 'Can view own profile, mark attendance, request leave, submit adjustments.',
];

// Get current admin's role from admin table
function getAdminRole($conn) {
    $admin_id = $_SESSION['admin_id'] ?? 0;
    $admin_email = $_SESSION['admin'] ?? '';
    
    if ($admin_id > 0) {
        $q = mysqli_query($conn, "SELECT role FROM admin WHERE id='$admin_id' LIMIT 1");
    } else {
        $q = mysqli_query($conn, "SELECT role FROM admin WHERE email='$admin_email' LIMIT 1");
    }
    
    if ($q && mysqli_num_rows($q) > 0) {
        $r = mysqli_fetch_assoc($q);
        return $r['role'] ?: 'Admin';
    }
    return 'Admin';
}

// Get admin role level
function getAdminLevel($conn) {
    global $ROLE_HIERARCHY;
    $role = getAdminRole($conn);
    return $ROLE_HIERARCHY[$role] ?? 99;
}

// Check if current admin can manage a specific role
function canManageRole($conn, $targetRole) {
    global $ROLE_HIERARCHY;
    $adminRole = getAdminRole($conn);
    $adminLevel = $ROLE_HIERARCHY[$adminRole] ?? 99;
    $targetLevel = $ROLE_HIERARCHY[$targetRole] ?? 99;
    
    // Same level cannot manage each other (except Super Admin)
    if ($adminLevel == $targetLevel && $adminRole != 'Super Admin') {
        return false;
    }
    
    // Higher level (lower number) can manage lower levels (higher number)
    return $adminLevel < $targetLevel;
}

// Get roles that current admin can manage
function getManageableRoles($conn) {
    global $ROLE_HIERARCHY;
    $adminRole = getAdminRole($conn);
    $adminLevel = $ROLE_HIERARCHY[$adminRole] ?? 99;
    
    $manageable = [];
    foreach ($ROLE_HIERARCHY as $role => $level) {
        if ($level > $adminLevel) {
            $manageable[] = $role;
        }
    }
    return $manageable;
}

// Filter employees query based on admin's role
function getAccessibleEmployeesQuery($conn, $baseQuery = "SELECT * FROM employees WHERE 1=1") {
    global $ROLE_HIERARCHY;
    $adminRole = getAdminRole($conn);
    $adminLevel = $ROLE_HIERARCHY[$adminRole] ?? 99;
    $admin_id = $_SESSION['admin_id'] ?? 0;
    
    switch ($adminRole) {
        case 'Super Admin':
            // Can see all employees
            return $baseQuery;
            
        case 'Admin':
            // Can see all employees except Super Admin accounts
            return $baseQuery;
            
        case 'Operations Manager':
            // Can see employees under their departments
            // Get admin's departments
            $deptQ = mysqli_query($conn, "SELECT GROUP_CONCAT(DISTINCT department) as depts FROM employees WHERE reporting_manager_id='$admin_id'");
            $deptRow = mysqli_fetch_assoc($deptQ);
            if ($deptRow && $deptRow['depts']) {
                $depts = explode(',', $deptRow['depts']);
                $deptConditions = [];
                foreach ($depts as $d) {
                    $deptConditions[] = "department='" . mysqli_real_escape_string($conn, trim($d)) . "'";
                }
                return $baseQuery . " AND (" . implode(' OR ', $deptConditions) . ")";
            }
            return $baseQuery . " AND 1=0"; // No employees if no departments
            
        case 'Supervisor':
            // Can see team leads and employees under them
            return $baseQuery . " AND (reporting_supervisor_id='$admin_id' OR id='$admin_id')";
            
        case 'Team Lead':
            // Can see their own team members
            return $baseQuery . " AND (reporting_team_lead_id='$admin_id' OR id='$admin_id')";
            
        case 'WFM Executive':
            // Can see all employees for attendance purposes
            return $baseQuery;
            
        case 'Finance Manager':
        case 'Accountant':
            // Can see all employees for payroll purposes
            return $baseQuery;
            
        default:
            // Employee or other roles - can only see themselves
            return $baseQuery . " AND id='$admin_id'";
    }
}

// Get employees list for dropdown (filtered by manageable roles)
function getEmployeeDropdown($conn, $selectedId = 0, $manageableRoles = null) {
    if ($manageableRoles === null) {
        $manageableRoles = getManageableRoles($conn);
    }
    
    $options = '<option value="">Select Employee</option>';
    
    $where = "";
    if (!empty($manageableRoles)) {
        $roleConditions = [];
        foreach ($manageableRoles as $r) {
            $roleConditions[] = "role='" . mysqli_real_escape_string($conn, $r) . "'";
        }
        $where = " AND (" . implode(' OR ', $roleConditions) . ")";
    }
    
    $q = mysqli_query($conn, "SELECT id, employee_id, full_name, role FROM employees WHERE status='Active' $where ORDER BY full_name ASC");
    while ($r = mysqli_fetch_assoc($q)) {
        $sel = ($r['id'] == $selectedId) ? 'selected' : '';
        $options .= "<option value='{$r['id']}' $sel>{$r['employee_id']} - {$r['full_name']} ({$r['role']})</option>";
    }
    return $options;
}

// Role-based redirect
function checkRoleAccess($conn, $allowedRoles = []) {
    $role = getAdminRole($conn);
    if (!empty($allowedRoles) && !in_array($role, $allowedRoles)) {
        header("Location: dashboard.php");
        exit();
    }
}

// Restrict page access by hierarchy level
function requireMinLevel($conn, $minLevel) {
    global $ROLE_HIERARCHY;
    $role = getAdminRole($conn);
    $level = $ROLE_HIERARCHY[$role] ?? 99;
    
    if ($level > $minLevel) {
        // Redirect to dashboard if not authorized
        header("Location: access_denied.php");
        exit();
    }
}
?>