CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(100) NOT NULL UNIQUE,
    role_slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    hierarchy_level INT NOT NULL DEFAULT 6,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_roles_hierarchy (hierarchy_level),
    INDEX idx_roles_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    permission_name VARCHAR(100) NOT NULL UNIQUE,
    permission_slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    module_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_role_permission_module (role_id,permission_id,module_name),
    CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    INDEX idx_role_permissions_module (role_id,module_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO roles (role_name,role_slug,description,hierarchy_level) VALUES
('Super Admin','super_admin','Full system access',1),
('Admin','admin','Administrative access',2),
('Operations Manager','operations_manager','Operations and employee management',3),
('VP','vp','Executive requisition approval',3),
('Senior Assistant Manager','senior_assistant_manager','Second-stage requisition approval',4),
('Assistant Manager','assistant_manager','First-stage requisition approval',5),
('WFM Executive','wfm_executive','Attendance and workforce management',3),
('Finance Manager','finance_manager','Payroll and finance management',3),
('Accountant','accountant','Payroll processing support',4),
('Supervisor','supervisor','Team attendance and leave approvals',4),
('Team Lead','team_lead','Team oversight',5),
('Employee','employee','Employee self service',6);

INSERT IGNORE INTO permissions (permission_name,permission_slug,description) VALUES
('View','view','View module data'),
('Create','create','Create records'),
('Edit','edit','Edit records'),
('Delete','delete','Delete records'),
('Approve','approve','Approve requests'),
('Export','export','Export data');

-- Super Admin receives every permission for every module.
INSERT IGNORE INTO role_permissions (role_id,permission_id,module_name)
SELECT r.id,p.id,m.module_name
FROM roles r CROSS JOIN permissions p CROSS JOIN (
    SELECT 'dashboard' module_name UNION ALL SELECT 'employee_management' UNION ALL
    SELECT 'requisitions' UNION ALL
    SELECT 'attendance' UNION ALL SELECT 'leave_management' UNION ALL SELECT 'payroll' UNION ALL
    SELECT 'department' UNION ALL SELECT 'reports' UNION ALL SELECT 'notifications' UNION ALL
    SELECT 'settings' UNION ALL SELECT 'user_management' UNION ALL SELECT 'role_permission'
) m WHERE r.role_name='Super Admin';

-- Admin receives all standard operational permissions.
INSERT IGNORE INTO role_permissions (role_id,permission_id,module_name)
SELECT r.id,p.id,m.module_name
FROM roles r CROSS JOIN permissions p CROSS JOIN (
    SELECT 'dashboard' module_name UNION ALL SELECT 'employee_management' UNION ALL
    SELECT 'requisitions' UNION ALL
    SELECT 'attendance' UNION ALL SELECT 'leave_management' UNION ALL SELECT 'payroll' UNION ALL
    SELECT 'department' UNION ALL SELECT 'reports' UNION ALL SELECT 'notifications' UNION ALL
    SELECT 'settings' UNION ALL SELECT 'user_management'
) m WHERE r.role_name='Admin';

INSERT IGNORE INTO role_permissions (role_id,permission_id,module_name)
SELECT r.id,p.id,g.module_name FROM roles r JOIN (
 SELECT 'dashboard' module_name,'view' permission_slug UNION ALL SELECT 'employee_management','view' UNION ALL SELECT 'employee_management','create' UNION ALL SELECT 'employee_management','edit' UNION ALL
 SELECT 'attendance','view' UNION ALL SELECT 'attendance','approve' UNION ALL SELECT 'leave_management','view' UNION ALL SELECT 'leave_management','approve' UNION ALL SELECT 'reports','view' UNION ALL SELECT 'reports','export'
) g JOIN permissions p ON p.permission_slug=g.permission_slug WHERE r.role_name='Operations Manager';

INSERT IGNORE INTO role_permissions (role_id,permission_id,module_name)
SELECT r.id,p.id,g.module_name FROM roles r JOIN (
 SELECT 'dashboard' module_name,'view' permission_slug UNION ALL SELECT 'employee_management','view' UNION ALL SELECT 'attendance','view' UNION ALL SELECT 'attendance','create' UNION ALL SELECT 'attendance','edit' UNION ALL SELECT 'attendance','approve' UNION ALL SELECT 'attendance','export' UNION ALL SELECT 'leave_management','view' UNION ALL SELECT 'reports','view' UNION ALL SELECT 'reports','export'
) g JOIN permissions p ON p.permission_slug=g.permission_slug WHERE r.role_name='WFM Executive';

INSERT IGNORE INTO role_permissions (role_id,permission_id,module_name)
SELECT r.id,p.id,g.module_name FROM roles r JOIN (
 SELECT 'dashboard' module_name,'view' permission_slug UNION ALL SELECT 'payroll','view' UNION ALL SELECT 'payroll','create' UNION ALL SELECT 'payroll','edit' UNION ALL SELECT 'payroll','delete' UNION ALL SELECT 'payroll','approve' UNION ALL SELECT 'payroll','export' UNION ALL SELECT 'reports','view' UNION ALL SELECT 'reports','export'
) g JOIN permissions p ON p.permission_slug=g.permission_slug WHERE r.role_name='Finance Manager';

INSERT IGNORE INTO role_permissions (role_id,permission_id,module_name)
SELECT r.id,p.id,g.module_name FROM roles r JOIN (
 SELECT 'dashboard' module_name,'view' permission_slug UNION ALL SELECT 'payroll','view' UNION ALL SELECT 'payroll','create' UNION ALL SELECT 'payroll','edit' UNION ALL SELECT 'payroll','export' UNION ALL SELECT 'reports','view'
) g JOIN permissions p ON p.permission_slug=g.permission_slug WHERE r.role_name='Accountant';

INSERT IGNORE INTO role_permissions (role_id,permission_id,module_name)
SELECT r.id,p.id,g.module_name FROM roles r JOIN (
 SELECT 'dashboard' module_name,'view' permission_slug UNION ALL SELECT 'employee_management','view' UNION ALL SELECT 'attendance','view' UNION ALL SELECT 'attendance','approve' UNION ALL SELECT 'leave_management','view' UNION ALL SELECT 'leave_management','approve'
) g JOIN permissions p ON p.permission_slug=g.permission_slug WHERE r.role_name='Supervisor';

INSERT IGNORE INTO role_permissions (role_id,permission_id,module_name)
SELECT r.id,p.id,g.module_name FROM roles r JOIN (
 SELECT 'dashboard' module_name,'view' permission_slug UNION ALL SELECT 'employee_management','view' UNION ALL SELECT 'attendance','view' UNION ALL SELECT 'leave_management','view'
) g JOIN permissions p ON p.permission_slug=g.permission_slug WHERE r.role_name='Team Lead';

-- Requisition reviewers receive only the access needed for their approval step.
INSERT IGNORE INTO role_permissions (role_id,permission_id,module_name)
SELECT r.id,p.id,'requisitions'
FROM roles r
JOIN permissions p ON p.permission_slug IN ('view','approve')
WHERE r.role_name IN ('Assistant Manager','Senior Assistant Manager','Operations Manager','VP');
