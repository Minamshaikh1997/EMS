<?php
ini_set('display_errors', '0');
ini_set('log_errors', '1');

session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

include("../config/db.php");
include_once("../config/audit.php");
include("admincheck_role.php");

if (!in_array($admin_role, ['Super Admin', 'Admin', 'Operations Manager'], true)) {
    http_response_code(403);
    exit('You do not have permission to send system email.');
}

$message = '';
$messageType = '';

// Fetch all admin emails
$adminsQuery = mysqli_query($conn, "SELECT id, name, email, role FROM admin WHERE email IS NOT NULL AND email != '' ORDER BY name ASC");
$admins = [];
while ($row = mysqli_fetch_assoc($adminsQuery)) {
    $admins[] = $row;
}

// Fetch all employee emails
$employeesQuery = mysqli_query($conn, "SELECT id, employee_id, full_name, email, department FROM employees WHERE email IS NOT NULL AND email != '' ORDER BY full_name ASC");
$employees = [];
while ($row = mysqli_fetch_assoc($employeesQuery)) {
    $employees[] = $row;
}

$allowedEmails = [];
foreach (array_merge($admins, $employees) as $recipient) {
    $normalized = strtolower(trim((string)$recipient['email']));
    if (filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
        $allowedEmails[$normalized] = true;
    }
}

