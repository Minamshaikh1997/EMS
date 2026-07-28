-- ============================================
-- ROLE & PERMISSION MANAGEMENT SYSTEM
-- Database Tables and Sample Data
-- ============================================

-- Drop tables if they exist (for clean installation)
DROP TABLE IF EXISTS role_permissions;
DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS roles;

-- ============================================
-- 1. ROLES TABLE
-- ============================================
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(100) NOT NULL UNIQUE,
    role_slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    hierarchy_level INT NOT NULL DEFAULT 6,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_hierarchy (hierarchy_level),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. PERMISSIONS TABLE
-- ============================================
CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    permission_name VARCHAR(100) NOT NULL UNIQUE,
    permission_slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (permission_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. ROLE_PERMISSIONS TABLE (Pivot Table)
-- ============================================
CREATE TABLE role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    module_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_role_permission_module (role_id, permission_id, module_name),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    INDEX idx_role_module (role_id, module_name),
    INDEX idx_permission (permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERT ROLES
-- ============================================
INSERT INTO roles (role_name, role_slug, description, hierarchy_level) VALUES
('Super Admin', 'super_admin', 'Full system access. Can manage all users, settings, and data.', 1),
('Admin', 'admin', 'Managing Director. Manages departments, managers, employees.', 2),
('VP', 'vp', 'Vice President. High-level oversight and reporting.', 3),
('Supervisor', 'supervisor', 'Manages Team Leads. Approves attendance and leave requests.', 4),
('Team Lead', 'team_lead', 'Manages employees in their team. Approves daily attendance.', 5),
('WFM', 'wfm', 'Workforce Management. Manages attendance, shifts, schedules.', 3),
('Operations Manager', 'operations_manager', 'Manages operations and supervises teams.', 3),
('Employee', 'employee', 'Can view own profile, mark attendance, request leave.', 6);

-- ============================================
-- INSERT PERMISSIONS
-- ============================================
INSERT INTO permissions (permission_name, permission_slug, description) VALUES
('View', 'view', 'Can view/module data'),
('Create', 'create', 'Can create new records'),
('Edit', 'edit', 'Can edit existing records'),
('Delete', 'delete', 'Can delete records'),
('Approve', 'approve', 'Can approve requests/records'),
('Export', 'export', 'Can export data to Excel/PDF');

-- ============================================
-- INSERT ROLE_PERMISSIONS (Sample Data)
-- Super Admin gets ALL permissions on ALL modules
-- ============================================

-- Get role IDs
SET @super_admin_id = (SELECT id FROM roles WHERE role_slug = 'super_admin');
SET @admin_id = (SELECT id FROM roles WHERE role_slug = 'admin');
SET @vp_id = (SELECT id FROM roles WHERE role_slug = 'vp');
SET @supervisor_id = (SELECT id FROM roles WHERE role_slug = 'supervisor');
SET @team_lead_id = (SELECT id FROM roles WHERE role_slug = 'team_lead');
SET @wfm_id = (SELECT id FROM roles WHERE role_slug = 'wfm');
SET @operations_manager_id = (SELECT id FROM roles WHERE role_slug = 'operations_manager');
SET @employee_id = (SELECT id FROM roles WHERE role_slug = 'employee');

-- Get permission IDs
SET @view_id = (SELECT id FROM permissions WHERE permission_slug = 'view');
SET @create_id = (SELECT id FROM permissions WHERE permission_slug = 'create');
SET @edit_id = (SELECT id FROM permissions WHERE permission_slug = 'edit');
SET @delete_id = (SELECT id FROM permissions WHERE permission_slug = 'delete');
SET @approve_id = (SELECT id FROM permissions WHERE permission_slug = 'approve');
SET @export_id = (SELECT id FROM permissions WHERE permission_slug = 'export');

-- ============================================
-- SUPER ADMIN - All permissions on all modules
-- ============================================
INSERT INTO role_permissions (role_id, permission_id, module_name) VALUES
-- Dashboard
(@super_admin_id, @view_id, 'dashboard'),
-- Employee Management
(@super_admin_id, @view_id, 'employee_management'),
(@super_admin_id, @create_id, 'employee_management'),
(@super_admin_id, @edit_id, 'employee_management'),
(@super_admin_id, @delete_id, 'employee_management'),
(@super_admin_id, @export_id, 'employee_management'),
-- Attendance
(@super_admin_id, @view_id, 'attendance'),
(@super_admin_id, @create_id, 'attendance'),
(@super_admin_id, @edit_id, 'attendance'),
(@super_admin_id, @delete_id, 'attendance'),
(@super_admin_id, @approve_id, 'attendance'),
(@super_admin_id, @export_id, 'attendance'),
-- Leave Management
(@super_admin_id, @view_id, 'leave_management'),
(@super_admin_id, @create_id, 'leave_management'),
(@super_admin_id, @edit_id, 'leave_management'),
(@super_admin_id, @delete_id, 'leave_management'),
(@super_admin_id, @approve_id, 'leave_management'),
(@super_admin_id, @export_id, 'leave_management'),
-- Payroll
(@super_admin_id, @view_id, 'payroll'),
(@super_admin_id, @create_id, 'payroll'),
(@super_admin_id, @edit_id, 'payroll'),
(@super_admin_id, @delete_id, 'payroll'),
(@super_admin_id, @approve_id, 'payroll'),
(@super_admin_id, @export_id, 'payroll'),
-- Department
(@super_admin_id, @view_id, 'department'),
(@super_admin_id, @create_id, 'department'),
(@super_admin_id, @edit_id, 'department'),
(@super_admin_id, @delete_id, 'department'),
(@super_admin_id, @export_id, 'department'),
-- Reports
(@super_admin_id, @view_id, 'reports'),
(@super_admin_id, @export_id, 'reports'),
-- Notifications
(@super_admin_id, @view_id, 'notifications'),
(@super_admin_id, @create_id, 'notifications'),
(@super_admin_id, @edit_id, 'notifications'),
(@super_admin_id, @delete_id, 'notifications'),
-- Settings
(@super_admin_id, @view_id, 'settings'),
(@super_admin_id, @edit_id, 'settings'),
-- User Management
(@super_admin_id, @view_id, 'user_management'),
(@super_admin_id, @create_id, 'user_management'),
(@super_admin_id, @edit_id, 'user_management'),
(@super_admin_id, @delete_id, 'user_management'),
-- Role & Permission
(@super_admin_id, @view_id, 'role_permission'),
(@super_admin_id, @create_id, 'role_permission'),
(@super_admin_id, @edit_id, 'role_permission'),
(@super_admin_id, @delete_id, 'role_permission');

-- ============================================
-- ADMIN - Almost all permissions except Super Admin management
-- ============================================
INSERT INTO role_permissions (role_id, permission_id, module_name) VALUES
-- Dashboard
(@admin_id, @view_id, 'dashboard'),
-- Employee Management
(@admin_id, @view_id, 'employee_management'),
(@admin_id, @create_id, 'employee_management'),
(@admin_id, @edit_id, 'employee_management'),
(@admin_id, @delete_id, 'employee_management'),
(@admin_id, @export_id, 'employee_management'),
-- Attendance
(@admin_id, @view_id, 'attendance'),
(@admin_id, @create_id, 'attendance'),
(@admin_id, @edit_id, 'attendance'),
(@admin_id, @delete_id, 'attendance'),
(@admin_id, @approve_id, 'attendance'),
(@admin_id, @export_id, 'attendance'),
-- Leave Management
(@admin_id, @view_id, 'leave_management'),
(@admin_id, @create_id, 'leave_management'),
(@admin_id, @edit_id, 'leave_management'),
(@admin_id, @delete_id, 'leave_management'),
(@admin_id, @approve_id, 'leave_management'),
(@admin_id, @export_id, 'leave_management'),
-- Payroll
(@admin_id, @view_id, 'payroll'),
(@admin_id, @create_id, 'payroll'),
(@admin_id, @edit_id, 'payroll'),
(@admin_id, @delete_id, 'payroll'),
(@admin_id, @approve_id, 'payroll'),
(@admin_id, @export_id, 'payroll'),
-- Department
(@admin_id, @view_id, 'department'),
(@admin_id, @create_id, 'department'),
(@admin_id, @edit_id, 'department'),
(@admin_id, @delete_id, 'department'),
(@admin_id, @export_id, 'department'),
-- Reports
(@admin_id, @view_id, 'reports'),
(@admin_id, @export_id, 'reports'),
-- Notifications
(@admin_id, @view_id, 'notifications'),
(@admin_id, @create_id, 'notifications'),
(@admin_id, @edit_id, 'notifications'),
(@admin_id, @delete_id, 'notifications'),
-- Settings
(@admin_id, @view_id, 'settings'),
(@admin_id, @edit_id, 'settings'),
-- User Management
(@admin_id, @view_id, 'user_management'),
(@admin_id, @create_id, 'user_management'),
(@admin_id, @edit_id, 'user_management'),
(@admin_id, @delete_id, 'user_management'),
-- Role & Permission
(@admin_id, @view_id, 'role_permission'),
(@admin_id, @edit_id, 'role_permission');

-- ============================================
-- VP - High-level access
-- ============================================
INSERT INTO role_permissions (role_id, permission_id, module_name) VALUES
(@vp_id, @view_id, 'dashboard'),
(@vp_id, @view_id, 'employee_management'),
(@vp_id, @view_id, 'attendance'),
(@vp_id, @view_id, 'leave_management'),
(@vp_id, @approve_id, 'leave_management'),
(@vp_id, @view_id, 'payroll'),
(@vp_id, @view_id, 'reports'),
(@vp_id, @export_id, 'reports'),
(@vp_id, @view_id, 'notifications');

-- ============================================
-- OPERATIONS MANAGER
-- ============================================
INSERT INTO role_permissions (role_id, permission_id, module_name) VALUES
(@operations_manager_id, @view_id, 'dashboard'),
(@operations_manager_id, @view_id, 'employee_management'),
(@operations_manager_id, @create_id, 'employee_management'),
(@operations_manager_id, @edit_id, 'employee_management'),
(@operations_manager_id, @view_id, 'attendance'),
(@operations_manager_id, @approve_id, 'attendance'),
(@operations_manager_id, @view_id, 'leave_management'),
(@operations_manager_id, @approve_id, 'leave_management'),
(@operations_manager_id, @view_id, 'reports'),
(@operations_manager_id, @export_id, 'reports');

-- ============================================
-- WFM - Workforce Management
-- ============================================
INSERT INTO role_permissions (role_id, permission_id, module_name) VALUES
(@wfm_id, @view_id, 'dashboard'),
(@wfm_id, @view_id, 'employee_management'),
(@wfm_id, @view_id, 'attendance'),
(@wfm_id, @create_id, 'attendance'),
(@wfm_id, @edit_id, 'attendance'),
(@wfm_id, @approve_id, 'attendance'),
(@wfm_id, @export_id, 'attendance'),
(@wfm_id, @view_id, 'leave_management'),
(@wfm_id, @approve_id, 'leave_management'),
(@wfm_id, @view_id, 'reports'),
(@wfm_id, @export_id, 'reports');

-- ============================================
-- SUPERVISOR
-- ============================================
INSERT INTO role_permissions (role_id, permission_id, module_name) VALUES
(@supervisor_id, @view_id, 'dashboard'),
(@supervisor_id, @view_id, 'employee_management'),
(@supervisor_id, @view_id, 'attendance'),
(@supervisor_id, @approve_id, 'attendance'),
(@supervisor_id, @view_id, 'leave_management'),
(@supervisor_id, @approve_id, 'leave_management');

-- ============================================
-- TEAM LEAD
-- ============================================
INSERT INTO role_permissions (role_id, permission_id, module_name) VALUES
(@team_lead_id, @view_id, 'dashboard'),
(@team_lead_id, @view_id, 'employee_management'),
(@team_lead_id, @view_id, 'attendance'),
(@team_lead_id, @view_id, 'leave_management');

-- ============================================
-- EMPLOYEE - Basic access only
-- ============================================
INSERT INTO role_permissions (role_id, permission_id, module_name) VALUES
(@employee_id, @view_id, 'dashboard'),
(@employee_id, @view_id, 'employee_management'),
(@employee_id, @view_id, 'attendance'),
(@employee_id, @create_id, 'leave_management'),
(@employee_id, @view_id, 'notifications');

-- ============================================
-- 4. PERMISSION_LOGS TABLE (Audit Trail)
-- ============================================
CREATE TABLE permission_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT DEFAULT 0,
    admin_email VARCHAR(255) DEFAULT '',
    role VARCHAR(100) DEFAULT 'Unknown',
    module VARCHAR(100) NOT NULL,
    permission VARCHAR(100) NOT NULL,
    granted TINYINT(1) DEFAULT 1,
    ip_address VARCHAR(45) DEFAULT '',
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin (admin_id),
    INDEX idx_module (module),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- VERIFICATION QUERIES
-- ============================================
SELECT 
    r.role_name,
    p.permission_name,
    rp.module_name
FROM role_permissions rp
JOIN roles r ON rp.role_id = r.id
JOIN permissions p ON rp.permission_id = p.id
ORDER BY r.hierarchy_level, rp.module_name, p.permission_name;

-- Show role summary
SELECT 
    r.role_name,
    r.hierarchy_level,
    COUNT(DISTINCT rp.module_name) as modules_count,
    COUNT(rp.id) as permissions_count
FROM roles r
LEFT JOIN role_permissions rp ON r.id = rp.role_id
GROUP BY r.id, r.role_name, r.hierarchy_level
ORDER BY r.hierarchy_level ASC;
