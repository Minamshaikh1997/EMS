<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.html");
    exit();
}

include("admincheck_role.php");

$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$admin_role = $_SESSION['admin_role'] ?? '';
$admin_id = $_SESSION['admin_id'] ?? 0;

// Process supervisor action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['adj_id'])) {
    $adj_id = intval($_POST['adj_id']);
    $comment = mysqli_real_escape_string($conn, $_POST['comment'] ?? '');
    $action = $_POST['action'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    $adj = mysqli_query($conn, "SELECT * FROM attendance_adjustments WHERE id='$adj_id'");
    if (mysqli_num_rows($adj) === 0) {
        $error = "Adjustment request not found.";
    } else {
        $adj_row = mysqli_fetch_assoc($adj);
        
        if ($action === 'approve') {
            $new_status = 'Approved';
            $action_desc = 'Approved';
            $emp_id = $adj_row['employee_id'];
            $att_date = $adj_row['attendance_date'];
            $check_in = $adj_row['requested_check_in'];
            $check_out = $adj_row['requested_check_out'];
            
            $existing_att = mysqli_query($conn, "SELECT id FROM attendance WHERE employee_id='$emp_id' AND attendance_date='$att_date' LIMIT 1");
            if (mysqli_num_rows($existing_att) > 0) {
                $att_row = mysqli_fetch_assoc($existing_att);
                $att_id = $att_row['id'];
                $update_fields = [];
                if ($check_in) $update_fields[] = "check_in='$check_in'";
                if ($check_out) $update_fields[] = "check_out='$check_out'";
                if (!empty($update_fields)) {
                    $update_fields[] = "status='Present'";
                    mysqli_query($conn, "UPDATE attendance SET " . implode(',', $update_fields) . " WHERE id='$att_id'");
                }
            } else {
                $in_time = $check_in ? "'$check_in'" : "'09:00:00'";
                $out_time = $check_out ? "'$check_out'" : "'17:00:00'";
                mysqli_query($conn, "INSERT INTO attendance (employee_id, attendance_date, check_in, check_out, status) 
                    VALUES ('$emp_id', '$att_date', $in_time, $out_time, 'Present')");
            }
        } elseif ($action === 'reject') {
            $new_status = 'Rejected';
            $action_desc = 'Rejected';
        } elseif ($action === 'hold') {
            $new_status = 'Hold';
            $action_desc = 'Placed on Hold';
        } else {
            $error = "Invalid action.";
        }
        
        if (!isset($error)) {
            mysqli_query($conn, "UPDATE attendance_adjustments SET 
                status='$new_status', 
                supervisor_status='$new_status',
                supervisor_comment='$comment',
                supervisor_id='$admin_id',
                supervisor_date=NOW()
                WHERE id='$adj_id'");
            
            mysqli_query($conn, "INSERT INTO audit_log (action_type, description, performed_by, performed_by_role, target_id, target_type, ip_address) 
                VALUES ('Supervisor $action_desc Adjustment', 'Supervisor $action_desc adjustment request #$adj_id', '$admin_name', 'Supervisor', '$adj_id', 'attendance_adjustments', '$ip')");
            
            $success = "Adjustment request $action_desc successfully.";
        }
    }
}

// Get pending adjustments
$pending_adjustments = mysqli_query($conn, "
    SELECT a.*, e.full_name, e.department, e.employee_id AS emp_code
    FROM attendance_adjustments a
    INNER JOIN employees e ON a.employee_id = e.id
    WHERE a.status = 'Pending' AND a.supervisor_status = 'Pending'
    ORDER BY a.id DESC
");

// Get all adjustments
$all_adjustments = mysqli_query($conn, "
    SELECT a.*, e.full_name, e.department, e.employee_id AS emp_code
    FROM attendance_adjustments a
    INNER JOIN employees e ON a.employee_id = e.id
    ORDER BY a.id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Supervisor Adjustments - EMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="admin_panel.css" rel="stylesheet">
<style>
:root {
    --primary: #2563eb; --primary-dark: #1d4ed8; --success: #10b981; --warning: #f59e0b; --danger: #ef4444; --info: #06b6d4;
    --gray-50: #f8fafc; --gray-100: #f1f5f9; --gray-200: #e2e8f0; --gray-300: #cbd5e1; --gray-400: #94a3b8; --gray-500: #64748b;
    --gray-600: #475569; --gray-700: #334155; --gray-800: #1e293b; --gray-900: #0f172a;
    --radius: 16px; --radius-sm: 10px;
    --shadow: 0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.06);
    --shadow-md: 0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -1px rgba(0,0,0,.06);
}
body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: var(--gray-50); color: var(--gray-800); overflow-x: hidden; }
.table-modern { margin-bottom: 0; }
.table-modern thead th { font-size: 11px; font-weight: 700; color: var(--gray-500); text-transform: uppercase; letter-spacing: .5px; padding: 12px 16px; border-bottom: 2px solid var(--gray-200); background: var(--gray-50); white-space: nowrap; }
.table-modern tbody td { padding: 12px 16px; font-size: 13px; color: var(--gray-700); border-bottom: 1px solid var(--gray-100); vertical-align: middle; }
.table-modern tbody tr:hover { background: var(--gray-50); }
.table-modern tbody tr:last-child td { border-bottom: none; }
.badge-modern { padding: 4px 12px; border-radius: 100px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; }
.badge-approved { background: rgba(16,185,129,.12); color: #059669; }
.badge-pending { background: rgba(245,158,11,.12); color: #d97706; }
.badge-rejected { background: rgba(239,68,68,.12); color: #dc2626; }
.badge-hold { background: rgba(251,146,60,.12); color: #ea580c; }
.card-modern { background: white; border-radius: var(--radius); border: 1px solid var(--gray-200); box-shadow: var(--shadow); overflow: hidden; }
.card-modern .card-header-custom { padding: 16px 24px; border-bottom: 1px solid var(--gray-200); display: flex; align-items: center; justify-content: space-between; background: var(--gray-50); flex-wrap: wrap; gap: 12px; }
.card-modern .card-header-custom h5 { font-size: 16px; font-weight: 700; color: var(--gray-800); margin: 0; display: flex; align-items: center; gap: 8px; }
.card-modern .card-header-custom h5 i { color: var(--primary); }
.card-modern .card-body-custom { padding: 20px; }
.comment-box { background: var(--gray-50); border-radius: var(--radius-sm); padding: 12px; border: 1px solid var(--gray-200); }
.comment-box textarea { border: none; background: transparent; resize: vertical; width: 100%; outline: none; font-size: 13px; }
.empty-state { text-align: center; padding: 60px 20px; color: var(--gray-400); }
.empty-state i { font-size: 64px; color: var(--gray-300); margin-bottom: 20px; display: block; }
.emp-info { font-weight: 600; color: var(--gray-800); }
.emp-dept { font-size: 12px; color: var(--gray-500); }
.nav-tabs .nav-link { font-weight: 600; font-size: 13px; color: var(--gray-600); border: none; padding: 10px 20px; }
.nav-tabs .nav-link.active { color: var(--primary); border-bottom: 2px solid var(--primary); background: transparent; }
body.dark-mode { background: #0f172a; color: #e2e8f0; }
.dark-mode .card-modern { background: #1e293b; border-color: rgba(255,255,255,.08); }
.dark-mode .card-modern .card-header-custom { background: rgba(255,255,255,.04); border-color: rgba(255,255,255,.08); }
.dark-mode .card-modern .card-header-custom h5 { color: #e2e8f0; }
.dark-mode .table-modern thead th { color: var(--gray-400); border-color: rgba(255,255,255,.08); background: rgba(255,255,255,.04); }
.dark-mode .table-modern tbody td { color: #cbd5e1; border-color: rgba(255,255,255,.06); }
.dark-mode .table-modern tbody tr:hover { background: rgba(255,255,255,.04); }
.dark-mode .emp-info { color: #e2e8f0; }
.dark-mode .emp-dept { color: var(--gray-400); }
.dark-mode .comment-box { background: rgba(255,255,255,.04); border-color: rgba(255,255,255,.1); }
.dark-mode .comment-box textarea { color: #e2e8f0; }
.dark-mode .comment-box textarea::placeholder { color: var(--gray-500); }
.dark-mode .empty-state i { color: var(--gray-600); }
.dark-mode .nav-tabs .nav-link { color: var(--gray-400); }
.dark-mode .nav-tabs .nav-link.active { color: var(--primary-light); }
@media (max-width: 768px) { .page-content { padding: 16px; } }
</style>
</head>
<body>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="fa-solid fa-building"></i></div>
        <div class="brand-text">EMS <small>Employee Management</small></div>
    </div>
    <div class="sidebar-user">
        <div class="user-avatar"><?php echo strtoupper(substr($admin_name, 0, 1)); ?></div>
        <div class="user-info">
            <div class="user-name"><?php echo htmlspecialchars($admin_name); ?></div>
            <div class="user-role"><?php echo htmlspecialchars($admin_role ?: 'Administrator'); ?></div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="sidebar-section-title">Main</div>
        <a href="dashboard.php" class="sidebar-link"><i class="fa fa-gauge"></i> Dashboard</a>
        <a href="employee.php" class="sidebar-link"><i class="fa fa-users"></i> Employees</a>
        <a href="add_employee.php" class="sidebar-link"><i class="fa fa-user-plus"></i> Add Employee</a>
        <a href="leave_requests.php" class="sidebar-link"><i class="fa fa-calendar-check"></i> Leave Requests</a>
        <a href="supervisor_adjustments.php" class="sidebar-link active"><i class="fa fa-user-tie"></i> Supervisor Adjustments</a>
        <a href="admin_adjustments.php" class="sidebar-link"><i class="fa fa-shield-alt"></i> Admin Adjustments</a>
        <a href="manage_shifts.php" class="sidebar-link"><i class="fa fa-clock-rotate-left"></i> Manage Shifts</a>
        <a href="attendance_report.php" class="sidebar-link"><i class="fa fa-clock"></i> Attendance</a>
        <a href="reports.php" class="sidebar-link"><i class="fa fa-chart-column"></i> Reports</a>
        <div class="sidebar-section-title">Payroll</div>
        <a href="payroll_dashboard.php" class="sidebar-link"><i class="fa-solid fa-money-bill-wave"></i> Payroll Dashboard</a>
        <a href="generate_payroll.php" class="sidebar-link"><i class="fa fa-file-invoice-dollar"></i> Generate Payroll</a>
        <a href="payroll_history.php" class="sidebar-link"><i class="fa fa-clock-rotate-left"></i> Payroll History</a>
        <a href="salary_components.php" class="sidebar-link"><i class="fa-solid fa-wallet"></i> Salary Components</a>
        <a href="salary_slips.php" class="sidebar-link"><i class="fa-solid fa-file-pdf"></i> Salary Slips</a>
        <a href="payroll_reports.php" class="sidebar-link"><i class="fa-solid fa-chart-line"></i> Payroll Reports</a>
        <a href="salary_structure.php" class="sidebar-link"><i class="fa fa-money-bill-wave"></i> Salary Structure</a>
        <a href="monthly_payroll.php" class="sidebar-link"><i class="fa fa-calendar"></i> Monthly Payroll</a>
        <div class="sidebar-section-title">System</div>
        <a href="add_notice.php" class="sidebar-link"><i class="fa fa-bullhorn"></i> Notices</a>
        <a href="add_holiday.php" class="sidebar-link"><i class="fa fa-plane"></i> Holidays</a>
        <a href="change_password.php" class="sidebar-link"><i class="fa fa-key"></i> Change Password</a>
        <a href="logout.php" class="sidebar-link"><i class="fa fa-right-from-bracket"></i> Logout</a>
    </nav>
</aside>

<div class="main-content" id="mainContent">
    <header class="header">
        <div class="header-left">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><i class="fa fa-bars"></i></button>
            <h4>Supervisor Adjustments <span>/ Attendance Adjustments</span></h4>
        </div>
        <div class="header-right">
            <span class="header-date"><i class="fa-regular fa-calendar"></i> <?=date('d M Y')?></span>
            <span class="header-admin-badge"><i class="fa fa-user-shield"></i> <span><?php echo htmlspecialchars($admin_name); ?></span></span>
            <?php $darkModeInTopbar = true; include("../dark_mode.php"); ?>
            <a href="logout.php" class="btn btn-danger btn-sm rounded-pill px-3"><i class="fa fa-right-from-bracket"></i> <span>Logout</span></a>
        </div>
    </header>

    <div class="page-content">
        <div class="card-modern">
            <div class="card-header-custom">
                <h5><i class="fa fa-user-tie"></i> Supervisor - Attendance Adjustments</h5>
                <small class="text-muted">Review and manage team adjustment requests</small>
            </div>
            <div class="card-body-custom">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show rounded-pill px-4"><?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show rounded-pill px-4"><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>
                
                <ul class="nav nav-tabs mb-4" id="statusTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">Pending Review</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">All Requests</button>
                    </li>
                </ul>
                
                <div class="tab-content">
                    <!-- Pending Tab -->
                    <div class="tab-pane fade show active" id="pending" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-modern">
                                <thead>
                                    <tr>
                                        <th>Request #</th>
                                        <th>Employee</th>
                                        <th>Attendance Date</th>
                                        <th>Type</th>
                                        <th>Requested Times</th>
                                        <th>Reason</th>
                                        <th>Attachment</th>
                                        <th>Comments & Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if ($pending_adjustments && mysqli_num_rows($pending_adjustments) > 0):
                                        while ($row = mysqli_fetch_assoc($pending_adjustments)):
                                    ?>
                                    <tr>
                                        <form method="POST">
                                            <input type="hidden" name="adj_id" value="<?php echo $row['id']; ?>">
                                            <td><strong><?php echo htmlspecialchars($row['request_no']); ?></strong></td>
                                            <td>
                                                <div class="emp-info"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                                <div class="emp-dept"><?php echo htmlspecialchars($row['department']); ?> (<?php echo htmlspecialchars($row['emp_code']); ?>)</div>
                                            </td>
                                            <td><?php echo date('d-m-Y', strtotime($row['attendance_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['adjustment_type']); ?></td>
                                            <td style="white-space: nowrap;">
                                                <?php 
                                                if ($row['requested_check_in']) echo 'In: ' . date('h:i A', strtotime($row['requested_check_in'])) . '<br>';
                                                if ($row['requested_check_out']) echo 'Out: ' . date('h:i A', strtotime($row['requested_check_out']));
                                                if (!$row['requested_check_in'] && !$row['requested_check_out']) echo '—';
                                                ?>
                                            </td>
                                            <td style="max-width: 200px;">
                                                <div style="max-height: 60px; overflow-y: auto; font-size: 13px;"><?php echo htmlspecialchars($row['reason']); ?></div>
                                            </td>
                                            <td>
                                                <?php if (!empty($row['attachment'])): ?>
                                                    <a href="../uploads/adjustments/<?php echo $row['attachment']; ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill"><i class="fas fa-paperclip"></i> View</a>
                                                <?php else: ?>
                                                    <span class="text-muted small">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="min-width: 280px;">
                                                <div class="comment-box mb-2">
                                                    <textarea name="comment" rows="2" placeholder="Add your comment..." class="form-control"></textarea>
                                                </div>
                                                <div class="d-flex gap-1 flex-wrap">
                                                    <button type="submit" name="action" value="approve" class="btn btn-success btn-sm rounded-pill px-3" onclick="return confirm('Approve this request? Attendance will be updated automatically.')"><i class="fas fa-check"></i> Approve</button>
                                                    <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm rounded-pill px-3" onclick="return confirm('Reject this request?')"><i class="fas fa-times"></i> Reject</button>
                                                    <button type="submit" name="action" value="hold" class="btn btn-warning btn-sm rounded-pill px-3" onclick="return confirm('Place this request on hold?')"><i class="fas fa-pause"></i> Hold</button>
                                                </div>
                                            </td>
                                        </form>
                                    </tr>
                                    <?php 
                                        endwhile;
                                    else: 
                                    ?>
                                    <tr>
                                        <td colspan="8">
                                            <div class="empty-state">
                                                <i class="fas fa-check-circle"></i>
                                                <h5>No Pending Requests</h5>
                                                <p class="text-muted">All adjustment requests have been reviewed.</p>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- All Requests Tab -->
                    <div class="tab-pane fade" id="all" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-modern">
                                <thead>
                                    <tr>
                                        <th>Request #</th>
                                        <th>Employee</th>
                                        <th>Date</th>
                                        <th>Attendance Date</th>
                                        <th>Type</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th>Supervisor Comment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if ($all_adjustments && mysqli_num_rows($all_adjustments) > 0):
                                        while ($row = mysqli_fetch_assoc($all_adjustments)):
                                            $statusClass = 'badge-pending';
                                            if ($row['status'] === 'Approved') $statusClass = 'badge-approved';
                                            elseif ($row['status'] === 'Rejected') $statusClass = 'badge-rejected';
                                            elseif ($row['status'] === 'Hold') $statusClass = 'badge-hold';
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['request_no']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                        <td><?php echo date('d-m-Y', strtotime($row['created_at'])); ?></td>
                                        <td><?php echo date('d-m-Y', strtotime($row['attendance_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['adjustment_type']); ?></td>
                                        <td style="max-width: 150px;"><div class="text-truncate"><?php echo htmlspecialchars($row['reason']); ?></div></td>
                                        <td><span class="badge-modern <?php echo $statusClass; ?>"><?php echo $row['status']; ?></span></td>
                                        <td style="max-width: 200px;">
                                            <?php if (!empty($row['supervisor_comment'])): ?>
                                                <div class="small"><?php echo htmlspecialchars($row['supervisor_comment']); ?></div>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php 
                                        endwhile;
                                    else: 
                                    ?>
                                    <tr>
                                        <td colspan="8">
                                            <div class="empty-state">
                                                <i class="fas fa-inbox"></i>
                                                <h5>No Requests Found</h5>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebarBackdrop = document.getElementById('sidebarBackdrop');
sidebarToggle.addEventListener('click', function() {
    const isMobile = window.matchMedia('(max-width: 991px)').matches;
    if (isMobile) { sidebar.classList.toggle('open'); sidebarBackdrop.classList.toggle('show', isOpen); }
    else { document.body.classList.toggle('sidebar-collapsed'); }
});
sidebarBackdrop.addEventListener('click', function() { sidebar.classList.remove('open'); sidebarBackdrop.classList.remove('show'); });
</script>
</body>
</html>