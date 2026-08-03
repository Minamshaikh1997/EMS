<?php

function ems_audit(mysqli $conn, string $action, ?string $targetType = null, ?int $targetId = null, array $details = []): bool
{
    $actorType = isset($_SESSION['admin_id']) ? 'Admin' : (isset($_SESSION['employee_id']) ? 'Employee' : 'System');
    $actorId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : (isset($_SESSION['employee_id']) ? (int)$_SESSION['employee_id'] : null);
    $actorName = (string)($_SESSION['admin_name'] ?? $_SESSION['employee_name'] ?? 'System');
    $actorRole = (string)($_SESSION['admin_role'] ?? $_SESSION['employee_role'] ?? $actorType);
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    $json = $details === [] ? null : json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    try {
        $stmt = $conn->prepare('INSERT INTO security_audit_log (actor_type,actor_id,actor_name,actor_role,action,target_type,target_id,details,ip_address) VALUES (?,?,?,?,?,?,?,?,?)');
        $stmt->bind_param('sissssiss', $actorType, $actorId, $actorName, $actorRole, $action, $targetType, $targetId, $json, $ip);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    } catch (Throwable $error) {
        error_log('EMS audit log failure: ' . $error->getMessage());
        return false;
    }
}