// Handle email sending
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    ems_verify_csrf();
    $selectedEmails = is_array($_POST['emails'] ?? null) ? $_POST['emails'] : [];
    $selectedEmails = array_values(array_unique(array_map(static fn($email) => strtolower(trim((string)$email)), $selectedEmails)));
    $selectedEmails = array_values(array_filter($selectedEmails, static fn($email) => isset($allowedEmails[$email])));
    $subject = trim((string)($_POST['subject'] ?? ''));
    $messageBody = trim((string)($_POST['message'] ?? ''));
    
    if (empty($selectedEmails)) {
        $message = 'Please select at least one recipient.';
        $messageType = 'danger';
    } elseif (count($selectedEmails) > 100) {
        $message = 'A maximum of 100 recipients is allowed per send.';
        $messageType = 'danger';
    } elseif ($subject === '' || strlen($subject) > 200 || preg_match('/[\r\n]/', $subject)) {
        $message = 'Please enter a valid subject up to 200 characters.';
        $messageType = 'danger';
    } elseif ($messageBody === '' || strlen($messageBody) > 10000) {
        $message = 'Please enter a message up to 10,000 characters.';
        $messageType = 'danger';
    } else {
        // Send emails
        $fromAddress = getenv('EMS_MAIL_FROM') ?: 'noreply@example.invalid';
        if (!filter_var($fromAddress, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $fromAddress)) {
            $fromAddress = 'noreply@example.invalid';
        }
        $headers = "From: EMS System <{$fromAddress}>\r\n";
        $headers .= "Reply-To: {$fromAddress}\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $safeBody = nl2br(htmlspecialchars($messageBody, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        
        $sentCount = 0;
        $failedCount = 0;
        $failedEmails = [];
        
        foreach ($selectedEmails as $email) {
            if (isset($allowedEmails[$email])) {
                $mailSent = mail($email, $subject, $safeBody, $headers);
                if ($mailSent) {
                    $sentCount++;
                } else {
                    $failedCount++;
                    $failedEmails[] = $email;
                }
            }
        }
        
        if ($sentCount > 0 && $failedCount == 0) {
            $message = "Email sent successfully to $sentCount recipient(s)!";
            $messageType = 'success';
            // Clear form
            $_POST = [];
        } elseif ($sentCount > 0 && $failedCount > 0) {
            $message = "Sent to $sentCount recipient(s); $failedCount delivery attempt(s) failed.";
            $messageType = 'warning';
        } else {
            $message = "Failed to send emails. Please check your server mail configuration.";
            $messageType = 'danger';
        }

        ems_audit($conn, 'email.bulk_send', 'email_batch', null, [
            'requested' => count($selectedEmails),
            'sent' => $sentCount,
            'failed' => $failedCount,
            'subject_length' => strlen($subject),
        ]);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Send Email - EMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
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
body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: var(--gray-50); color: var(--gray-800); }
.email-compose { background: white; border-radius: var(--radius); border: 1px solid var(--gray-200); box-shadow: var(--shadow); }
.email-compose .compose-header { padding: 20px 24px; border-bottom: 1px solid var(--gray-200); background: var(--gray-50); }
.email-compose .compose-body { padding: 24px; }
.form-label { font-size: 13px; font-weight: 600; color: var(--gray-700); margin-bottom: 6px; }
.form-control, .form-select { border: 1px solid var(--gray-300); border-radius: var(--radius-sm); padding: 10px 14px; font-size: 14px; }
.form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
.recipient-tabs { display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; }
.recipient-tab { padding: 8px 16px; border-radius: var(--radius-sm); border: 1px solid var(--gray-300); background: white; cursor: pointer; font-size: 13px; font-weight: 600; transition: all .2s; }
.recipient-tab:hover { background: var(--gray-50); }
.recipient-tab.active { background: var(--primary); color: white; border-color: var(--primary); }
.email-list { max-height: 300px; overflow-y: auto; border: 1px solid var(--gray-200); border-radius: var(--radius-sm); padding: 12px; background: var(--gray-50); }
.email-item { display: flex; align-items: center; padding: 8px 12px; margin-bottom: 6px; background: white; border-radius: 8px; border: 1px solid var(--gray-200); cursor: pointer; transition: all .2s; }
.email-item:hover { border-color: var(--primary); background: rgba(37,99,235,.04); }
.email-item input[type="checkbox"] { margin-right: 10px; width: 16px; height: 16px; cursor: pointer; }
.email-item .email-info { flex: 1; }
.email-item .email-name { font-size: 13px; font-weight: 600; color: var(--gray-800); }
.email-item .email-address { font-size: 12px; color: var(--gray-500); }
.email-item .email-role { font-size: 11px; color: var(--gray-400); font-weight: 500; }
.select-all-bar { display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; background: white; border: 1px solid var(--gray-200); border-radius: 8px; margin-bottom: 10px; }
.select-all-bar a { font-size: 12px; font-weight: 600; color: var(--primary); text-decoration: none; cursor: pointer; }
.select-all-bar a:hover { text-decoration: underline; }
.selected-count { font-size: 12px; color: var(--gray-600); font-weight: 600; }
textarea.form-control { min-height: 150px; resize: vertical; }
.btn-send { background: linear-gradient(135deg, var(--success), #059669); color: white; border: none; padding: 12px 28px; border-radius: var(--radius-sm); font-weight: 600; font-size: 14px; }
.btn-send:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); color: white; }
.alert-custom { border-radius: var(--radius-sm); border: none; padding: 14px 20px; font-size: 14px; }
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
        <div class="sidebar-section-group">
        <a href="dashboard.php" class="sidebar-link"><i class="fa fa-gauge"></i> Dashboard</a>
        <a href="employee.php" class="sidebar-link"><i class="fa fa-users"></i> Employees</a>
        <a href="add_employee.php" class="sidebar-link"><i class="fa fa-user-plus"></i> Add Employee</a>
        <a href="employee_rights_management.php" class="sidebar-link"><i class="fa fa-user-shield"></i> Employee Rights</a>
        <a href="leave_requests.php" class="sidebar-link"><i class="fa fa-calendar-check"></i> Leave Requests</a>
        <a href="supervisor_adjustments.php" class="sidebar-link"><i class="fa fa-user-tie"></i> Supervisor Adjustments</a>
        <a href="admin_adjustments.php" class="sidebar-link"><i class="fa fa-shield-alt"></i> Admin Adjustments</a>
        <a href="manage_shifts.php" class="sidebar-link"><i class="fa fa-clock-rotate-left"></i> Manage Shifts</a>
        <a href="attendance_report.php" class="sidebar-link"><i class="fa fa-clock"></i> Attendance</a>
        <a href="reports.php" class="sidebar-link"><i class="fa fa-chart-column"></i> Reports</a>
        </div>

        <div class="sidebar-section-title">Payroll</div>
        <div class="sidebar-section-group">
        <a href="payroll_dashboard.php" class="sidebar-link"><i class="fa-solid fa-money-bill-wave"></i> Payroll Dashboard</a>
        <a href="generate_payroll.php" class="sidebar-link"><i class="fa fa-file-invoice-dollar"></i> Generate Payroll</a>
        <a href="payroll_history.php" class="sidebar-link"><i class="fa fa-clock-rotate-left"></i> Payroll History</a>
        <a href="salary_components.php" class="sidebar-link"><i class="fa-solid fa-wallet"></i> Salary Components</a>
        <a href="salary_slips.php" class="sidebar-link"><i class="fa-solid fa-file-pdf"></i> Salary Slips</a>
        <a href="payroll_reports.php" class="sidebar-link"><i class="fa-solid fa-chart-line"></i> Payroll Reports</a>
        <a href="salary_structure.php" class="sidebar-link"><i class="fa fa-money-bill-wave"></i> Salary Structure</a>
        <a href="monthly_payroll.php" class="sidebar-link"><i class="fa fa-calendar"></i> Monthly Payroll</a>
        </div>

        <div class="sidebar-section-title">System</div>
        <div class="sidebar-section-group">
        <a href="add_notice.php" class="sidebar-link"><i class="fa fa-bullhorn"></i> Notices</a>
        <a href="add_holiday.php" class="sidebar-link"><i class="fa fa-plane"></i> Holidays</a>
        <a href="send_email.php" class="sidebar-link active"><i class="fa fa-envelope"></i> Send Email</a>
        <?php if (in_array($admin_role, ['Super Admin', 'Admin'], true)): ?><a href="security_audit.php" class="sidebar-link"><i class="fa fa-shield-halved"></i> Security Audit</a><?php endif; ?>
        <a href="change_password.php" class="sidebar-link"><i class="fa fa-key"></i> Change Password</a>
        <a href="logout.php" class="sidebar-link"><i class="fa fa-right-from-bracket"></i> Logout</a>
        </div>
    </nav>
