<?php
if (PHP_SAPI !== 'cli') {
    session_start();
}
if (PHP_SAPI !== 'cli' && !isset($_SESSION['admin'])) {
    http_response_code(403);
    exit('Admin login required.');
}

include dirname(__DIR__) . '/config/db.php';

$statements = [
    "ALTER TABLE employees ADD COLUMN IF NOT EXISTS cnic VARCHAR(15) NULL",
    "ALTER TABLE employees ADD COLUMN IF NOT EXISTS date_of_birth DATE NULL",
    "ALTER TABLE employees ADD COLUMN IF NOT EXISTS gender ENUM('Male','Female','Other','Prefer not to say') NULL",
    "ALTER TABLE employees ADD COLUMN IF NOT EXISTS marital_status ENUM('Single','Married','Divorced','Widowed') NULL",
    "ALTER TABLE employees ADD COLUMN IF NOT EXISTS phone VARCHAR(25) NULL",
    "ALTER TABLE employees ADD COLUMN IF NOT EXISTS address TEXT NULL",
    "ALTER TABLE employees ADD COLUMN IF NOT EXISTS city VARCHAR(100) NULL",
    "ALTER TABLE employees ADD COLUMN IF NOT EXISTS emergency_contact_name VARCHAR(150) NULL",
    "ALTER TABLE employees ADD COLUMN IF NOT EXISTS emergency_contact_relation VARCHAR(80) NULL",
    "ALTER TABLE employees ADD COLUMN IF NOT EXISTS emergency_contact_phone VARCHAR(25) NULL",
    "ALTER TABLE employees ADD COLUMN IF NOT EXISTS bank_name VARCHAR(150) NULL",
    "ALTER TABLE employees ADD COLUMN IF NOT EXISTS bank_account_title VARCHAR(150) NULL",
    "ALTER TABLE employees ADD COLUMN IF NOT EXISTS bank_account_number VARCHAR(80) NULL",
    "ALTER TABLE employees ADD COLUMN IF NOT EXISTS iban VARCHAR(34) NULL",
    "ALTER TABLE employees ADD COLUMN IF NOT EXISTS employment_type ENUM('Permanent','Contract','Probation','Intern','Part-time','Consultant') DEFAULT 'Permanent'",
    "ALTER TABLE employees ADD COLUMN IF NOT EXISTS probation_end_date DATE NULL",
    "ALTER TABLE employees ADD COLUMN IF NOT EXISTS confirmation_date DATE NULL",
    "ALTER TABLE employees ADD COLUMN IF NOT EXISTS separation_date DATE NULL",
    "ALTER TABLE employees ADD COLUMN IF NOT EXISTS separation_reason TEXT NULL",
    "ALTER TABLE employees ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    "CREATE TABLE IF NOT EXISTS employee_documents (
        id INT AUTO_INCREMENT PRIMARY KEY, employee_id INT NOT NULL,
        document_type ENUM('CNIC Front','CNIC Back','CV','Degree','Certificate','Contract','Offer Letter','Other') NOT NULL,
        document_title VARCHAR(180) NOT NULL, file_name VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) NOT NULL, mime_type VARCHAR(100) NOT NULL,
        file_size INT UNSIGNED NOT NULL, expiry_date DATE NULL, notes VARCHAR(500) NULL,
        uploaded_by INT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_employee_documents_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
        INDEX idx_employee_documents_employee (employee_id), INDEX idx_employee_documents_expiry (expiry_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS employee_history (
        id INT AUTO_INCREMENT PRIMARY KEY, employee_id INT NOT NULL,
        event_type ENUM('Created','Profile Updated','Status Changed','Department Changed','Designation Changed','Employment Changed','Document Added','Document Removed') NOT NULL,
        old_value TEXT NULL, new_value TEXT NULL, notes VARCHAR(500) NULL, changed_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_employee_history_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
        INDEX idx_employee_history_employee_date (employee_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

$errors = [];
foreach ($statements as $sql) {
    try { $conn->query($sql); } catch (Throwable $e) { $errors[] = $e->getMessage(); }
}

function addUniqueIndexIfMissing($conn, $table, $index, $column) {
    $safeIndex = preg_replace('/[^a-zA-Z0-9_]/', '', $index);
    $result = $conn->query("SHOW INDEX FROM `$table` WHERE Key_name='$safeIndex'");
    if ($result && $result->num_rows === 0) {
        $conn->query("ALTER TABLE `$table` ADD UNIQUE KEY `$safeIndex` (`$column`)");
    }
}

try { addUniqueIndexIfMissing($conn, 'employees', 'unique_employee_code', 'employee_id'); }
catch (Throwable $e) { $errors[] = 'Employee code index: ' . $e->getMessage(); }
try { addUniqueIndexIfMissing($conn, 'employees', 'unique_employee_cnic', 'cnic'); }
catch (Throwable $e) { $errors[] = 'CNIC index: ' . $e->getMessage(); }

if (PHP_SAPI === 'cli') {
    echo empty($errors) ? "Employee Management upgrade completed.\n" : "Upgrade completed with warnings:\n- " . implode("\n- ", $errors) . "\n";
    exit(empty($errors) ? 0 : 1);
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Employee Management Upgrade</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><main class="container py-5"><div class="card shadow-sm"><div class="card-body p-4">
<h2>Employee Management Upgrade</h2>
<?php if (!$errors): ?><div class="alert alert-success">Upgrade completed successfully.</div>
<?php else: ?><div class="alert alert-warning">Upgrade completed with warnings:<ul><?php foreach ($errors as $error): ?><li><?=htmlspecialchars($error)?></li><?php endforeach; ?></ul></div><?php endif; ?>
<a class="btn btn-primary" href="../admin/employee.php">Open Employees</a>
</div></div></main></body></html>
