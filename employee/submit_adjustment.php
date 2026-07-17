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

$success = '';
$error = '';

// Fetch attendance records for this employee
$attendance_records = mysqli_query($conn, "
    SELECT id, attendance_date, check_in, check_out, status 
    FROM attendance 
    WHERE employee_id='$employee_id' 
    ORDER BY attendance_date DESC 
    LIMIT 60
");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attendance_date = mysqli_real_escape_string($conn, $_POST['attendance_date']);
    $adjustment_type = mysqli_real_escape_string($conn, $_POST['adjustment_type']);
    $requested_check_in = !empty($_POST['requested_check_in']) ? $_POST['requested_check_in'] : null;
    $requested_check_out = !empty($_POST['requested_check_out']) ? $_POST['requested_check_out'] : null;
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    $attendance_id = !empty($_POST['attendance_id']) ? intval($_POST['attendance_id']) : null;
    
    // Handle file upload
    $attachment = '';
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../uploads/adjustments/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
        $attachment = 'ADJ_' . $employee_id . '_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['attachment']['tmp_name'], $target_dir . $attachment);
    }
    
    // Generate request number
    $req_result = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM attendance_adjustments");
    $req_count = mysqli_fetch_assoc($req_result)['cnt'] + 1;
    $request_no = 'ADJ-' . date('Ymd') . '-' . str_pad($req_count, 4, '0', STR_PAD_LEFT);
    
    $query = "INSERT INTO attendance_adjustments 
              (request_no, employee_id, attendance_id, attendance_date, adjustment_type, 
               requested_check_in, requested_check_out, reason, attachment, status, supervisor_status) 
              VALUES 
              ('$request_no', '$employee_id', " . ($attendance_id ? "'$attendance_id'" : "NULL") . ", 
               '$attendance_date', '$adjustment_type', " . 
               ($requested_check_in ? "'$requested_check_in'" : "NULL") . ", " . 
               ($requested_check_out ? "'$requested_check_out'" : "NULL") . ", 
               '$reason', '$attachment', 'Pending', 'Pending')";
    
    if (mysqli_query($conn, $query)) {
        $success = "Adjustment request submitted successfully! Request #: $request_no";
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Submit Adjustment Request</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 30px 0; }
    .container { max-width: 800px; }
    .card { border: none; border-radius: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.1); overflow: hidden; }
    .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 25px; }
    .card-header h3 { margin: 0; font-weight: 700; }
    .card-body { padding: 30px; }
    .form-label { font-weight: 600; color: #2d3748; }
    .form-control, .form-select { border-radius: 10px; padding: 12px; border: 2px solid #e2e8f0; }
    .form-control:focus, .form-select:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
    .btn-submit { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 14px 30px; border-radius: 12px; font-weight: 600; font-size: 16px; transition: all 0.3s ease; }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102,126,234,0.4); color: white; }
    .btn-back { background: #4a5568; color: white; border: none; padding: 14px 30px; border-radius: 12px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
    .btn-back:hover { background: #2d3748; color: white; }
    .alert { border-radius: 12px; }
    .preview-box { background: #f7fafc; border: 2px dashed #e2e8f0; border-radius: 12px; padding: 20px; text-align: center; }
</style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3><i class="fas fa-pen-alt"></i> Attendance Adjustment Request</h3>
            <a href="dashboard.php" class="btn btn-primary btn-sm"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        <div class="card-body">
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Attendance Date <span class="text-danger">*</span></label>
                        <input type="date" name="attendance_date" class="form-control" required max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Adjustment Type <span class="text-danger">*</span></label>
                        <select name="adjustment_type" class="form-select" required id="adjustmentType">
                            <option value="">Select Type</option>
                            <option value="Forgot Check In">Forgot Check In</option>
                            <option value="Forgot Check Out">Forgot Check Out</option>
                            <option value="Wrong Check In">Wrong Check In</option>
                            <option value="Wrong Check Out">Wrong Check Out</option>
                            <option value="Missed Attendance">Missed Attendance</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-6" id="checkInField" style="display:none;">
                        <label class="form-label">Correct Check-In Time</label>
                        <input type="time" name="requested_check_in" class="form-control">
                    </div>
                    <div class="col-md-6" id="checkOutField" style="display:none;">
                        <label class="form-label">Correct Check-Out Time</label>
                        <input type="time" name="requested_check_out" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="4" required placeholder="Explain why you need this adjustment..."></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Attachment (Optional)</label>
                        <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                        <small class="text-muted">Upload supporting document (max 5MB)</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Link to Attendance Record (Optional)</label>
                        <select name="attendance_id" class="form-select">
                            <option value="">-- No Link --</option>
                            <?php while ($att = mysqli_fetch_assoc($attendance_records)): ?>
                                <option value="<?php echo $att['id']; ?>">
                                    <?php echo $att['attendance_date'] . ' - ' . ($att['check_in'] ?? 'No Check In') . ' / ' . ($att['check_out'] ?? 'No Check Out') . ' [' . $att['status'] . ']'; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-12 mt-4 d-flex gap-3">
                        <button type="submit" class="btn btn-submit"><i class="fas fa-paper-plane"></i> Submit Request</button>
                        <a href="dashboard.php" class="btn btn-primary"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('adjustmentType').addEventListener('change', function() {
    var val = this.value;
    document.getElementById('checkInField').style.display = (val === 'Forgot Check In' || val === 'Wrong Check In') ? 'block' : 'none';
    document.getElementById('checkOutField').style.display = (val === 'Forgot Check Out' || val === 'Wrong Check Out') ? 'block' : 'none';
});
</script>
</body>
</html>