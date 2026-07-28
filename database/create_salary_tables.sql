-- Create salary_components table first
CREATE TABLE IF NOT EXISTS salary_components (
    id INT PRIMARY KEY AUTO_INCREMENT,
    component_name VARCHAR(100) NOT NULL,
    component_type ENUM('Allowance', 'Deduction') NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create salary_structure table
CREATE TABLE IF NOT EXISTS salary_structure (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    basic_salary DECIMAL(10,2) DEFAULT 0.00,
    house_allowance DECIMAL(10,2) DEFAULT 0.00,
    medical_allowance DECIMAL(10,2) DEFAULT 0.00,
    transport_allowance DECIMAL(10,2) DEFAULT 0.00,
    other_allowance DECIMAL(10,2) DEFAULT 0.00,
    tax_deduction DECIMAL(10,2) DEFAULT 0.00,
    other_deduction DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Create salary_structure_components table
CREATE TABLE IF NOT EXISTS salary_structure_components (
    id INT PRIMARY KEY AUTO_INCREMENT,
    salary_structure_id INT NOT NULL,
    component_id INT NOT NULL,
    amount DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (salary_structure_id) REFERENCES salary_structure(id) ON DELETE CASCADE,
    FOREIGN KEY (component_id) REFERENCES salary_components(id) ON DELETE CASCADE
);

-- Create payroll table
CREATE TABLE IF NOT EXISTS payroll (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    payroll_month VARCHAR(20) NOT NULL,
    payroll_year INT NOT NULL,
    basic_salary DECIMAL(10,2) DEFAULT 0.00,
    allowances DECIMAL(10,2) DEFAULT 0.00,
    overtime DECIMAL(10,2) DEFAULT 0.00,
    bonus DECIMAL(10,2) DEFAULT 0.00,
    deductions DECIMAL(10,2) DEFAULT 0.00,
    tax DECIMAL(10,2) DEFAULT 0.00,
    net_salary DECIMAL(10,2) DEFAULT 0.00,
    payment_status ENUM('Pending', 'Paid') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_employee_id (employee_id),
    INDEX idx_payroll_month (payroll_month),
    INDEX idx_payroll_year (payroll_year),
    INDEX idx_payment_status (payment_status),
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Create salary_slips table
CREATE TABLE IF NOT EXISTS salary_slips (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    salary_month VARCHAR(7) NOT NULL,
    basic_salary DECIMAL(10,2) DEFAULT 0.00,
    allowance DECIMAL(10,2) DEFAULT 0.00,
    deduction DECIMAL(10,2) DEFAULT 0.00,
    gross_salary DECIMAL(10,2) DEFAULT 0.00,
    net_salary DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY unique_salary_slip (employee_id, salary_month)
);

-- Insert default salary components
INSERT INTO salary_components (component_name, component_type, status) VALUES
('House Allowance', 'Allowance', 'Active'),
('Medical Allowance', 'Allowance', 'Active'),
('Transport Allowance', 'Allowance', 'Active'),
('Overtime', 'Allowance', 'Active'),
('Bonus', 'Allowance', 'Active'),
('Tax', 'Deduction', 'Active'),
('Provident Fund', 'Deduction', 'Active'),
('Insurance', 'Deduction', 'Active')
ON DUPLICATE KEY UPDATE component_name=component_name;
