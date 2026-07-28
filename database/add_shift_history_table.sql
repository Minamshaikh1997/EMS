-- Add shift_history table for tracking shift changes
-- This table stores the history of all shift changes made to employee records

CREATE TABLE IF NOT EXISTS shift_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    old_shift_name VARCHAR(50) NOT NULL,
    old_shift_start TIME NOT NULL,
    old_shift_end TIME NOT NULL,
    new_shift_name VARCHAR(50) NOT NULL,
    new_shift_start TIME NOT NULL,
    new_shift_end TIME NOT NULL,
    effective_date DATE NOT NULL,
    changed_by INT NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES admin(id) ON DELETE CASCADE
);

-- Add index for better query performance
CREATE INDEX idx_shift_history_employee ON shift_history(employee_id);
CREATE INDEX idx_shift_history_changed_at ON shift_history(changed_at DESC);