CREATE TABLE IF NOT EXISTS performance_campaigns (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150) NOT NULL UNIQUE,
    description TEXT NULL, is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS campaign_kpis (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, campaign_id INT UNSIGNED NOT NULL,
    kpi_name VARCHAR(150) NOT NULL, kpi_code VARCHAR(80) NOT NULL,
    weight DECIMAL(6,2) NOT NULL, display_order INT NOT NULL DEFAULT 0,
    UNIQUE KEY uq_campaign_code (campaign_id,kpi_code),
    CONSTRAINT fk_campaign_kpi_campaign FOREIGN KEY (campaign_id) REFERENCES performance_campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS employee_campaign_assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, employee_id INT NOT NULL,
    campaign_id INT UNSIGNED NOT NULL, assigned_from DATE NOT NULL, assigned_to DATE NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_active_assignment (employee_id,campaign_id,assigned_from),
    CONSTRAINT fk_campaign_assignment_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    CONSTRAINT fk_campaign_assignment_campaign FOREIGN KEY (campaign_id) REFERENCES performance_campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS campaign_performance (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, employee_id INT NOT NULL,
    campaign_id INT UNSIGNED NOT NULL, period_month TINYINT NOT NULL, period_year SMALLINT NOT NULL,
    total_score DECIMAL(6,2) NOT NULL, grade VARCHAR(5) NOT NULL, remarks TEXT NULL,
    uploaded_by INT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_campaign_performance (employee_id,campaign_id,period_month,period_year),
    CONSTRAINT fk_campaign_performance_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    CONSTRAINT fk_campaign_performance_campaign FOREIGN KEY (campaign_id) REFERENCES performance_campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS campaign_performance_scores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, performance_id INT UNSIGNED NOT NULL,
    kpi_id INT UNSIGNED NOT NULL, score DECIMAL(6,2) NOT NULL,
    UNIQUE KEY uq_performance_kpi (performance_id,kpi_id),
    CONSTRAINT fk_campaign_score_performance FOREIGN KEY (performance_id) REFERENCES campaign_performance(id) ON DELETE CASCADE,
    CONSTRAINT fk_campaign_score_kpi FOREIGN KEY (kpi_id) REFERENCES campaign_kpis(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS employee_kpi_performance (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, employee_id INT NOT NULL,
    period_month TINYINT UNSIGNED NOT NULL, period_year SMALLINT UNSIGNED NOT NULL,
    kpi_title VARCHAR(180) NOT NULL, target_score DECIMAL(6,2) NOT NULL DEFAULT 100,
    achieved_score DECIMAL(6,2) NOT NULL DEFAULT 0, performance_rating VARCHAR(30) NOT NULL,
    remarks TEXT NULL, attachment VARCHAR(255) NULL, original_filename VARCHAR(255) NULL,
    uploaded_by INT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    login_score DECIMAL(5,2) NULL, withdraw_score DECIMAL(5,2) NULL, aht_score DECIMAL(5,2) NULL,
    attendance_score DECIMAL(5,2) NULL, adherence_score DECIMAL(5,2) NULL,
    call_quality_score DECIMAL(5,2) NULL, gc_conversion_score DECIMAL(5,2) NULL,
    complaint_score DECIMAL(5,2) NULL, quiz_score DECIMAL(5,2) NULL, total_score DECIMAL(6,2) NULL,
    grade VARCHAR(5) NULL, designation_snapshot VARCHAR(100) NULL, team_snapshot VARCHAR(120) NULL,
    team_leader_snapshot VARCHAR(150) NULL,
    KEY idx_employee_period (employee_id,period_year,period_month),
    CONSTRAINT fk_kpi_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mis_adjustments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, request_no VARCHAR(30) NOT NULL UNIQUE,
    employee_id INT NOT NULL, recipient_id INT NOT NULL, campaign_id INT UNSIGNED NULL,
    adjustment_date DATE NOT NULL, adjustment_type VARCHAR(50) NOT NULL, adjustment_time TIME NOT NULL,
    reason_category VARCHAR(100) NOT NULL, reason_subcategory VARCHAR(100) NULL, reason TEXT NOT NULL,
    status ENUM('Pending','Approved','Rejected','Cancelled') NOT NULL DEFAULT 'Pending',
    approver_comment TEXT NULL, decided_at DATETIME NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mis_sender (employee_id), INDEX idx_mis_recipient (recipient_id),
    CONSTRAINT fk_mis_sender FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    CONSTRAINT fk_mis_recipient FOREIGN KEY (recipient_id) REFERENCES employees(id) ON DELETE CASCADE,
    CONSTRAINT fk_mis_campaign FOREIGN KEY (campaign_id) REFERENCES performance_campaigns(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
