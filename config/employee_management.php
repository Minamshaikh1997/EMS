<?php

function employeeManagementCsrfToken() {
    if (empty($_SESSION['employee_management_csrf'])) {
        $_SESSION['employee_management_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['employee_management_csrf'];
}

function verifyEmployeeManagementCsrf() {
    $token = $_POST['csrf_token'] ?? '';
    return !empty($token) && isset($_SESSION['employee_management_csrf'])
        && hash_equals($_SESSION['employee_management_csrf'], $token);
}

function normalizeOptionalDate($value) {
    $value = trim((string)$value);
    if ($value === '') return null;
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : null;
}

function normalizeCnic($value) {
    $digits = preg_replace('/\D+/', '', (string)$value);
    if ($digits === '') return null;
    if (strlen($digits) !== 13) return false;
    return substr($digits, 0, 5) . '-' . substr($digits, 5, 7) . '-' . substr($digits, 12, 1);
}

function logEmployeeHistory($conn, $employeeId, $eventType, $oldValue = null, $newValue = null, $notes = null) {
    $adminId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
    $stmt = $conn->prepare("INSERT INTO employee_history (employee_id, event_type, old_value, new_value, notes, changed_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('issssi', $employeeId, $eventType, $oldValue, $newValue, $notes, $adminId);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function employeeManagementSchemaReady($conn) {
    $result = $conn->query("SHOW TABLES LIKE 'employee_documents'");
    if (!$result || $result->num_rows === 0) return false;
    $result = $conn->query("SHOW COLUMNS FROM employees LIKE 'cnic'");
    return $result && $result->num_rows > 0;
}

function uploadEmployeeDocument($file, $employeeId) {
    $allowed = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Please select a valid document file.');
    }
    if ($file['size'] < 1 || $file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Document size must be between 1 byte and 5 MB.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Only PDF, JPG and PNG documents are allowed.');
    }
    $directory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'employee_documents';
    if (!is_dir($directory) && !mkdir($directory, 0750, true)) {
        throw new RuntimeException('Document storage directory could not be created.');
    }
    $stored = 'EMP_' . (int)$employeeId . '_' . bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . DIRECTORY_SEPARATOR . $stored)) {
        throw new RuntimeException('Document upload failed.');
    }
    return ['file_name' => $stored, 'mime_type' => $mime, 'file_size' => (int)$file['size']];
}

function deleteStoredEmployeeDocument($fileName) {
    $safeName = basename((string)$fileName);
    $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'employee_documents' . DIRECTORY_SEPARATOR . $safeName;
    return !is_file($path) || unlink($path);
}

