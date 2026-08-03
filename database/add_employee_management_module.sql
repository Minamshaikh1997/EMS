-- Employee Management Phase 1
-- Safe additive migration; existing employee records are preserved.

ALTER TABLE employees
    ADD COLUMN IF NOT EXISTS cnic VARCHAR(15) NULL,
    ADD COLUMN IF NOT EXISTS date_of_birth DATE NULL,
    ADD COLUMN IF NOT EXISTS gender ENUM('Male','Female','Other','Prefer not to say') NULL,
    ADD COLUMN IF NOT EXISTS marital_status ENUM('Single','Married','Divorced','Widowed') NULL,
    ADD COLUMN IF NOT EXISTS phone VARCHAR(25) NULL,
    ADD COLUMN IF NOT EXISTS address TEXT NULL,
    ADD COLUMN IF NOT EXISTS city VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS emergency_contact_name VARCHAR(150) NULL,
    ADD COLUMN IF NOT EXISTS emergency_contact_relation VARCHAR(80) NULL,
    ADD COLUMN IF NOT EXISTS emergency_contact_phone VARCHAR(25) NULL,
    ADD COLUMN IF NOT EXISTS bank_name VARCHAR(150) NULL,
    ADD COLUMN IF NOT EXISTS bank_account_title VARCHAR(150) NULL,
    ADD COLUMN IF NOT EXISTS bank_account_number VARCHAR(80) NULL,
    ADD COLUMN IF NOT EXISTS iban VARCHAR(34) NULL,
    ADD COLUMN IF NOT EXISTS employment_type ENUM('Permanent','Contract','Probation','Intern','Part-time','Consultant') DEFAULT 'Permanent',
    ADD COLUMN IF NOT EXISTS probation_end_date DATE NULL,
    ADD COLUMN IF NOT EXISTS confirmation_date DATE NULL,
    ADD COLUMN IF NOT EXISTS separation_date DATE NULL,
    ADD COLUMN IF NOT EXISTS separation_reason TEXT NULL,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

CREATE TABLE IF NOT EXISTS employee_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    document_type ENUM('CNIC Front','CNIC Back','CV','Degree','Certificate','Contract','Offer Letter','Other') NOT NULL,
    document_title VARCHAR(180) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    expiry_date DATE NULL,
    notes VARCHAR(500) NULL,
    uploaded_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_employee_documents_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_employee_documents_employee (employee_id),
    INDEX idx_employee_documents_expiry (expiry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS employee_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    event_type ENUM('Created','Profile Updated','Status Changed','Department Changed','Designation Changed','Employment Changed','Document Added','Document Removed') NOT NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    notes VARCHAR(500) NULL,
    changed_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_employee_history_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_employee_history_employee_date (employee_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
