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

// Process admin action
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
            
            // Auto-update attendance
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
        } elseif ($action === 'cancel') {
            $new_status = 'Cancelled';
            $action_desc = 'Cancelled';
        } else {
            $error = "Invalid action.";
        }
        
        if (!isset($error)) {
            mysqli_query($conn, "UPDATE attendance_adjustments SET 
                status='$new_status',
                admin_comment='$comment',
                admin_id='$admin_id',
                admin_date=NOW()
                WHERE id='$adj_id'");
            
            // Audit log
            mysqli_query($conn, "INSERT INTO audit_log (action_type, description, performed_by, performed_by_role, target_id, target_type, ip_address) 
                VALUES ('Admin $action_desc Adjustment', 'Admin $action_desc adjustment request #$adj_id', '$admin_name', 'Admin', '$adj_id', 'attendance_adjustments', '$ip')");
            
            $success = "Adjustment request $action_desc successfully.";
        }
    }
}

// Get ALL adjustments with employee info
$adjustments = mysqli_query($conn, "
    SELECT a.*, e.full_name, e.department, e.employee_id AS emp_code
    FROM attendance_adjustments a
    INNER JOIN employees e ON a.employee_id = e.id
    ORDER BY a.id DESC
");
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin - Attendance Adjustments</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    body { background: #f4f7fc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 30px 0; }
    .container { max-width: 1400px; }
    .card { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); overflow: hidden; }
    .card-header { background: linear-gradient(135deg, #1f2937 0%, #374151 100%); color: white; border: none; padding: 20px 25px; }
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
    .badge-cancelled { background: #e0e0e0; color: #424242; }
    .comment-box { background: #f7fafc; border-radius: 10px; padding: 12px; border: 1px solid #e2e8f0; }
    .comment-box textarea { border: none; background: transparent; resize: vertical; width: 100%; outline: none; }
    .comment-box textarea:focus { border: none; box-shadow: none; }
    .btn-action { border-radius: 8px; padding: 8px 16px; font-weight: 600; font-size: 13px; transition: all 0.2s; }
    .btn-action:hover { transform: translateY(-1px); }
    .empty-state { text-align: center; padding: 60px 20px; color: #718096; }
    .empty-state i { font-size: 64px; color: #cbd5e0; margin-bottom: 20px; display: block; }
    .alert { border-radius: 10px; }
    .action-group { display: flex; gap: 5px; flex-wrap: wrap; }
    .emp-info { font-weight: 600; color: #2d3748; }
    .emp-dept { font-size: 12px; color: #718096; }
    .sidebar-link { display: inline-block; padding: 8px 16px; background: rgba(255,255,255,0.2); border-radius: 8px; color: white; text-decoration: none; font-size: 14px; }
    .sidebar-link:hover { background: rgba(255,255,255,0.3); color: white; }
    .filter-badge { cursor: pointer; }
    .status-filter { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
    .status-filter .btn { border-radius: 20px; padding: 6px 16px; font-size: 13px; }
</style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h4><i class="fas fa-shield-alt"></i> Admin - All Attendance Adjustments</h4>
                <small>Full control: Approve, Reject, Hold, or Cancel requests</small>
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
            
            <div class="table-responsive">
                <table class="table" id="adjustmentsTable">
                    <thead>
                        <tr>
                            <th>Request #</th>
                            <th>Employee</th>
                            <th>Submitted</th>
                            <th>Attendance Date</th>
                            <th>Type</th>
                            <th>Requested Times</th>
                            <th>Reason</th>
                            <th>Attachment</th>
                            <th>Supervisor</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($adjustments && mysqli_num_rows($adjustments) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($adjustments)): ?>
                                <?php
                                $statusClass = 'badge-pending';
                                if ($row['status'] === 'Approved') $statusClass = 'badge-approved';
                                elseif ($row['status'] === 'Rejected') $statusClass = 'badge-rejected';
                                elseif ($row['status'] === 'Hold') $statusClass = 'badge-hold';
                                elseif ($row['status'] === 'Cancelled') $statusClass = 'badge-cancelled';
                                
                                $supervisor_status = $row['supervisor_status'] ?? 'Pending';
                                $supClass = 'badge-pending';
                                if ($supervisor_status === 'Approved') $supClass = 'badge-approved';
                                elseif ($supervisor_status === 'Rejected') $supClass = 'badge-rejected';
                                elseif ($supervisor_status === 'Hold') $supClass = 'badge-hold';
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['request_no']); ?></strong></td>
                                    <td>
                                        <div class="emp-info"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                        <div class="emp-dept"><?php echo htmlspecialchars($row['department']); ?></div>
                                    </td>
                                    <td><?php echo date('d-m-Y', strtotime($row['created_at'])); ?></td>
                                    <td><?php echo date('d-m-Y', strtotime($row['attendance_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['adjustment_type']); ?></td>
                                    <td style="white-space: nowrap;">
                                        <?php 
                                        if ($row['requested_check_in']) echo 'In: ' . date('h:i A', strtotime($row['requested_check_in'])) . '<br>';
                                        if ($row['requested_check_out']) echo 'Out: ' . date('h:i A', strtotime($row['requested_check_out']));
                                        if (!$row['requested_check_in'] && !$row['requested_check_out']) echo '—';
                                        ?>
                                    </td>
                                    <td style="max-width: 150px;">
                                        <div style="max-height: 60px; overflow-y: auto;"><?php echo htmlspecialchars($row['reason']); ?></div>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['attachment'])): ?>
                                            <a href="../uploads/adjustments/<?php echo $row['attachment']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-paperclip"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $supClass; ?>"><?php echo $supervisor_status; ?></span>
                                        <?php if (!empty($row['supervisor_comment'])): ?>
                                            <div class="small text-muted mt-1"><?php echo htmlspecialchars($row['supervisor_comment']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge <?php echo $statusClass; ?>"><?php echo $row['status']; ?></span></td>
                                    <td style="min-width: 200px;">
                                        <?php if ($row['status'] === 'Pending' || $row['status'] === 'Hold'): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="adj_id" value="<?php echo $row['id']; ?>">
                                                <div class="comment-box mb-2">
                                                    <textarea name="comment" class="form-control" rows="1" placeholder="Comment..."></textarea>
                                                </div>
                                                <div class="action-group">
                                                    <button type="submit" name="action" value="approve" class="btn btn-success btn-action btn-sm" onclick="return confirm('Approve? Attendance will be updated.')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="submit" name="action" value="reject" class="btn btn-danger btn-action btn-sm" onclick="return confirm('Reject?')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                    <button type="submit" name="action" value="hold" class="btn btn-warning btn-action btn-sm" onclick="return confirm('Hold?')">
                                                        <i class="fas fa-pause"></i>
                                                    </button>
                                                    <button type="submit" name="action" value="cancel" class="btn btn-secondary btn-action btn-sm" onclick="return confirm('Cancel this request?')">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                </div>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small">Processed</span>
                                            <?php if (!empty($row['admin_comment'])): ?>
                                                <div class="small text-muted mt-1">Admin: <?php echo htmlspecialchars($row['admin_comment']); ?></div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11">
                                    <div class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <h5>No Adjustment Requests</h5>
                                        <p>No attendance adjustment requests have been submitted yet.</p>
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
<div class="text-center mt-3">
    <a href="dashboard.php" class="btn btn-primary"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
</div>
</body>
</html>
