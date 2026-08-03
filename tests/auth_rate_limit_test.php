<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';

$_SERVER['REMOTE_ADDR'] = '127.0.0.250';
$_SERVER['HTTP_USER_AGENT'] = 'EMS automated auth test';
$email = 'rate-limit-test-' . bin2hex(random_bytes(6)) . '@example.invalid';

mysqli_begin_transaction($conn);
try {
    if (ems_db_login_is_limited($conn, $email)) {
        throw new RuntimeException('Fresh identity was unexpectedly limited.');
    }
    for ($i = 0; $i < 5; $i++) {
        ems_db_record_failed_login($conn, $email);
    }
    if (!ems_db_login_is_limited($conn, $email)) {
        throw new RuntimeException('Five failures did not trigger the limit.');
    }
    ems_db_resolve_login_attempts($conn, $email);
    if (ems_db_login_is_limited($conn, $email)) {
        throw new RuntimeException('Resolved attempts still trigger the limit.');
    }
    mysqli_rollback($conn);
    echo "[PASS] Database login throttling and resolution\n";
    exit(0);
} catch (Throwable $error) {
    mysqli_rollback($conn);
    fwrite(STDERR, "[FAIL] " . $error->getMessage() . "\n");
    exit(1);
}

