<?php
require_once('../config/security.php');
ems_start_secure_session();
include('admincheck_role.php');
include('../config/db.php');

if (!in_array($admin_role, ['Super Admin', 'Admin'], true)) {
    http_response_code(403);
    exit('You do not have permission to view the security audit log.');
}

$action = trim((string)($_GET['action'] ?? ''));
$actorType = (string)($_GET['actor_type'] ?? '');
$allowedActorTypes = ['Admin', 'Employee', 'System'];
if (!in_array($actorType, $allowedActorTypes, true)) $actorType = '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

$where = [];
$types = '';
$params = [];
if ($action !== '') {
    $where[] = 'action LIKE ?';
    $types .= 's';
    $params[] = '%' . $action . '%';
}
if ($actorType !== '') {
    $where[] = 'actor_type = ?';
    $types .= 's';
    $params[] = $actorType;
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$countStmt = $conn->prepare('SELECT COUNT(*) total FROM security_audit_log' . $whereSql);
if ($params) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$total = (int)$countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$query = 'SELECT * FROM security_audit_log' . $whereSql . ' ORDER BY id DESC LIMIT ? OFFSET ?';
$stmt = $conn->prepare($query);
$listTypes = $types . 'ii';
$listParams = [...$params, $perPage, $offset];
$stmt->bind_param($listTypes, ...$listParams);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$pages = max(1, (int)ceil($total / $perPage));

function auditQuery(array $changes): string {
    return http_build_query(array_merge($_GET, $changes));
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Security Audit Log - EMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
</head><body class="bg-light">
<main class="container-fluid py-4 px-lg-5">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div><h2 class="mb-1"><i class="fa fa-shield-halved text-primary"></i> Security Audit Log</h2><div class="text-muted">Read-only history of sensitive actions</div></div>
  <a href="dashboard.php" class="btn btn-outline-secondary"><i class="fa fa-arrow-left"></i> Dashboard</a>
</div>
<form method="get" class="card card-body border-0 shadow-sm mb-3"><div class="row g-2 align-items-end">
  <div class="col-md-5"><label class="form-label">Action contains</label><input name="action" class="form-control" maxlength="100" value="<?=htmlspecialchars($action)?>" placeholder="e.g. payroll.generated"></div>
  <div class="col-md-3"><label class="form-label">Actor type</label><select name="actor_type" class="form-select"><option value="">All</option><?php foreach($allowedActorTypes as $type): ?><option <?=$actorType===$type?'selected':''?>><?=htmlspecialchars($type)?></option><?php endforeach; ?></select></div>
  <div class="col-md-4"><button class="btn btn-primary"><i class="fa fa-filter"></i> Filter</button> <a href="security_audit.php" class="btn btn-outline-secondary">Reset</a></div>
</div></form>
<div class="card border-0 shadow-sm"><div class="card-header bg-white d-flex justify-content-between"><strong><?=number_format($total)?> events</strong><span class="text-muted">Page <?=$page?> of <?=$pages?></span></div>
<div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-light"><tr><th>Date</th><th>Actor</th><th>Action</th><th>Target</th><th>IP</th><th>Details</th></tr></thead><tbody>
<?php foreach($rows as $row): ?><tr>
<td class="text-nowrap"><?=htmlspecialchars($row['created_at'])?></td>
<td><strong><?=htmlspecialchars($row['actor_name'] ?: $row['actor_type'])?></strong><div class="small text-muted"><?=htmlspecialchars($row['actor_role'] ?: $row['actor_type'])?> #<?=htmlspecialchars((string)($row['actor_id'] ?? '—'))?></div></td>
<td><code><?=htmlspecialchars($row['action'])?></code></td>
<td><?=htmlspecialchars($row['target_type'] ?: '—')?><?=isset($row['target_id'])?' #'.(int)$row['target_id']:''?></td>
<td><?=htmlspecialchars($row['ip_address'] ?: '—')?></td>
<td><small><?=htmlspecialchars($row['details'] ?: '—')?></small></td>
</tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="6" class="text-center text-muted py-5">No audit events found.</td></tr><?php endif; ?>
</tbody></table></div></div>
<nav class="mt-3 d-flex justify-content-between">
  <a class="btn btn-outline-primary <?=$page<=1?'disabled':''?>" href="?<?=htmlspecialchars(auditQuery(['page'=>max(1,$page-1)]))?>">Previous</a>
  <a class="btn btn-outline-primary <?=$page>=$pages?'disabled':''?>" href="?<?=htmlspecialchars(auditQuery(['page'=>min($pages,$page+1)]))?>">Next</a>
</nav>
</main></body></html>
