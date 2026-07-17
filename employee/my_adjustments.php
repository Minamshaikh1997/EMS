<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['employee_id'])) {
    header("Location: ../index.php");
    exit();
}

$employee_id = $_SESSION['employee_id'];
$employee_name = $_SESSION['employee_name'] ?? '';
$employee_role = $_SESSION['employee_role'] ?? 'Employee';

// Handle Cancel Request
if (isset($_GET['cancel']) && isset($_GET['id'])) {
    $adj_id = intval($_GET['id']);
    // Only allow cancel if status is Pending
    $check = mysqli_query($conn, "SELECT * FROM attendance_adjustments WHERE id='$adj_id' AND employee_id='$employee_id' AND status='Pending'");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "UPDATE attendance_adjustments SET status='Cancelled' WHERE id='$adj_id'");
        // Audit log
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        mysqli_query($conn, "INSERT INTO audit_log (action_type, description, performed_by, performed_by_role, target_id, target_type, ip_address) 
            VALUES ('Cancel Adjustment', 'Employee cancelled adjustment request #$adj_id', '$employee_name', 'Employee', '$adj_id', 'attendance_adjustments', '$ip')");
        $success = "Request cancelled successfully.";
    } else {
        $error = "Cannot cancel this request. It may already be processed.";
    }
}

// Fetch all adjustments for this employee
$adjustments = mysqli_query($conn, "
    SELECT a.*, 
           COALESCE(a.supervisor_comment, '') AS supervisor_comment,
           COALESCE(a.admin_comment, '') AS admin_comment
    FROM attendance_adjustments a 
    WHERE a.employee_id='$employee_id' 
    ORDER BY a.id DESC
");
?>
<!DOCTYPE html>
<html>
<head>
<title>My Adjustment Requests</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 30px 0; }
    .container { max-width: 1200px; }
    .card { border: none; border-radius: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.1); overflow: hidden; }
    .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 25px; }
    .card-header h3 { margin: 0; font-weight: 700; }
    .card-body { padding: 30px; }
    .table { margin-bottom: 0; }
    .table thead { background: #f8f9ff; }
    .table thead th { border: none; font-weight: 600; color: #2d3748; padding: 15px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
    .table tbody td { padding: 15px; border-color: #f0f0f0; vertical-align: middle; }
    .table tbody tr:hover { background: #f8f9ff; }
    .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .badge-pending { background: #fff9c4; color: #f57f17; }
    .badge-approved { background: #c8e6c9; color: #2e7d32; }
    .badge-rejected { background: #ffcdd2; color: #c62828; }
    .badge-hold { background: #ffe0b2; color: #e65100; }
    .badge-cancelled { background: #e0e0e0; color: #424242; }
    .btn-sm { border-radius: 8px; padding: 6px 14px; font-weight: 600; font-size: 12px; }
    .empty-state { text-align: center; padding: 60px 20px; color: #718096; }
    .empty-state i { font-size: 64px; color: #cbd5e0; margin-bottom: 20px; display: block; }
    .comment-box { background: #f7fafc; border-radius: 10px; padding: 10px 15px; margin-top: 5px; font-size: 13px; border-left: 3px solid #667eea; }
    .comment-label { font-weight: 600; color: #667eea; font-size: 11px; text-transform: uppercase; }
    .alert { border-radius: 12px; }
    .btn-new { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 600; text-decoration: none; }
    .btn-new:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102,126,234,0.4); color: white; }
</style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3><i class="fas fa-list"></i> My Adjustment Requests</h3>
            <div>
                <a href="submit_adjustment.php" class="btn btn-new me-2"><i class="fas fa-plus"></i> New Request</a>
                <a href="dashboard.php" class="btn btn-primary btn-sm"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
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
                <table class="table">
                    <thead>
                        <tr>
                            <th>Request #</th>
                            <th>Date</th>
                            <th>Attendance Date</th>
                            <th>Type</th>
                            <th>Check In/Out</th>
                            <th>Reason</th>
                            <th>Comments</th>
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
                                
                                $checkTimes = '—';
                                if ($row['requested_check_in'] || $row['requested_check_out']) {
                                    $checkTimes = '';
                                    if ($row['requested_check_in']) $checkTimes .= 'In: ' . date('h:i A', strtotime($row['requested_check_in']));
                                    if ($row['requested_check_out']) $checkTimes .= ($checkTimes ? ' | ' : '') . 'Out: ' . date('h:i A', strtotime($row['requested_check_out']));
                                }
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['request_no']); ?></strong></td>
                                    <td><?php echo date('d-m-Y', strtotime($row['created_at'])); ?></td>
                                    <td><?php echo date('d-m-Y', strtotime($row['attendance_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['adjustment_type']); ?></td>
                                    <td><?php echo $checkTimes; ?></td>
                                    <td style="max-width: 200px;">
                                        <div class="text-truncate" style="max-width: 180px;" title="<?php echo htmlspecialchars($row['reason']); ?>">
                                            <?php echo htmlspecialchars($row['reason']); ?>
                                        </div>
                                        <?php if (!empty($row['attachment'])): ?>
                                            <a href="../uploads/adjustments/<?php echo $row['attachment']; ?>" target="_blank" class="text-primary small"><i class="fas fa-paperclip"></i> Attachment</a>
                                        <?php endif; ?>
                                    </td>
                                    <td style="max-width: 200px;">
                                        <?php if (!empty($row['supervisor_comment'])): ?>
                                            <div class="comment-box">
                                                <div class="comment-label">Supervisor:</div>
                                                <?php echo htmlspecialchars($row['supervisor_comment']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($row['admin_comment'])): ?>
                                            <div class="comment-box mt-1">
                                                <div class="comment-label">Admin:</div>
                                                <?php echo htmlspecialchars($row['admin_comment']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (empty($row['supervisor_comment']) && empty($row['admin_comment'])): ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge <?php echo $statusClass; ?>"><?php echo $row['status']; ?></span></td>
                                    <td>
                                        <?php if ($row['status'] === 'Pending'): ?>
                                            <a href="?cancel=1&id=<?php echo $row['id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to cancel this request?')">
                                                <i class="fas fa-times"></i> Cancel
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <h5>No Adjustment Requests</h5>
                                        <p>You haven't submitted any attendance adjustment requests yet.</p>
                                        <a href="submit_adjustment.php" class="btn btn-new"><i class="fas fa-plus"></i> Submit Your First Request</a>
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
<div class="text-center mt-3 mb-4">
    <a href="dashboard.php" class="btn btn-primary"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
</div>
</body>
</html>
