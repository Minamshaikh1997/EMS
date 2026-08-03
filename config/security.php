<?php

/**
 * Shared web-security helpers.
 * Include this file before reading or writing session data.
 */
function ems_start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $forceHttps = filter_var(getenv('EMS_FORCE_HTTPS') ?: false, FILTER_VALIDATE_BOOL);
    $isHttps = $forceHttps || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
    if (isset($_SESSION['admin_role'])) {
        $_SESSION['admin_role_original'] = $_SESSION['admin_role_original'] ?? $_SESSION['admin_role'];
        $_SESSION['admin_role'] = ems_canonical_admin_role((string)$_SESSION['admin_role']);
    }
}

function ems_canonical_admin_role(string $role): string
{
    $normalized = strtolower(trim($role));
    return match ($normalized) {
        'ceo', 'chief executive officer', 'superadmin', 'super_admin', 'super admin' => 'Super Admin',
        'managing director', 'administrator', 'admin' => 'Admin',
        'wfm', 'workforce management', 'wfm executive' => 'WFM Executive',
        default => trim($role),
    };
}

function ems_login_is_limited(): bool
{
    $attempts = $_SESSION['login_attempts'] ?? [];
    $cutoff = time() - 900;
    $attempts = array_values(array_filter($attempts, static fn($time) => $time >= $cutoff));
    $_SESSION['login_attempts'] = $attempts;
    return count($attempts) >= 5;
}

function ems_record_failed_login(): void
{
    $_SESSION['login_attempts'][] = time();
}

function ems_complete_login(): void
{
    unset($_SESSION['login_attempts']);
    session_regenerate_id(true);
}

function ems_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function ems_verify_csrf(): void
{
    $submitted = (string)($_POST['csrf_token'] ?? '');
    $expected = (string)($_SESSION['csrf_token'] ?? '');
    if ($expected === '' || !hash_equals($expected, $submitted)) {
        http_response_code(403);
        exit('The request could not be verified. Please refresh the page and try again.');
    }
}

function ems_password_validation_error(string $password): ?string
{
    if (strlen($password) < 12) {
        return 'Password must be at least 12 characters long.';
    }
    if (!preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password)
        || !preg_match('/\d/', $password) || !preg_match('/[^a-zA-Z0-9]/', $password)) {
        return 'Password must include uppercase, lowercase, number and special characters.';
    }
    return null;
}

function ems_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
