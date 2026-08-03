<?php

function ems_login_identity_hash(string $email): string
{
    return hash('sha256', strtolower(trim($email)));
}

function ems_request_ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
}

function ems_db_login_is_limited(mysqli $conn, string $email): bool
{
    $emailHash = ems_login_identity_hash($email);
    $ip = ems_request_ip();
    $stmt = $conn->prepare("SELECT
        SUM(email_hash=? AND resolved_at IS NULL AND attempted_at >= NOW() - INTERVAL 15 MINUTE) email_failures,
        SUM(ip_address=? AND resolved_at IS NULL AND attempted_at >= NOW() - INTERVAL 15 MINUTE) ip_failures
        FROM auth_login_attempts
        WHERE attempted_at >= NOW() - INTERVAL 15 MINUTE AND (email_hash=? OR ip_address=?)");
    $stmt->bind_param('ssss', $emailHash, $ip, $emailHash, $ip);
    $stmt->execute();
    $counts = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($counts['email_failures'] ?? 0) >= 5 || (int)($counts['ip_failures'] ?? 0) >= 20;
}

function ems_db_record_failed_login(mysqli $conn, string $email): void
{
    $emailHash = ems_login_identity_hash($email);
    $ip = ems_request_ip();
    $agentHash = hash('sha256', (string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
    $stmt = $conn->prepare('INSERT INTO auth_login_attempts (email_hash,ip_address,user_agent_hash) VALUES (?,?,?)');
    $stmt->bind_param('sss', $emailHash, $ip, $agentHash);
    $stmt->execute();
    $stmt->close();
}

function ems_db_resolve_login_attempts(mysqli $conn, string $email): void
{
    $emailHash = ems_login_identity_hash($email);
    $stmt = $conn->prepare('UPDATE auth_login_attempts SET resolved_at=NOW() WHERE email_hash=? AND resolved_at IS NULL');
    $stmt->bind_param('s', $emailHash);
    $stmt->execute();
    $stmt->close();
}

