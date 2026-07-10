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

if ($employee_role === 'Admin') {
    header("Location: ../admin/dashboard.php");
    exit();
}

$employee = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM employees WHERE id='$employee_id' LIMIT 1"));
$photo = !empty($employee['photo']) ? $employee['photo'] : 'default.png';

$totalLeaves = 0;
$approvedLeaves = 0;
$pendingLeaves = 0;
$today = date('Y-m-d');
$attendanceStatus = 'Absent';

$leaveCounts = mysqli_query($conn, "SELECT status, COUNT(*) AS cnt FROM leave_requests WHERE employee_id='$employee_id' GROUP BY status");
if ($leaveCounts) {
    while ($row = mysqli_fetch_assoc($leaveCounts)) {
        $totalLeaves += $row['cnt'];
        if ($row['status'] === 'Approved') {
            $approvedLeaves = $row['cnt'];
        } elseif ($row['status'] === 'Pending') {
            $pendingLeaves = $row['cnt'];
        }
    }
}

$todayAttendance = mysqli_query($conn, "SELECT * FROM attendance WHERE employee_id='$employee_id' AND attendance_date='$today' LIMIT 1");
if ($todayAttendance && mysqli_num_rows($todayAttendance) > 0) {
    $attendance = mysqli_fetch_assoc($todayAttendance);
    $attendanceStatus = $attendance['status'] ?? 'Present';
}

$shiftLabel = !empty($employee['shift_name']) ? $employee['shift_name'] : 'Morning';
$shiftStart = !empty($employee['shift_start_time']) ? date('H:i', strtotime($employee['shift_start_time'])) : '09:00';
$shiftEnd = !empty($employee['shift_end_time']) ? date('H:i', strtotime($employee['shift_end_time'])) : '17:00';
?>

<!DOCTYPE html>
<html>

<head>

