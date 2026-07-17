<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.html");
    exit();
}

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
<html>
<head>
<title>Supervisor - Adjustment Requests</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    body { background: #f4f7fc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 30px 0; }
    .container { max-width: 1400px; }
    .card { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); overflow: hidden; }
    .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 20px 25px; }
    .card-header h4 { margin: 0; font-weight: 700; }
    .card-body { padding: 25px; }
    .table { margin-bottom: 0; }
    .table thead { background: #f8f9ff; }
    .table thead th { border: none; font-weight: 600; color: #2d3748; padding: 15px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
    .table tbody td { padding: 12px 15px; border-color: #f0f0f0; vertical-align: middle; font-size: 14px; }
    .table tbody tr:hover { background: #f8f9ff; }
    .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .badge-pending { background: #fff9c4; color: #f57f17; }
    .badge-approved { background: #c8e6c9; color: #2e7d32; }
    .badge-rejected { background: #ffcdd2; color: #c62828; }
    .badge-hold { background: #ffe0b2; color: #e65100; }
    .comment-box { background: #f7fafc; border-radius: 10px; padding: 12px; border: 1px solid #e2e8f0; }
    .comment-box textarea { border: none; background: transparent; resize: vertical; width: 100%; outline: none; }
    .btn-action { border-radius: 8px; padding: 8px 16px; font-weight: 600; font-size: 13px; transition: all 0.2s; }
    .btn-action:hover { transform: translateY(-1px); }
    .empty-state { text-align: center; padding: 60px 20px; color: #718096; }
    .empty-state i { font-size: 64px; color: #cbd5e0; margin-bottom: 20px; display: block; }
    .alert { border-radius: 10px; }
    .action-group { display: flex; gap: 5px; flex-wrap: nowrap; }
    .emp-info { font-weight: 600; color: #2d3748; }
    .emp-dept { font-size: 12px; color: #718096; }
    .sidebar-link { display: inline-block; padding: 8px 16px; background: rgba(255,255,255,0.2); border-radius: 8px; color: white; text-decoration: none; font-size: 14px; }
    .sidebar-link:hover { background: rgba(255,255,255,0.3); color: white; }
</style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h4><i class="fas fa-user-tie"></i> Supervisor - Attendance Adjustments</h4>
                <small>Review and manage team adjustment requests</small>
            </div>
            <div>
                <a href="dashboard.php" class="sidebar-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>
        </div>
        <div class="card-body">
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
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
                        <table class="table">
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
                                            <div style="max-height: 60px; overflow-y: auto;"><?php echo htmlspecialchars($row['reason']); ?></div>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['attachment'])): ?>
                                                <a href="../uploads/adjustments/<?php echo $row['attachment']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-paperclip"></i> View
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="min-width: 280px;">
                                            <div class="comment-box mb-2">
                                                <textarea name="comment" class="form-control" rows="2" placeholder="Add your comment..."></textarea>
                                            </div>
                                            <div class="action-group">
                                                <button type="submit" name="action" value="approve" class="btn btn-success btn-action btn-sm" onclick="return confirm('Approve this request? Attendance will be updated automatically.')">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                                <button type="submit" name="action" value="reject" class="btn btn-danger btn-action btn-sm" onclick="return confirm('Reject this request?')">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                                <button type="submit" name="action" value="hold" class="btn btn-warning btn-action btn-sm" onclick="return confirm('Place this request on hold?')">
                                                    <i class="fas fa-pause"></i> Hold
                                                </button>
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
                                            <p>All adjustment requests have been reviewed.</p>
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
                        <table class="table">
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
                                    <td><span class="badge <?php echo $statusClass; ?>"><?php echo $row['status']; ?></span></td>
                                    <td style="max-width: 200px;">
                                        <?php if (!empty($row['supervisor_comment'])): ?>
                                            <div class="small"><?php echo htmlspecialchars($row['supervisor_comment']); ?></div>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
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

<div class="text-center mt-3">
    <a href="dashboard.php" class="btn btn-primary"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