</aside>

<div class="main-content" id="mainContent">
    <header class="header">
        <div class="header-left">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><i class="fa fa-bars"></i></button>
            <h4>Send Email <span>/ Compose Message</span></h4>
        </div>
        <div class="header-right">
            <span class="header-date"><i class="fa-regular fa-calendar"></i> <?=date('d M Y')?></span>
            <span class="header-admin-badge"><i class="fa fa-user-shield"></i> <span><?php echo htmlspecialchars($admin_name); ?></span></span>
            <?php $darkModeInTopbar = true; include("../dark_mode.php"); ?>
            <a href="logout.php" class="btn btn-danger btn-sm rounded-pill px-3"><i class="fa fa-right-from-bracket"></i> <span>Logout</span></a>
        </div>
    </header>

    <div class="page-content">
        <?php if($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show rounded-pill px-4" role="alert">
                <i class="fa fa-<?php echo $messageType == 'success' ? 'check-circle' : ($messageType == 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="email-compose">
            <div class="compose-header">
                <h5 class="mb-0"><i class="fa fa-envelope me-2" style="color: var(--primary);"></i> Compose Email</h5>
            </div>
            <div class="compose-body">
<form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(ems_csrf_token()) ?>">
                    <!-- Recipient Type Selection -->
                    <div class="mb-3">
                        <label class="form-label">Select Recipients</label>
                        <div class="recipient-tabs">
                            <button type="button" class="recipient-tab active" data-type="admins" onclick="switchRecipientType('admins')">
                                <i class="fa fa-user-shield me-1"></i> Admins (<?php echo count($admins); ?>)
                            </button>
                            <button type="button" class="recipient-tab" data-type="employees" onclick="switchRecipientType('employees')">
                                <i class="fa fa-users me-1"></i> Employees (<?php echo count($employees); ?>)
                            </button>
                            <button type="button" class="recipient-tab" data-type="all" onclick="switchRecipientType('all')">
                                <i class="fa fa-address-book me-1"></i> All (<?php echo count($admins) + count($employees); ?>)
                            </button>
                        </div>
                    </div>

                    <!-- Email List -->
                    <div class="mb-3">
                        <div class="select-all-bar">
                            <span class="selected-count" id="selectedCount">0 selected</span>
                            <div>
                                <a onclick="selectAll()">Select All</a> &nbsp;|&nbsp; 
                                <a onclick="deselectAll()">Deselect All</a>
                            </div>
                        </div>
                        
                        <div class="email-list" id="emailList">
                            <!-- Admins List -->
                            <div id="adminsList">
                                <?php if(count($admins) > 0): ?>
                                    <?php foreach($admins as $admin): ?>
                                        <div class="email-item">
                                            <input type="checkbox" name="emails[]" value="<?php echo htmlspecialchars($admin['email']); ?>" class="email-checkbox" onchange="updateCount()">
                                            <div class="email-info">
                                                <div class="email-name"><?php echo htmlspecialchars($admin['name']); ?></div>
                                                <div class="email-address"><?php echo htmlspecialchars($admin['email']); ?></div>
                                                <div class="email-role"><?php echo htmlspecialchars($admin['role']); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-center text-muted py-3 mb-0">No admins found</p>
                                <?php endif; ?>
                            </div>

                            <!-- Employees List (Hidden by default) -->
                            <div id="employeesList" style="display: none;">
                                <?php if(count($employees) > 0): ?>
                                    <?php foreach($employees as $emp): ?>
                                        <div class="email-item">
                                            <input type="checkbox" name="emails[]" value="<?php echo htmlspecialchars($emp['email']); ?>" class="email-checkbox" onchange="updateCount()">
                                            <div class="email-info">
                                                <div class="email-name"><?php echo htmlspecialchars($emp['full_name']); ?> (<?php echo htmlspecialchars($emp['employee_id']); ?>)</div>
                                                <div class="email-address"><?php echo htmlspecialchars($emp['email']); ?></div>
                                                <div class="email-role"><?php echo htmlspecialchars($emp['department'] ?? 'N/A'); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-center text-muted py-3 mb-0">No employees found</p>
                                <?php endif; ?>
                            </div>

                            <!-- All List (Hidden by default) -->
                            <div id="allList" style="display: none;">
                                <?php if(count($admins) > 0): ?>
                                    <div class="mb-2"><strong class="text-primary"><i class="fa fa-user-shield me-1"></i> Admins</strong></div>
                                    <?php foreach($admins as $admin): ?>
                                        <div class="email-item">
                                            <input type="checkbox" name="emails[]" value="<?php echo htmlspecialchars($admin['email']); ?>" class="email-checkbox" onchange="updateCount()">
                                            <div class="email-info">
                                                <div class="email-name"><?php echo htmlspecialchars($admin['name']); ?></div>
                                                <div class="email-address"><?php echo htmlspecialchars($admin['email']); ?></div>
                                                <div class="email-role"><?php echo htmlspecialchars($admin['role']); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <?php if(count($employees) > 0): ?>
                                    <div class="mb-2 mt-3"><strong class="text-success"><i class="fa fa-users me-1"></i> Employees</strong></div>
                                    <?php foreach($employees as $emp): ?>
                                        <div class="email-item">
                                            <input type="checkbox" name="emails[]" value="<?php echo htmlspecialchars($emp['email']); ?>" class="email-checkbox" onchange="updateCount()">
                                            <div class="email-info">
                                                <div class="email-name"><?php echo htmlspecialchars($emp['full_name']); ?> (<?php echo htmlspecialchars($emp['employee_id']); ?>)</div>
                                                <div class="email-address"><?php echo htmlspecialchars($emp['email']); ?></div>
                                                <div class="email-role"><?php echo htmlspecialchars($emp['department'] ?? 'N/A'); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Subject -->
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="subject" name="subject" maxlength="200" placeholder="Enter email subject" required value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>">
                    </div>

                    <!-- Message -->
                    <div class="mb-3">
                        <label for="message" class="form-label">Message <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="message" name="message" maxlength="10000" placeholder="Type your message here..." required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                    </div>

                    <!-- Buttons -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-send">
                            <i class="fa fa-paper-plane me-2"></i> Send Email
                        </button>
                        <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill px-4">
                            <i class="fa fa-times me-1"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Switch between recipient types
function switchRecipientType(type) {
    // Update active tab
    document.querySelectorAll('.recipient-tab').forEach(tab => {
        tab.classList.remove('active');
        if(tab.dataset.type === type) {
            tab.classList.add('active');
        }
    });

    // Show corresponding list
    document.getElementById('adminsList').style.display = 'none';
    document.getElementById('employeesList').style.display = 'none';
    document.getElementById('allList').style.display = 'none';
    
    if(type === 'admins') {
        document.getElementById('adminsList').style.display = 'block';
    } else if(type === 'employees') {
        document.getElementById('employeesList').style.display = 'block';
    } else if(type === 'all') {
        document.getElementById('allList').style.display = 'block';
    }

    updateCount();
}

// Update selected count
function updateCount() {
    const checkboxes = document.querySelectorAll('.email-checkbox:checked');
    document.getElementById('selectedCount').textContent = checkboxes.length + ' selected';
}

// Select all emails in current view
function selectAll() {
    const visibleList = document.querySelector('.email-list > div[style="display: block;"]');
    if(visibleList) {
        visibleList.querySelectorAll('.email-checkbox').forEach(cb => cb.checked = true);
        updateCount();
    }
}

// Deselect all emails
function deselectAll() {
    document.querySelectorAll('.email-checkbox').forEach(cb => cb.checked = false);
    updateCount();
}

// Sidebar Toggle
const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebarBackdrop = document.getElementById('sidebarBackdrop');

sidebarToggle.addEventListener('click', function() {
    const isMobile = window.matchMedia('(max-width: 991px)').matches;
    if (isMobile) {
        const isOpen = sidebar.classList.toggle('open');
        sidebarBackdrop.classList.toggle('show', isOpen);
    } else {
        document.body.classList.toggle('sidebar-collapsed');
    }
});

sidebarBackdrop.addEventListener('click', function() {
    sidebar.classList.remove('open');
    sidebarBackdrop.classList.remove('show');
});

// Sidebar Category Collapse/Expand
document.querySelectorAll('.sidebar-nav > .sidebar-section-title').forEach(function(title) {
    // Add collapse icon with data-section attribute
    const sectionName = title.childNodes[0].textContent.trim();
    const icon = document.createElement('span');
    icon.className = 'section-collapse-icon';
    icon.textContent = '\u25BC';
    icon.setAttribute('data-section', sectionName);
    title.appendChild(icon);

    // Toggle on click
    title.addEventListener('click', function(e) {
        if (e.target.tagName === 'A') return;
        const group = this.nextElementSibling;
        if (!group || !group.classList.contains('sidebar-section-group')) return;

        const isCollapsed = group.classList.toggle('collapsed');
        const ico = this.querySelector('.section-collapse-icon');
        if (ico) ico.classList.toggle('collapsed', isCollapsed);
    });

    // Restore state
    const saved = null;
    const group = title.nextElementSibling;
    if (saved === 'collapsed' && group && group.classList.contains('sidebar-section-group')) {
        group.classList.add('collapsed');
        const ico = title.querySelector('.section-collapse-icon');
        if (ico) ico.classList.add('collapsed');
    }
});
</script>

</body>
</html>
