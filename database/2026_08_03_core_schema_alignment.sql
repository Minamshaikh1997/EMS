-- Align the live schema with fields already used by the EMS application.
-- Additive migration: existing employee and attendance records are preserved.

ALTER TABLE employees
    MODIFY COLUMN status ENUM('Active','Inactive','Suspended','Terminated') DEFAULT 'Active',
    ADD COLUMN IF NOT EXISTS reporting_manager_id INT NULL,
    ADD COLUMN IF NOT EXISTS reporting_supervisor_id INT NULL,
    ADD COLUMN IF NOT EXISTS reporting_team_lead_id INT NULL,
    ADD COLUMN IF NOT EXISTS can_view_payroll TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS can_apply_leave TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS can_view_attendance TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS can_submit_adjustment TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS can_edit_profile TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS can_view_reports TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS can_change_password TINYINT(1) NOT NULL DEFAULT 1;

CREATE TABLE IF NOT EXISTS shift_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    old_shift_name VARCHAR(100) NOT NULL,
    old_shift_start TIME NOT NULL,
    old_shift_end TIME NOT NULL,
    new_shift_name VARCHAR(100) NOT NULL,
    new_shift_start TIME NOT NULL,
    new_shift_end TIME NOT NULL,
    effective_date DATE NOT NULL,
    changed_by INT NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_shift_history_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    CONSTRAINT fk_shift_history_admin FOREIGN KEY (changed_by) REFERENCES admin(id) ON DELETE RESTRICT,
    INDEX idx_shift_history_employee (employee_id),
    INDEX idx_shift_history_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

