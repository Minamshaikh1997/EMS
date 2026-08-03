<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$action = $argv[1] ?? '';
$fixtureFile = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'ems-browser-smoke-fixture.json';
$employeeFixtureFile = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'ems-browser-smoke-employee-fixture.json';

if ($action === 'create-employee') {
    if (is_file($employeeFixtureFile)) {
        fwrite(STDERR, "An employee browser smoke fixture already exists. Clean it first.\n");
        exit(1);
    }

    $suffix = date('YmdHis') . bin2hex(random_bytes(3));
    $employeeCode = 'SMK' . substr($suffix, -12);
    $email = "codex.employee.{$suffix}@example.invalid";
    $password = 'Smoke!' . bin2hex(random_bytes(8)) . 'Aa9';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $name = 'Codex Employee Smoke Test';
    $role = 'Employee';
    $department = 'Quality Assurance';
    $designation = 'Smoke Tester';
    $joiningDate = date('Y-m-d');

    $stmt = $conn->prepare('INSERT INTO employees (employee_id,full_name,email,role,password,department,designation,joining_date,status,is_active) VALUES (?,?,?,?,?,?,?,?,\'Active\',1)');
    $stmt->bind_param('ssssssss', $employeeCode, $name, $email, $role, $hash, $department, $designation, $joiningDate);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();

    $fixture = ['employee_row_id' => $id, 'employee_id' => $employeeCode, 'email' => $email, 'password' => $password];
    file_put_contents($employeeFixtureFile, json_encode($fixture, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    echo json_encode($fixture, JSON_UNESCAPED_SLASHES), PHP_EOL;
    exit;
}

if ($action === 'cleanup-employee') {
    if (!is_file($employeeFixtureFile)) {
        echo "No employee browser smoke fixture exists.\n";
        exit;
    }

    $fixture = json_decode((string) file_get_contents($employeeFixtureFile), true, 512, JSON_THROW_ON_ERROR);
    $id = (int) ($fixture['employee_row_id'] ?? 0);
    $employeeCode = (string) ($fixture['employee_id'] ?? '');
    $email = (string) ($fixture['email'] ?? '');
    if ($id <= 0 || !str_starts_with($employeeCode, 'SMK') || !str_starts_with($email, 'codex.employee.') || !str_ends_with($email, '@example.invalid')) {
        fwrite(STDERR, "Employee fixture identity validation failed; nothing was deleted.\n");
        exit(1);
    }

    $conn->begin_transaction();
    $stmt = $conn->prepare('DELETE ssc FROM salary_structure_components ssc INNER JOIN salary_structure ss ON ss.id=ssc.salary_structure_id WHERE ss.employee_id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    foreach (['salary_slips', 'payroll', 'salary_structure', 'leave_requests', 'leave_balance', 'attendance'] as $childTable) {
        $stmt = $conn->prepare("DELETE FROM {$childTable} WHERE employee_id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $conn->prepare('DELETE FROM attendance_adjustments WHERE employee_id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM employees WHERE id=? AND employee_id=? AND email=? AND full_name='Codex Employee Smoke Test'");
    $stmt->bind_param('iss', $id, $employeeCode, $email);
    $stmt->execute();
    $deleted = $stmt->affected_rows;
    $stmt->close();
    if ($deleted !== 1) {
        $conn->rollback();
        fwrite(STDERR, "Expected to delete exactly one employee fixture; deleted {$deleted}.\n");
        exit(1);
    }

    $conn->commit();
    unlink($employeeFixtureFile);
    echo "Employee browser smoke fixture removed.\n";
    exit;
}

if ($action === 'create') {
    if (is_file($fixtureFile)) {
        fwrite(STDERR, "A browser smoke fixture already exists. Clean it first.\n");
        exit(1);
    }

    $suffix = date('YmdHis') . bin2hex(random_bytes(3));
    $email = "codex.smoke.{$suffix}@example.invalid";
    $password = 'Smoke!' . bin2hex(random_bytes(8)) . 'Aa9';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $name = 'Codex Browser Smoke Test';
    $role = 'Super Admin';

    $stmt = $conn->prepare('INSERT INTO admin (name,email,password,role) VALUES (?,?,?,?)');
    $stmt->bind_param('ssss', $name, $email, $hash, $role);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();

    $fixture = ['admin_id' => $id, 'email' => $email, 'password' => $password];
    file_put_contents($fixtureFile, json_encode($fixture, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    echo json_encode($fixture, JSON_UNESCAPED_SLASHES), PHP_EOL;
    exit;
}

if ($action === 'cleanup') {
    if (!is_file($fixtureFile)) {
        echo "No browser smoke fixture exists.\n";
        exit;
    }

    $fixture = json_decode((string) file_get_contents($fixtureFile), true, 512, JSON_THROW_ON_ERROR);
    $id = (int) ($fixture['admin_id'] ?? 0);
    $email = (string) ($fixture['email'] ?? '');
    if ($id <= 0 || !str_starts_with($email, 'codex.smoke.') || !str_ends_with($email, '@example.invalid')) {
        fwrite(STDERR, "Fixture identity validation failed; nothing was deleted.\n");
        exit(1);
    }

    $stmt = $conn->prepare("DELETE FROM admin WHERE id=? AND email=? AND name='Codex Browser Smoke Test'");
    $stmt->bind_param('is', $id, $email);
    $stmt->execute();
    $deleted = $stmt->affected_rows;
    $stmt->close();
    if ($deleted !== 1) {
        fwrite(STDERR, "Expected to delete exactly one fixture account; deleted {$deleted}.\n");
        exit(1);
    }

    unlink($fixtureFile);
    echo "Browser smoke fixture removed.\n";
    exit;
}

fwrite(STDERR, "Usage: php tests/browser_smoke_fixture.php create|cleanup|create-employee|cleanup-employee\n");
exit(1);
