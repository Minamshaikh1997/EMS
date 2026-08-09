-- Adds the requisition approval roles and safely seeds their least-privilege access.
-- This migration is idempotent and does not remove manually assigned permissions.

INSERT INTO roles (role_name, role_slug, description, hierarchy_level, is_active) VALUES
('VP', 'vp', 'Executive requisition approval', 3, 1),
('Senior Assistant Manager', 'senior_assistant_manager', 'Second-stage requisition approval', 4, 1),
('Assistant Manager', 'assistant_manager', 'First-stage requisition approval', 5, 1)
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    hierarchy_level = VALUES(hierarchy_level),
    is_active = 1;

INSERT IGNORE INTO role_permissions (role_id, permission_id, module_name)
SELECT r.id, p.id, 'requisitions'
FROM roles r
JOIN permissions p ON p.permission_slug IN ('view', 'approve')
WHERE r.role_name IN ('Assistant Manager', 'Senior Assistant Manager', 'Operations Manager', 'VP');

INSERT IGNORE INTO role_permissions (role_id, permission_id, module_name)
SELECT r.id, p.id, 'requisitions'
FROM roles r
JOIN permissions p
WHERE r.role_name IN ('Super Admin', 'Admin');