<title>Employee Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        padding: 30px 0;
    }

    .container {
        max-width: 1200px;
    }

    /* Dashboard Header */
    .dashboard-header {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.3);
        margin-bottom: 30px;
        animation: slideDown 0.6s ease-out;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .dashboard-header .profile-section {
        display: flex;
        align-items: center;
        gap: 25px;
        margin-bottom: 25px;
        padding-bottom: 25px;
        border-bottom: 2px solid #f0f0f0;
    }

    .dashboard-header .profile-section img {
        width: 100px;
        height: 100px;
        border-radius: 20px;
        border: 4px solid #667eea;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        object-fit: cover;
    }

    .dashboard-header h3 {
        font-size: 28px;
        font-weight: 700;
        color: #2d3748;
        margin: 0;
    }

    .dashboard-header p {
        color: #718096;
        margin: 8px 0 0 0;
        font-size: 14px;
    }

    .header-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .btn-header {
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-header:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    }

    /* Stats Cards */
    .stat-card {
        border: none;
        border-radius: 16px;
        padding: 25px;
        background: white;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        text-align: center;
        animation: fadeIn 0.6s ease-out;
        margin-bottom: 20px;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
    }

    .stat-card .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        font-size: 28px;
    }

    .stat-card .stat-value {
        font-size: 32px;
        font-weight: 700;
        color: #2d3748;
        margin: 10px 0;
    }

    .stat-card .stat-label {
        font-size: 14px;
        color: #718096;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-card.stat-blue .stat-icon {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.2), rgba(118, 75, 162, 0.2));
        color: #667eea;
    }

    .stat-card.stat-green .stat-icon {
        background: linear-gradient(135deg, rgba(76, 175, 80, 0.2), rgba(56, 142, 60, 0.2));
        color: #4caf50;
    }

    .stat-card.stat-orange .stat-icon {
        background: linear-gradient(135deg, rgba(255, 152, 0, 0.2), rgba(255, 111, 0, 0.2));
        color: #ff9800;
    }

    .stat-card.stat-red .stat-icon {
        background: linear-gradient(135deg, rgba(244, 67, 54, 0.2), rgba(229, 57, 53, 0.2));
        color: #f44336;
    }

    /* Feature Cards */
    .feature-card {
        border: none;
        border-radius: 16px;
        background: white;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        transition: all 0.3s ease;
        animation: fadeIn 0.6s ease-out 0.1s backwards;
    }

    .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
    }

    .feature-card .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 20px;
        font-weight: 700;
        font-size: 16px;
        border-radius: 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .feature-card .card-header i {
        font-size: 20px;
    }

    .feature-card .card-body {
        padding: 25px;
        background: white;
    }

    .feature-title {
        font-size: 13px;
        font-weight: 700;
        color: #667eea;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 12px;
    }

    .feature-card .card-body strong {
        color: #2d3748;
        font-size: 14px;
    }

    .feature-card .card-body p {
        color: #718096;
        margin: 8px 0;
        font-size: 15px;
    }

    /* Menu Section */
    .menu-section {
        background: white;
        border-radius: 16px;
        padding: 30px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        margin-top: 30px;
    }

    .menu-section h5 {
        font-size: 18px;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .menu-section h5 i {
        color: #667eea;
        font-size: 20px;
    }

    .menu-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }

    .menu-btn {
        padding: 18px 20px;
        border-radius: 12px;
        font-weight: 600;
        border: none;
        transition: all 0.3s ease;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        color: white;
        font-size: 14px;
    }

    .menu-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        color: white;
        text-decoration: none;
    }

    .menu-btn i {
        font-size: 16px;
    }

    .menu-btn.btn-attendance {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .menu-btn.btn-history {
        background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%);
    }

    .menu-btn.btn-apply {
        background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
    }

    .menu-btn.btn-leave {
        background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);
    }

    .menu-btn.btn-password {
        background: linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%);
    }

    .menu-btn.btn-balance {
        background: linear-gradient(135deg, #00bcd4 0%, #0097a7 100%);
    }

    /* Recent Leaves Table */
    .recent-section {
        background: white;
        border-radius: 16px;
        padding: 30px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        margin-top: 30px;
    }

    .recent-section h5 {
        font-size: 18px;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .recent-section h5 i {
        color: #667eea;
        font-size: 20px;
    }

    .table-custom {
        margin-bottom: 0;
    }

    .table-custom thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .table-custom thead th {
        border: none;
        font-weight: 600;
        padding: 15px;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .table-custom tbody td {
        padding: 15px;
        border-color: #f0f0f0;
        color: #2d3748;
        font-size: 14px;
    }

    .table-custom tbody tr:hover {
        background: #f8f9ff;
    }

    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .status-approved {
        background: #c8e6c9;
        color: #2e7d32;
    }

    .status-pending {
        background: #fff9c4;
        color: #f57f17;
    }

    .status-rejected {
        background: #ffcdd2;
        color: #c62828;
    }

    .empty-state {
        text-align: center;
        padding: 40px;
        color: #718096;
    }

    .empty-state i {
        font-size: 48px;
        color: #cbd5e0;
        margin-bottom: 15px;
        display: block;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .dashboard-header {
            padding: 25px;
        }

        .dashboard-header .profile-section {
            flex-direction: column;
            text-align: center;
        }

        .dashboard-header h3 {
            font-size: 24px;
        }

        .menu-grid {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        }

        .header-actions {
            justify-content: center;
        }
    }

    @media (max-width: 480px) {
        body {
            padding: 15px 0;
        }

        .dashboard-header {
            padding: 20px;
            margin-bottom: 20px;
        }

        .dashboard-header .profile-section img {
            width: 80px;
            height: 80px;
        }

        .dashboard-header h3 {
            font-size: 20px;
        }

        .stat-card .stat-value {
            font-size: 24px;
        }

        .menu-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

</head>

<body>
<?php include("../dark_mode.php"); ?>

<div class="container mt-5">

<div class="dashboard-header mb-4">
    <div class="profile-section">
        <img src="../uploads/<?php echo $photo; ?>" alt="Profile">
        <div>
            <h3><?php echo $employee_name; ?></h3>
            <p>Welcome back! Role: <strong><?php echo htmlspecialchars($employee_role); ?></strong></p>
        </div>
    </div>
    <div class="header-actions">
        <a href="edit_profile.php" class="btn-header btn-outline-primary" style="background: #667eea; color: white;">
            <i class="fas fa-edit"></i> Edit Profile
        </a>
        <a href="upload_photo.php" class="btn-header btn-outline-info" style="background: #2196f3; color: white;">
            <i class="fas fa-camera"></i> Profile Photo
        </a>
        <a href="logout.php" class="btn-header btn-outline-danger" style="background: #f44336; color: white;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<!-- Statistics -->
<div class="row">
    <div class="col-lg-3 col-md-6">
        <div class="stat-card stat-blue">
            <div class="stat-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-label">Total Leaves</div>
            <div class="stat-value"><?php echo $totalLeaves; ?></div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="stat-card stat-green">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-label">Approved</div>
            <div class="stat-value"><?php echo $approvedLeaves; ?></div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="stat-card stat-orange">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-label">Pending</div>
            <div class="stat-value"><?php echo $pendingLeaves; ?></div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="stat-card stat-red">
            <div class="stat-icon">
                <i class="fas fa-user-clock"></i>
            </div>
            <div class="stat-label">Today's Attendance</div>
            <div class="stat-value" style="font-size: 18px;"><?php echo $attendanceStatus; ?></div>
        </div>
    </div>
</div>

<!-- Menu -->
<div class="menu-section">
    <h5><i class="fas fa-bars"></i> Quick Access</h5>
    <div class="menu-grid">
        <a href="attendance.php" class="menu-btn btn-attendance">
            <i class="fas fa-sign-in-alt"></i> Attendance
        </a>
        <a href="attendance_history.php" class="menu-btn btn-history">
            <i class="fas fa-history"></i> History
        </a>
        <a href="apply_leave.php" class="menu-btn btn-apply">
            <i class="fas fa-plus-circle"></i> Apply Leave
        </a>
        <a href="leave_history.php" class="menu-btn btn-leave">
            <i class="fas fa-list"></i> Leave History
        </a>
        <a href="change_password.php" class="menu-btn btn-password">
            <i class="fas fa-key"></i> Change Password
        </a>
        <a href="leave_balance.php" class="menu-btn btn-balance">
            <i class="fas fa-chart-pie"></i> Leave Balance
        </a>
    </div>
</div>

<!-- Recent Leave Requests -->
<div class="recent-section">
    <h5><i class="fas fa-file-alt"></i> Recent Leave Requests</h5>
    <div class="table-responsive"><?php

$recent = mysqli_query($conn,"
SELECT leave_type,start_date,end_date,status
FROM leave_requests
WHERE employee_id='$employee_id'
ORDER BY id DESC
LIMIT 5
");

?>

<table class="table table-custom">

<thead>

<tr>

<th>Leave Type</th>
<th>Start Date</th>
<th>End Date</th>
<th>Status</th>

</tr>

</thead>

<tbody>

<?php

if(mysqli_num_rows($recent)>0)
{

while($leave=mysqli_fetch_assoc($recent))
{
    $statusClass = 'status-pending';
    if($leave['status'] === 'Approved') {
        $statusClass = 'status-approved';
    } elseif($leave['status'] === 'Rejected') {
        $statusClass = 'status-rejected';
    }
?>

<tr>

<td><?php echo $leave['leave_type']; ?></td>

<td><?php echo date("d-m-Y",strtotime($leave['start_date'])); ?></td>

<td><?php echo date("d-m-Y",strtotime($leave['end_date'])); ?></td>

<td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $leave['status']; ?></span></td>

</tr>

<?php

}

}

else

{

?>

<tr>

<td colspan="4" class="empty-state">

<i class="fas fa-inbox"></i>
No leave requests yet

</td>

</tr>

<?php

}

?>

</tbody>

</table>
    </div>
</div>

</div>

</body>
</html>
