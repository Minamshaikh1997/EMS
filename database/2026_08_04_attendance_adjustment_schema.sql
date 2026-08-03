ALTER TABLE attendance_adjustments
    ADD COLUMN IF NOT EXISTS request_no VARCHAR(40) NULL AFTER id,
    ADD COLUMN IF NOT EXISTS attendance_id INT NULL AFTER employee_id,
    ADD COLUMN IF NOT EXISTS attendance_date DATE NULL AFTER attendance_id,
    ADD COLUMN IF NOT EXISTS adjustment_type VARCHAR(100) NULL AFTER attendance_date,
    ADD COLUMN IF NOT EXISTS requested_check_in TIME NULL AFTER adjustment_type,
    ADD COLUMN IF NOT EXISTS requested_check_out TIME NULL AFTER requested_check_in,
    ADD COLUMN IF NOT EXISTS attachment VARCHAR(255) NULL AFTER reason,
    ADD COLUMN IF NOT EXISTS supervisor_comment TEXT NULL AFTER supervisor_id,
    ADD COLUMN IF NOT EXISTS supervisor_date DATETIME NULL AFTER supervisor_comment,
    ADD COLUMN IF NOT EXISTS admin_comment TEXT NULL AFTER supervisor_date,
    ADD COLUMN IF NOT EXISTS admin_id INT NULL AFTER admin_comment,
    ADD COLUMN IF NOT EXISTS admin_date DATETIME NULL AFTER admin_id;

ALTER TABLE attendance_adjustments
    MODIFY request_date DATE NULL,
    MODIFY status ENUM('Pending','Approved','Rejected','Hold','Cancelled') NOT NULL DEFAULT 'Pending';

UPDATE attendance_adjustments
SET attendance_date = COALESCE(attendance_date, request_date),
    adjustment_type = COALESCE(NULLIF(adjustment_type, ''), 'Other'),
    request_no = COALESCE(NULLIF(request_no, ''), CONCAT('LEGACY-', LPAD(id, 8, '0')));

ALTER TABLE attendance_adjustments
    ADD UNIQUE KEY IF NOT EXISTS uq_attendance_adjustment_request_no (request_no),
    ADD KEY IF NOT EXISTS idx_attendance_adjustment_employee_status (employee_id, status),
    ADD KEY IF NOT EXISTS idx_attendance_adjustment_attendance (attendance_id);
