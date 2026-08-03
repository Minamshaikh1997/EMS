<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

$root = dirname(__DIR__);
$passed = 0;
$failed = 0;
$check = static function (bool $condition, string $label) use (&$passed, &$failed): void {
    $condition ? $passed++ : $failed++;
    echo ($condition ? '[PASS] ' : '[FAIL] ') . $label . PHP_EOL;
};

$check(version_compare(PHP_VERSION, '8.2.0', '>='), 'PHP 8.2 or newer');
foreach (['mysqli', 'fileinfo', 'json', 'openssl'] as $extension) {
    $check(extension_loaded($extension), "PHP extension: {$extension}");
}

foreach (['uploads', 'storage/backups'] as $directory) {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $directory);
    $check(is_dir($path) && is_writable($path), "Writable runtime directory: {$directory}");
}

$envExample = (string)file_get_contents($root . '/.env.example');
foreach (['EMS_APP_ENV', 'EMS_FORCE_HTTPS', 'EMS_DB_HOST', 'EMS_DB_USER', 'EMS_DB_PASSWORD', 'EMS_DB_NAME', 'EMS_MAIL_FROM'] as $name) {
    $check(str_contains($envExample, $name . '='), "Documented environment variable: {$name}");
}

$htaccess = (string)file_get_contents($root . '/.htaccess');
$check(str_contains($htaccess, 'Options -Indexes'), 'Directory listing disabled');
$check(str_contains($htaccess, 'Require all denied'), 'Sensitive files denied by Apache');
$check(str_contains($htaccess, 'X-Content-Type-Options'), 'Security headers configured');

if (strtolower((string)getenv('EMS_APP_ENV')) === 'production') {
    foreach (['EMS_DB_HOST', 'EMS_DB_USER', 'EMS_DB_NAME'] as $name) {
        $check(trim((string)getenv($name)) !== '', "Production value configured: {$name}");
    }
    $forceHttps = filter_var(getenv('EMS_FORCE_HTTPS') ?: false, FILTER_VALIDATE_BOOL);
    $check($forceHttps, 'Production secure cookies forced');
}

echo PHP_EOL . "Deployment readiness: {$passed} passed, {$failed} failed." . PHP_EOL;
exit($failed === 0 ? 0 : 1);
