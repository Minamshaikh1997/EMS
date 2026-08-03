<?php
/**
 * Employee Rights Management System
 * Provides functions for checking and managing individual employee feature access
 * 
 * @package EMS
 * @version 1.0
 */

// Prevent direct access
if (!defined('EMS_ROOT')) {
    define('EMS_ROOT', dirname(__DIR__));
}

/**
 * Check if employee has specific feature access
 * 
 * @param mysqli $conn Database connection
 * @param int $employee_id Employee ID
 * @param string $feature Feature name (e.g., 'can_view_payroll', 'can_apply_leave')
 * @return bool True if employee has access, false otherwise
 */
function hasEmployeeRight($conn, $employee_id, $feature) {
    // Super Admin and Admin roles have all rights
    if (isset($_SESSION['employee_role']) && in_array($_SESSION['employee_role'], ['Super Admin', 'Admin'])) {
        return true;
    }

    if (!$employee_id) {
        return false;
    }

    $allowed_features = [
        'can_view_payroll', 'can_apply_leave', 'can_view_attendance',
        'can_submit_adjustment', 'can_edit_profile', 'can_view_reports',
        'can_change_password'
    ];
    if (!in_array($feature, $allowed_features, true)) {
        return false;
    }

    // Check if feature is enabled for this employee
    $stmt = $conn->prepare("
        SELECT $feature FROM employees 
        WHERE id = ? AND $feature = 1
        LIMIT 1
    ");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $has_right = $result->num_rows > 0;
    $stmt->close();

    return $has_right;
}

/**
 * Require specific employee right - redirects if not authorized
 * 
 * @param mysqli $conn Database connection
 * @param int $employee_id Employee ID
 * @param string $feature Feature name
 * @param string $redirect_url URL to redirect to if access denied
 */
function requireEmployeeRight($conn, $employee_id, $feature, $redirect_url = 'dashboard.php') {
    if (!hasEmployeeRight($conn, $employee_id, $feature)) {
        header("Location: $redirect_url?error=access_denied");
        exit();
    }
}

/**
 * Get all rights for an employee
 * 
 * @param mysqli $conn Database connection
 * @param int $employee_id Employee ID
 * @return array Array of feature => is_enabled
 */
function getEmployeeRights($conn, $employee_id) {
    $rights = [];
    
    if (!$employee_id) {
        return $rights;
    }

    $result = mysqli_query($conn, "
        SELECT 
            can_view_payroll,
            can_apply_leave,
            can_view_attendance,
            can_submit_adjustment,
            can_edit_profile,
            can_view_reports,
            can_change_password
        FROM employees 
        WHERE id = '$employee_id'
        LIMIT 1
    ");

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $rights = [
            'can_view_payroll' => $row['can_view_payroll'] ?? 1,
            'can_apply_leave' => $row['can_apply_leave'] ?? 1,
            'can_view_attendance' => $row['can_view_attendance'] ?? 1,
            'can_submit_adjustment' => $row['can_submit_adjustment'] ?? 1,
            'can_edit_profile' => $row['can_edit_profile'] ?? 1,
            'can_view_reports' => $row['can_view_reports'] ?? 1,
            'can_change_password' => $row['can_change_password'] ?? 1
        ];
    }

    return $rights;
}

/**
 * Update employee right
 * 
 * @param mysqli $conn Database connection
 * @param int $employee_id Employee ID
 * @param string $feature Feature name
 * @param int $value 1 or 0
 * @return bool True on success, false on failure
 */
function updateEmployeeRight($conn, $employee_id, $feature, $value) {
    $allowed_features = [
        'can_view_payroll',
        'can_apply_leave',
        'can_view_attendance',
        'can_submit_adjustment',
        'can_edit_profile',
        'can_view_reports',
        'can_change_password'
    ];

    if (!in_array($feature, $allowed_features)) {
        return false;
    }

    $value = intval($value);
    $employee_id = intval($employee_id);

    $result = mysqli_query($conn, "UPDATE employees SET $feature = '$value' WHERE id = '$employee_id'");
    
    return $result;
}

/**
 * Check multiple rights at once
 * 
 * @param mysqli $conn Database connection
 * @param int $employee_id Employee ID
 * @param array $features Array of feature names
 * @return array Array of feature => has_access
 */
function checkMultipleRights($conn, $employee_id, $features) {
    $results = [];
    foreach ($features as $feature) {
        $results[$feature] = hasEmployeeRight($conn, $employee_id, $feature);
    }
    return $results;
}

?>
