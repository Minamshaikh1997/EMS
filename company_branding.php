<?php
require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');

$branding = [
    'company_name' => 'Leave System',
    'logo_url' => null,
];

try {
    $result = $conn->query('SELECT company_name, logo_path, updated_at FROM company_settings WHERE id=1 LIMIT 1');
    if ($result && ($row = $result->fetch_assoc())) {
        $name = trim((string)($row['company_name'] ?? ''));
        if ($name !== '') $branding['company_name'] = $name;

        $logoPath = str_replace('\\', '/', trim((string)($row['logo_path'] ?? '')));
        if (preg_match('#^uploads/company/[a-zA-Z0-9._-]+$#', $logoPath)) {
            $branding['logo_url'] = $logoPath . '?v=' . rawurlencode((string)($row['updated_at'] ?? ''));
        }
    }
} catch (Throwable $error) {
    error_log('Company branding lookup failed: ' . $error->getMessage());
}

echo json_encode($branding, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
