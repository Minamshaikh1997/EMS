CREATE TABLE IF NOT EXISTS employee_requisitions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    job_title VARCHAR(150) NOT NULL,
    department VARCHAR(120) NOT NULL,
    positions INT UNSIGNED NOT NULL DEFAULT 1,
    employment_type VARCHAR(50) NOT NULL DEFAULT 'Full Time',
    required_by DATE NULL,
    justification TEXT NOT NULL,
    requirements TEXT NULL,
    priority ENUM('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium',
    requested_by INT NOT NULL,
    requested_by_name VARCHAR(120) NOT NULL,
    requested_by_role VARCHAR(60) NOT NULL,
    manager_id INT NULL,
    vp_id INT NULL,
    manager_status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    manager_comment TEXT NULL,
    manager_action_by INT NULL,
    manager_action_at DATETIME NULL,
    vp_status ENUM('Not Ready','Pending','Approved','Rejected') NOT NULL DEFAULT 'Not Ready',
    vp_comment TEXT NULL,
    vp_action_by INT NULL,
    vp_action_at DATETIME NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Pending Assistant Manager',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_requisition_status (status),
    KEY idx_requisition_manager (manager_id, manager_status),
    KEY idx_requisition_vp (vp_id, vp_status),
    KEY idx_requisition_requester (requested_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE employee_requisitions
    MODIFY status VARCHAR(50) NOT NULL DEFAULT 'Pending Assistant Manager';

CREATE TABLE IF NOT EXISTS employee_requisition_approvals (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    requisition_id INT UNSIGNED NOT NULL,
    step_order TINYINT UNSIGNED NOT NULL,
    stage_key VARCHAR(40) NOT NULL,
    stage_label VARCHAR(80) NOT NULL,
    approver_id INT NOT NULL,
    approver_name VARCHAR(120) NOT NULL,
    status ENUM('Waiting','Pending','Approved','Rejected') NOT NULL DEFAULT 'Waiting',
    comment TEXT NULL,
    acted_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_requisition_step (requisition_id, step_order),
    KEY idx_requisition_approver (approver_id, status),
    CONSTRAINT fk_requisition_approval FOREIGN KEY (requisition_id) REFERENCES employee_requisitions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
