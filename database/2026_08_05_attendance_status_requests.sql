CREATE TABLE IF NOT EXISTS attendance_status_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attendance_id INT NOT NULL,
    requested_status VARCHAR(30) NOT NULL,
    requested_by INT NOT NULL,
    requested_by_name VARCHAR(120) NOT NULL,
    incharge_id INT NOT NULL,
    incharge_name VARCHAR(120) NOT NULL,
    status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    review_comment TEXT NULL,
    reviewed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_status_incharge (incharge_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
