<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/admincheck_role.php';

$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$admin_role = $_SESSION['admin_role'] ?? 'Admin';
$departments = mysqli_query($conn, "SELECT DISTINCT department FROM employees WHERE department IS NOT NULL AND department<>'' ORDER BY department");
$campaigns = mysqli_query($conn, "SELECT id,name FROM performance_campaigns WHERE is_active=1 ORDER BY name");
$filters = [
    'employee_code' => trim($_GET['employee_code'] ?? ''), 'employee_name' => trim($_GET['employee_name'] ?? ''),
    'department' => trim($_GET['department'] ?? ''), 'campaign_id' => (int)($_GET['campaign_id'] ?? 0),
    'adjustment_type' => trim($_GET['adjustment_type'] ?? ''), 'from_date' => trim($_GET['from_date'] ?? ''),
    'to_date' => trim($_GET['to_date'] ?? ''), 'status' => trim($_GET['status'] ?? ''),
];
$where = [];
if ($filters['employee_code'] !== '') $where[] = "sender.employee_id LIKE '%" . mysqli_real_escape_string($conn, $filters['employee_code']) . "%'";
if ($filters['employee_name'] !== '') $where[] = "sender.full_name LIKE '%" . mysqli_real_escape_string($conn, $filters['employee_name']) . "%'";
if ($filters['department'] !== '') $where[] = "sender.department='" . mysqli_real_escape_string($conn, $filters['department']) . "'";
if ($filters['campaign_id'] > 0) $where[] = 'm.campaign_id=' . $filters['campaign_id'];
if (in_array($filters['adjustment_type'], ['Login','Withdrawal'], true)) $where[] = "m.adjustment_type='" . $filters['adjustment_type'] . "'";
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['from_date'])) $where[] = "m.adjustment_date>='" . $filters['from_date'] . "'";
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['to_date'])) $where[] = "m.adjustment_date<='" . $filters['to_date'] . "'";
if (in_array($filters['status'], ['Pending','Approved','Rejected','Cancelled'], true)) $where[] = "m.status='" . $filters['status'] . "'";
$misAdjustments = [];
$tableCheck = $conn->query("SHOW TABLES LIKE 'mis_adjustments'");
if ($tableCheck && $tableCheck->num_rows) {
    $sql = "SELECT m.*,sender.full_name sender_name,sender.employee_id sender_code,sender.department,
                   receiver.full_name receiver_name,receiver.employee_id receiver_code,c.name campaign_name
            FROM mis_adjustments m
            JOIN employees sender ON sender.id=m.employee_id
            JOIN employees receiver ON receiver.id=m.recipient_id
            LEFT JOIN performance_campaigns c ON c.id=m.campaign_id" . ($where ? ' WHERE '.implode(' AND ', $where) : '') . " ORDER BY m.id DESC";
    $misAdjustments = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=UTF-8'); header('Content-Disposition: attachment; filename="mis-adjustments.csv"');
    echo "\xEF\xBB\xBF"; $out=fopen('php://output','w'); fputcsv($out,['Request No','Sender Code','Sender Name','Department','Receiver','Campaign','Date','Type','Time','Category','SubCategory','Reason','Status','Approver Comment','Requested At','Decided At']);
    foreach($misAdjustments as $row) fputcsv($out,[$row['request_no'],$row['sender_code'],$row['sender_name'],$row['department'],$row['receiver_name'],$row['campaign_name'],$row['adjustment_date'],$row['adjustment_type'],$row['adjustment_time'].' H:M:S',$row['reason_category'],$row['reason_subcategory'],$row['reason'],$row['status'],$row['approver_comment'],$row['created_at'],$row['decided_at']]);
    fclose($out); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MIS Adjustments - EMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="admin_panel.css" rel="stylesheet">
    <style>
        :root { --mis-primary:#2563eb; --mis-navy:#163a70; --mis-cyan:#06b6d4; --mis-border:#dbe4f0; --mis-soft:#f5f8fc; }
        .mis-shell { background:transparent; }
        .mis-title { position:relative; overflow:hidden; background:linear-gradient(125deg,var(--mis-navy),var(--mis-primary) 65%,var(--mis-cyan)); color:#fff; padding:24px 28px; border-radius:18px; font-size:23px; font-weight:700; box-shadow:0 12px 30px rgba(37,99,235,.18); }
        .mis-title::after { content:'MIS'; position:absolute; right:28px; top:50%; transform:translateY(-50%); font-size:58px; font-weight:800; opacity:.09; letter-spacing:6px; }
        .section-title { color:#172033; padding:0 0 15px; font-size:17px; font-weight:700; display:flex; align-items:center; gap:9px; }
        .section-title::before { content:''; width:5px; height:22px; background:linear-gradient(var(--mis-primary),var(--mis-cyan)); border-radius:5px; }
        .search-panel,.adjustment-section { background:#fff; border:1px solid var(--mis-border); border-radius:18px; box-shadow:0 7px 22px rgba(15,23,42,.06); }
        .search-panel { margin:20px 0; padding:22px; }
        .search-grid { display:grid; grid-template-columns:repeat(4,minmax(190px,1fr)); gap:16px; }
        .filter-item { display:flex; flex-direction:column; gap:7px; }
        .filter-item label { margin:0; color:#475569; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.35px; }
        .filter-item .form-control,.filter-item .form-select { min-height:42px; margin:0; width:100%; font-size:13px; border:1px solid #cfd9e7; border-radius:10px; background:var(--mis-soft); }
        .filter-item .form-control:focus,.filter-item .form-select:focus { background:#fff; border-color:var(--mis-primary); box-shadow:0 0 0 3px rgba(37,99,235,.12); }
        .search-actions { display:flex; justify-content:flex-end; gap:10px; padding-top:20px; margin-top:20px; border-top:1px solid #e8edf5; }
        .search-actions .btn { min-width:105px; min-height:40px; border-radius:10px; font-weight:600; }
        .btn-mis { background:var(--mis-primary); border-color:var(--mis-primary); color:#fff; box-shadow:0 5px 14px rgba(37,99,235,.2); }
        .btn-mis:hover { background:#1d4ed8; border-color:#1d4ed8; color:#fff; }
        .adjustment-section { margin:0; padding:22px; }
        .table-responsive { border:1px solid var(--mis-border); border-radius:12px; }
        .mis-table { min-width:2050px; margin:0; }
        .mis-table thead th { background:#eef3fa; color:#334155; border:0; border-right:1px solid #dce5f1; border-bottom:2px solid #cbd8e8; padding:13px 8px; font-size:11px; font-weight:700; text-align:center; vertical-align:middle; text-transform:uppercase; letter-spacing:.25px; white-space:normal; }
        .mis-table tbody td { border:0; border-right:1px solid #edf1f6; border-bottom:1px solid #e7edf5; padding:11px 8px; font-size:12px; text-align:center; vertical-align:middle; }
        .mis-table tbody tr:nth-child(even){background:#f8fafc}.mis-table tbody tr:hover{background:#eff6ff}
        .empty-row td { padding:58px 16px!important; color:#64748b; font-size:14px!important; background:#fff; }
        .empty-row i { display:block; font-size:34px; color:#a9b8ca; margin:0 0 10px!important; }
        body.dark-mode .search-panel,body.dark-mode .adjustment-section { background:#1e293b; border-color:rgba(255,255,255,.08); }
        body.dark-mode .section-title { color:#e2e8f0; } body.dark-mode .filter-item label,body.dark-mode .empty-row td { color:#cbd5e1; }
        body.dark-mode .filter-item .form-control,body.dark-mode .filter-item .form-select { background:#172033; color:#e2e8f0; border-color:#39475c; }
        body.dark-mode .mis-table thead th { background:#172033; color:#cbd5e1; border-color:#39475c; }
        body.dark-mode .empty-row td { background:#1e293b; }
        @media(max-width:1200px){.search-grid{grid-template-columns:repeat(2,minmax(240px,1fr));}}
        @media(max-width:700px){.search-grid{grid-template-columns:1fr}.search-actions{flex-wrap:wrap}.search-actions .btn{flex:1}.mis-title{font-size:19px;padding:20px}.mis-title::after{font-size:40px}.search-panel,.adjustment-section{padding:16px}}
    </style>
</head>
<body>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand"><div class="brand-icon"><i class="fa fa-building"></i></div><div class="brand-text">EMS<small>Employee Management</small></div></div>
    <div class="sidebar-user"><div class="user-avatar"><?=htmlspecialchars(strtoupper(substr($admin_name, 0, 1)))?></div><div class="user-info"><div class="user-name"><?=htmlspecialchars($admin_name)?></div><div class="user-role"><?=htmlspecialchars($admin_role)?></div></div></div>
    <nav class="sidebar-nav">
        <div class="sidebar-section-title">Main</div>
        <div class="sidebar-section-group">
            <a href="dashboard.php" class="sidebar-link"><i class="fa fa-gauge"></i> Dashboard</a>
            <a href="employee.php" class="sidebar-link"><i class="fa fa-users"></i> Employees</a>
            <a href="add_employee.php" class="sidebar-link"><i class="fa fa-user-plus"></i> Add Employee</a>
            <a href="employee_rights_management.php" class="sidebar-link"><i class="fa fa-user-shield"></i> Employee Rights</a>
            <a href="requisitions.php" class="sidebar-link"><i class="fa fa-file-circle-plus"></i> Requisitions</a>
            <a href="leave_requests.php" class="sidebar-link"><i class="fa fa-calendar-check"></i> Leave Requests</a>
            <a href="supervisor_adjustments.php" class="sidebar-link"><i class="fa fa-user-tie"></i> Supervisor Adjustments</a>
            <a href="admin_adjustments.php" class="sidebar-link"><i class="fa fa-shield-alt"></i> Admin Adjustments</a>
            <a href="manage_shifts.php" class="sidebar-link"><i class="fa fa-clock-rotate-left"></i> Manage Shifts</a>
            <a href="attendance_report.php" class="sidebar-link"><i class="fa fa-clock"></i> Attendance</a>
            <a href="reports.php" class="sidebar-link"><i class="fa fa-chart-column"></i> Reports</a>
        </div>
        <div class="sidebar-section-title">System</div>
        <div class="sidebar-section-group">
            <a href="change_password.php" class="sidebar-link"><i class="fa fa-key"></i> Change Password</a>
            <a href="logout.php" class="sidebar-link"><i class="fa fa-right-from-bracket"></i> Logout</a>
        </div>
    </nav>
</aside>

<div class="main-content" id="mainContent">
    <header class="header">
        <div class="header-left"><button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><i class="fa fa-bars"></i></button><h4>MIS Adjustments</h4></div>
        <div class="header-right"><span class="header-date"><i class="fa-regular fa-calendar"></i> <?=date('d M Y')?></span><span class="header-admin-badge"><i class="fa fa-user-shield"></i> <?=htmlspecialchars($admin_name)?></span><?php $darkModeInTopbar = true; include __DIR__ . '/../dark_mode.php'; ?><a href="logout.php" class="btn btn-danger btn-sm rounded-pill px-3"><i class="fa fa-right-from-bracket"></i> Logout</a></div>
    </header>
    <div class="page-content">
        <div class="mis-shell">
            <div class="mis-title">View Adjustments</div>
            <form id="misSearchForm" class="search-panel" method="get">
                <div class="section-title">Search &amp; Filter Adjustments</div>
                <div class="search-grid">
                    <div class="filter-item"><label for="employeeCode">Employee Code</label><input id="employeeCode" name="employee_code" value="<?=htmlspecialchars($filters['employee_code'])?>" class="form-control form-control-sm" type="text"></div>
                    <div class="filter-item"><label for="department">Department</label><select id="department" name="department" class="form-select form-select-sm"><option value="">All Departments</option><?php if($departments): while($d=mysqli_fetch_assoc($departments)): ?><option value="<?=htmlspecialchars($d['department'])?>" <?=$filters['department']===$d['department']?'selected':''?>><?=htmlspecialchars($d['department'])?></option><?php endwhile; endif; ?></select></div>
                    <div class="filter-item"><label for="campaign">Campaign</label><select id="campaign" name="campaign_id" class="form-select form-select-sm"><option value="">All Campaigns</option><?php if($campaigns): while($c=mysqli_fetch_assoc($campaigns)): ?><option value="<?=$c['id']?>" <?=$filters['campaign_id']==(int)$c['id']?'selected':''?>><?=htmlspecialchars($c['name'])?></option><?php endwhile; endif; ?></select></div>
                    <div class="filter-item"><label for="adjustmentType">Adjustment Type</label><select id="adjustmentType" name="adjustment_type" class="form-select form-select-sm"><option value="">All Types</option><option <?=$filters['adjustment_type']==='Login'?'selected':''?>>Login</option><option <?=$filters['adjustment_type']==='Withdrawal'?'selected':''?>>Withdrawal</option></select></div>
                    <div class="filter-item"><label for="employeeName">Employee Name</label><input id="employeeName" name="employee_name" value="<?=htmlspecialchars($filters['employee_name'])?>" class="form-control form-control-sm" type="text"></div>
                    <div class="filter-item"><label for="fromDate">From Date</label><input id="fromDate" name="from_date" class="form-control form-control-sm" type="date" value="<?=htmlspecialchars($filters['from_date'])?>"></div>
                    <div class="filter-item"><label for="toDate">To Date</label><input id="toDate" name="to_date" class="form-control form-control-sm" type="date" value="<?=htmlspecialchars($filters['to_date'])?>"></div>
                    <div class="filter-item"><label for="status">Status</label><select id="status" name="status" class="form-select form-select-sm"><option value="">All Statuses</option><?php foreach(['Pending','Approved','Rejected','Cancelled'] as $status):?><option <?=$filters['status']===$status?'selected':''?>><?=$status?></option><?php endforeach;?></select></div>
                </div>
                <div class="search-actions"><button type="submit" class="btn btn-sm btn-mis px-4"><i class="fa fa-magnifying-glass me-1"></i> Search</button><a href="mis_adjustment.php" class="btn btn-sm btn-outline-secondary px-4"><i class="fa fa-rotate-left me-1"></i> Reset</a><button type="submit" name="export" value="1" class="btn btn-sm btn-light border px-4"><i class="fa fa-file-export me-1"></i> Export</button></div>
            </form>

            <section class="adjustment-section">
                <div class="section-title">All MIS Adjustments (<?=count($misAdjustments)?>)</div>
                <div class="table-responsive">
                    <table class="table mis-table">
                        <thead><tr><th>Request No</th><th>Sender</th><th>Employee Code</th><th>Department</th><th>Sent To</th><th>Campaign</th><th>Adjustment Date</th><th>Type</th><th>Duration (H:M:S)</th><th>Reason Category</th><th>SubCategory</th><th>Reason</th><th>Requested Date</th><th>Decision Date</th><th>Status</th><th>Approver Comment</th></tr></thead>
                        <tbody><?php foreach($misAdjustments as $row):?><tr><td><strong><?=htmlspecialchars($row['request_no'])?></strong></td><td><?=htmlspecialchars($row['sender_name'])?></td><td><?=htmlspecialchars($row['sender_code'])?></td><td><?=htmlspecialchars($row['department'])?></td><td><?=htmlspecialchars($row['receiver_name'])?><div class="text-muted"><?=htmlspecialchars($row['receiver_code'])?></div></td><td><?=htmlspecialchars($row['campaign_name']??'-')?></td><td><?=date('d M Y',strtotime($row['adjustment_date']))?></td><td><?=htmlspecialchars($row['adjustment_type'])?></td><td><strong><?=htmlspecialchars($row['adjustment_time'])?></strong></td><td><?=htmlspecialchars($row['reason_category'])?></td><td><?=htmlspecialchars($row['reason_subcategory']?:'-')?></td><td style="min-width:220px;text-align:left"><?=htmlspecialchars($row['reason'])?></td><td><?=date('d M Y h:i A',strtotime($row['created_at']))?></td><td><?=$row['decided_at']?date('d M Y h:i A',strtotime($row['decided_at'])):'-'?></td><td><span class="badge text-bg-<?=$row['status']==='Approved'?'success':($row['status']==='Rejected'?'danger':($row['status']==='Cancelled'?'secondary':'warning'))?>"><?=htmlspecialchars($row['status'])?></span></td><td><?=htmlspecialchars($row['approver_comment']?:'-')?></td></tr><?php endforeach;?><?php if(!$misAdjustments):?><tr class="empty-row"><td colspan="16"><i class="fa fa-inbox me-2"></i>No MIS adjustments available.</td></tr><?php endif;?></tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</div>
<script>
const sidebar = document.getElementById('sidebar');
const sidebarBackdrop = document.getElementById('sidebarBackdrop');
document.getElementById('sidebarToggle').addEventListener('click', function () {
    if (window.innerWidth <= 991) { sidebar.classList.toggle('open'); sidebarBackdrop.classList.toggle('show'); }
    else { document.body.classList.toggle('sidebar-collapsed'); }
});
sidebarBackdrop.addEventListener('click', function () { sidebar.classList.remove('open'); sidebarBackdrop.classList.remove('show'); });
</script>
</body>
</html>
