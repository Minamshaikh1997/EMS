<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.html");
    exit();
}

include("../config/db.php");

$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$admin_id = $_SESSION['admin_id'] ?? 0;

// Delete history record
if (isset($_GET['delete_history'])) {
    $history_id = intval($_GET['delete_history']);
    mysqli_query($conn, "DELETE FROM shift_history WHERE id='$history_id'");
    $success = "Shift history record deleted.";
}

// Process shift update
if (isset($_POST['update_shift'])) {
    $emp_id = intval($_POST['emp_id']);
    $shift_name = mysqli_real_escape_string($conn, $_POST['shift_name']);
    $shift_start = mysqli_real_escape_string($conn, $_POST['shift_start_time']);
    $shift_end = mysqli_real_escape_string($conn, $_POST['shift_end_time']);

    // Get old shift data
    $old_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT shift_name, shift_start_time, shift_end_time FROM employees WHERE id='$emp_id'"));
    $old_shift_name = $old_data['shift_name'] ?? '';
    $old_shift_start = $old_data['shift_start_time'] ?? '09:00:00';
    $old_shift_end = $old_data['shift_end_time'] ?? '17:00:00';

    $effective_date = mysqli_real_escape_string($conn, $_POST['effective_date'] ?? date('Y-m-d'));

    // Update employee's current shift
    mysqli_query($conn, "UPDATE employees SET 
        shift_name='$shift_name',
        shift_start_time='$shift_start',
        shift_end_time='$shift_end'
        WHERE id='$emp_id'");

    // Save to shift_history with date
    mysqli_query($conn, "INSERT INTO shift_history 
        (employee_id, old_shift_name, old_shift_start, old_shift_end, 
         new_shift_name, new_shift_start, new_shift_end, 
         effective_date, changed_by) VALUES 
        ('$emp_id', '$old_shift_name', '$old_shift_start', '$old_shift_end',
         '$shift_name', '$shift_start', '$shift_end',
         '$effective_date', '$admin_id')");

    $success = "Shift updated successfully effective from " . date('d-m-Y', strtotime($effective_date)) . ".";
}

// Get departments for filter dropdown
$departments = mysqli_query($conn, "SELECT * FROM departments ORDER BY department_name");

// Filters
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filter_department = isset($_GET['department']) ? mysqli_real_escape_string($conn, $_GET['department']) : '';
$filter_shift = isset($_GET['shift']) ? mysqli_real_escape_string($conn, $_GET['shift']) : '';

// Build query with filters
$sql = "SELECT id, employee_id, full_name, department, designation, shift_name, shift_start_time, shift_end_time FROM employees WHERE 1=1";
if ($search != '') {
    $sql .= " AND (full_name LIKE '%$search%' OR employee_id LIKE '%$search%')";
}
if ($filter_department != '') {
    $sql .= " AND department='$filter_department'";
}
if ($filter_shift != '') {
    $sql .= " AND shift_name='$filter_shift'";
}
$sql .= " ORDER BY full_name ASC";
$employees = mysqli_query($conn, $sql);
$total_filtered = mysqli_num_rows($employees);
?>
<!DOCTYPE html>
<html>
<head>
<title>Manage Employee Shifts</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
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
    .sidebar-link { display: inline-block; padding: 8px 16px; background: rgba(255,255,255,0.2); border-radius: 8px; color: white; text-decoration: none; font-size: 14px; }
    .sidebar-link:hover { background: rgba(255,255,255,0.3); color: white; }
    .form-control-sm { font-size: 13px; padding: 4px 8px; }
    .btn-sm { font-size: 12px; padding: 5px 12px; }
    .alert { border-radius: 10px; }
    .emp-info { font-weight: 600; color: #2d3748; }
    .emp-dept { font-size: 12px; color: #718096; }
    .toggle-icon { cursor: pointer; transition: transform 0.3s; user-select: none; }
    .toggle-icon:hover { opacity: 0.7; }
    .toggle-icon.expanded { transform: rotate(90deg); }
    .child-row { display: none; }
    .child-row.show { display: table-row; }
    .shift-history-cell { background: #fafbff; padding: 15px !important; }
    .shift-history-cell .history-table { font-size: 12px; }
    .shift-history-cell .history-table th { background: #e8eaff; color: #2d3748; padding: 6px 10px; font-size: 11px; text-transform: uppercase; }
    .shift-history-cell .history-table td { padding: 6px 10px; }
</style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h4><i class="fa-solid fa-clock-rotate-left"></i> Manage Employee Shifts</h4>
                <small>View and update shift timings for all employees</small>
            </div>
            <div>
                <a href="dashboard.php" class="sidebar-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>
        </div>
        <div class="card-body">
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <!-- Filter Section -->
            <form method="GET" class="row g-2 mb-4">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search by name or employee ID..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select name="department" class="form-control">
                        <option value="">All Departments</option>
                        <?php if ($departments): while($dept = mysqli_fetch_assoc($departments)): ?>
                            <option value="<?php echo htmlspecialchars($dept['department_name']); ?>" <?php echo ($filter_department == $dept['department_name']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['department_name']); ?>
                            </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="shift" class="form-control">
                        <option value="">All Shifts</option>
                        <option value="Morning" <?php echo ($filter_shift == 'Morning') ? 'selected' : ''; ?>>Morning</option>
                        <option value="Evening" <?php echo ($filter_shift == 'Evening') ? 'selected' : ''; ?>>Evening</option>
                        <option value="Night" <?php echo ($filter_shift == 'Night') ? 'selected' : ''; ?>>Night</option>
                        <option value="Flexible" <?php echo ($filter_shift == 'Flexible') ? 'selected' : ''; ?>>Flexible</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                    <a href="manage_shifts.php" class="btn btn-secondary"><i class="fas fa-undo"></i> Reset</a>
                    <span class="ms-2 text-muted small"><?php echo $total_filtered; ?> employee(s)</span>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Designation</th>
                            <th>Shift Name</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Effective Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($employees && mysqli_num_rows($employees) > 0): ?>
                            <?php while ($emp = mysqli_fetch_assoc($employees)): 
                                // Get this employee's shift history
                                $emp_history = mysqli_query($conn, "
                                    SELECT sh.*, a.name AS changed_by_name
                                    FROM shift_history sh
                                    LEFT JOIN admin a ON sh.changed_by = a.id
                                    WHERE sh.employee_id = '{$emp['id']}'
                                    ORDER BY sh.id DESC LIMIT 10
                                ");
                                $has_history = $emp_history && mysqli_num_rows($emp_history) > 0;
                                $history_id = 'hist_' . $emp['id'];
                            ?>
                                <tr>
                                    <form method="POST">
                                        <input type="hidden" name="emp_id" value="<?php echo $emp['id']; ?>">
                                        <td class="text-center">
                                            <?php if ($has_history): ?>
                                                <span class="toggle-icon" onclick="toggleHistory('<?php echo $history_id; ?>', this)" title="Click to view shift change history">
                                                    <i class="fas fa-chevron-right"></i>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted small"><i class="fas fa-minus"></i></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="emp-info"><?php echo htmlspecialchars($emp['full_name']); ?></div>
                                            <div class="emp-dept"><?php echo htmlspecialchars($emp['employee_id']); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($emp['department']); ?></td>
                                        <td><?php echo htmlspecialchars($emp['designation']); ?></td>
                                        <td>
                                            <select name="shift_name" class="form-control form-control-sm" style="min-width: 100px;">
                                                <option value="Morning" <?php echo ($emp['shift_name'] == 'Morning') ? 'selected' : ''; ?>>Morning</option>
                                                <option value="Evening" <?php echo ($emp['shift_name'] == 'Evening') ? 'selected' : ''; ?>>Evening</option>
                                                <option value="Night" <?php echo ($emp['shift_name'] == 'Night') ? 'selected' : ''; ?>>Night</option>
                                                <option value="Flexible" <?php echo ($emp['shift_name'] == 'Flexible') ? 'selected' : ''; ?>>Flexible</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="time" name="shift_start_time" class="form-control form-control-sm" style="min-width: 100px;" value="<?php echo !empty($emp['shift_start_time']) ? date('H:i', strtotime($emp['shift_start_time'])) : '09:00'; ?>">
                                        </td>
                                        <td>
                                            <input type="time" name="shift_end_time" class="form-control form-control-sm" style="min-width: 100px;" value="<?php echo !empty($emp['shift_end_time']) ? date('H:i', strtotime($emp['shift_end_time'])) : '17:00'; ?>">
                                        </td>
                                        <td>
                                            <input type="date" name="effective_date" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>" required style="min-width: 130px;">
                                        </td>
                                        <td>
                                            <button type="submit" name="update_shift" class="btn btn-primary btn-sm" onclick="return confirm('Update shift for <?php echo htmlspecialchars($emp['full_name']); ?> effective from this date?')">
                                                <i class="fas fa-save"></i> Update
                                            </button>
                                        </td>
                                    </form>
                                </tr>
                                <!-- Hidden row for shift history -->
                                <tr id="<?php echo $history_id; ?>" class="child-row">
                                    <td colspan="9" class="shift-history-cell">
                                        <?php if ($has_history): ?>
                                            <table class="table history-table mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Changed At</th>
                                                        <th>Old Shift</th>
                                                        <th>New Shift</th>
                                                        <th>Effective From</th>
                                                        <th>Changed By</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($h = mysqli_fetch_assoc($emp_history)): ?>
                                                        <tr>
                                                            <td><?php echo date('d-m-Y H:i', strtotime($h['changed_at'])); ?></td>
                                                            <td><?php echo htmlspecialchars($h['old_shift_name']) . ' (' . date('H:i', strtotime($h['old_shift_start'])) . '-' . date('H:i', strtotime($h['old_shift_end'])) . ')'; ?></td>
                                                            <td><?php echo htmlspecialchars($h['new_shift_name']) . ' (' . date('H:i', strtotime($h['new_shift_start'])) . '-' . date('H:i', strtotime($h['new_shift_end'])) . ')'; ?></td>
                                                            <td><?php echo date('d-m-Y', strtotime($h['effective_date'])); ?></td>
                                                            <td><?php echo htmlspecialchars($h['changed_by_name'] ?? 'Admin'); ?></td>
                                                            <td>
                                                                <a href="?delete_history=<?php echo $h['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this shift history record?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        <?php else: ?>
                                            <p class="text-muted text-center mb-0">No shift change history</p>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted">No employees found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>
<div class="text-center mt-3 mb-3">
    <a href="dashboard.php" class="btn btn-primary"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
</div>
<script>
function toggleHistory(id, el) {
    var row = document.getElementById(id);
    if (row.classList.contains('show')) {
        row.classList.remove('show');
        el.classList.remove('expanded');
    } else {
        row.classList.add('show');
        el.classList.add('expanded');
    }
}
</script>
</body>
</html>