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

// Check if employee can view payroll
$emp_result = mysqli_query($conn, "SELECT can_view_payroll FROM employees WHERE id='$employee_id' LIMIT 1");
$emp_data = mysqli_fetch_assoc($emp_result);
$can_view_payroll = $emp_data['can_view_payroll'] ?? 1;

if (!$can_view_payroll) {
    header("Location: dashboard.php?error=no_payroll_access");
    exit();
}

// Fetch employee details
$result = mysqli_query($conn, "SELECT * FROM employees WHERE id='$employee_id' LIMIT 1");
if (!$result || mysqli_num_rows($result) === 0) {
    header("Location: ../index.php?error=invalid_session");
    exit();
}
$employee = mysqli_fetch_assoc($result);

$photo = !empty($employee['photo']) ? $employee['photo'] : 'default.png';
$designation = $employee['designation'] ?? $employee_role;

// Fetch salary slips for this employee
$salary_slips = mysqli_query($conn, "
    SELECT * FROM salary_slips 
    WHERE employee_id='$employee_id' 
    ORDER BY created_at DESC 
    LIMIT 12
");

// Fetch salary structure if exists
$salary_structure = mysqli_query($conn, "
    SELECT * FROM salary_structure 
    WHERE employee_id='$employee_id' 
    LIMIT 1
");
$structure = mysqli_fetch_assoc($salary_structure);

// Calculate totals from salary slips
$total_earnings = 0;
$total_deductions = 0;
$total_net_salary = 0;
$slip_count = 0;

if ($salary_slips) {
    while ($slip = mysqli_fetch_assoc($salary_slips)) {
        $total_earnings += $slip['total_earnings'] ?? 0;
        $total_deductions += $slip['total_deductions'] ?? 0;
        $total_net_salary += $slip['net_salary'] ?? 0;
        $slip_count++;
    }
    // Reset pointer
    mysqli_data_seek($salary_slips, 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Payroll - <?php echo htmlspecialchars($employee_name); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="../admin/admin_panel.css" rel="stylesheet">
<style>
/* Employee payroll specific styles */
.welcome-banner {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 40%, #7c3aed 100%);
    border-radius: var(--radius);
    padding: 32px 36px;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(37,99,235,.25);
}

.welcome-banner::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 400px;
    height: 400px;
    background: rgba(255,255,255,.04);
    border-radius: 50%;
}

.welcome-banner-content {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 24px;
}

.welcome-banner .profile-img {
    width: 72px;
    height: 72px;
    border-radius: 16px;
    border: 3px solid rgba(255,255,255,.3);
    object-fit: cover;
    flex-shrink: 0;
    box-shadow: 0 4px 16px rgba(0,0,0,.2);
}

.welcome-banner .welcome-text h2 {
    font-size: 24px;
    font-weight: 700;
    color: white;
    margin-bottom: 4px;
}

.welcome-banner .welcome-text p {
    color: rgba(255,255,255,.8);
    font-size: 14px;
    margin: 0;
}

.welcome-banner .welcome-text p strong { color: white; }

.salary-card {
    background: white;
    border-radius: var(--radius);
    border: 1px solid var(--gray-200);
    box-shadow: var(--shadow);
    padding: 24px;
    margin-bottom: 20px;
    transition: all .3s ease;
}

.salary-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.salary-card .card-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-bottom: 16px;
}

.salary-card .card-icon.earnings { background: rgba(16,185,129,.12); color: var(--success); }
.salary-card .card-icon.deductions { background: rgba(239,68,68,.12); color: var(--danger); }
.salary-card .card-icon.net { background: rgba(37,99,235,.12); color: var(--primary); }
.salary-card .card-icon.count { background: rgba(245,158,11,.12); color: var(--warning); }

.salary-card .card-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: .5px;
    margin-bottom: 8px;
}

.salary-card .card-value {
    font-size: 32px;
    font-weight: 800;
    color: var(--gray-900);
    line-height: 1;
    margin-bottom: 4px;
}

.salary-card .card-sub {
    font-size: 13px;
    color: var(--gray-500);
}

.slip-card {
    background: white;
    border-radius: var(--radius);
    border: 1px solid var(--gray-200);
    box-shadow: var(--shadow);
    padding: 20px;
    margin-bottom: 16px;
    transition: all .3s ease;
}

.slip-card:hover {
    box-shadow: var(--shadow-md);
    border-color: var(--primary);
}

.slip-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 2px solid var(--gray-100);
}

.slip-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--gray-800);
    margin: 0;
}

.slip-date {
    font-size: 12px;
    color: var(--gray-500);
}

.slip-details {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-bottom: 16px;
}

.slip-detail-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 12px;
    background: var(--gray-50);
    border-radius: 8px;
}

.slip-detail-label {
    font-size: 13px;
    color: var(--gray-600);
    font-weight: 500;
}

.slip-detail-value {
    font-size: 13px;
    color: var(--gray-900);
    font-weight: 600;
}

.slip-total {
    background: linear-gradient(135deg, #2563eb, #3b82f6);
    color: white;
    padding: 12px 16px;
    border-radius: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.slip-total-label {
    font-size: 14px;
    font-weight: 600;
}

.slip-total-value {
    font-size: 20px;
    font-weight: 800;
}

.structure-card {
    background: white;
    border-radius: var(--radius);
    border: 1px solid var(--gray-200);
    box-shadow: var(--shadow);
    padding: 24px;
    margin-bottom: 20px;
}

.structure-header {
    font-size: 18px;
    font-weight: 700;
    color: var(--gray-800);
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid var(--gray-100);
    display: flex;
    align-items: center;
    gap: 10px;
}

.structure-header i { color: var(--primary); }

.structure-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.structure-item {
    padding: 16px;
    background: var(--gray-50);
    border-radius: 10px;
    border-left: 4px solid var(--primary);
}

.structure-item-label {
    font-size: 12px;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: .5px;
    margin-bottom: 6px;
    font-weight: 600;
}

.structure-item-value {
    font-size: 20px;
    font-weight: 700;
    color: var(--gray-900);
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--gray-500);
}

.empty-state i {
    font-size: 64px;
    margin-bottom: 16px;
    opacity: 0.3;
}

.empty-state h5 {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--gray-600);
}

.empty-state p {
    font-size: 14px;
    margin: 0;
}

/* Dark mode overrides */
.dark-mode .salary-card,
.dark-mode .slip-card,
.dark-mode .structure-card { background: #1e293b; border-color: rgba(255,255,255,.08); }
.dark-mode .salary-card .card-value,
.dark-mode .slip-title,
.dark-mode .structure-header,
.dark-mode .structure-item-value { color: #e2e8f0; }
.dark-mode .slip-detail-item { background: rgba(255,255,255,.04); }
.dark-mode .slip-detail-label { color: var(--gray-400); }
.dark-mode .slip-detail-value { color: #e2e8f0; }
.dark-mode .structure-item { background: rgba(255,255,255,.04); }
.dark-mode .structure-item-label { color: var(--gray-400); }

@media (max-width: 768px) {
    .welcome-banner { padding: 24px; }
    .welcome-banner-content { flex-wrap: wrap; }
    .slip-details { grid-template-columns: 1fr; }
    .structure-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<!-- Sidebar Backdrop -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="fa-solid fa-building"></i></div>
        <div class="brand-text">
            EMS
            <small>Employee Portal</small>
        </div>
    </div>
    <div class="sidebar-user">
        <div class="user-avatar"><?php echo strtoupper(substr($employee_name, 0, 1)); ?></div>
        <div class="user-info">
            <div class="user-name"><?php echo htmlspecialchars($employee_name); ?></div>
            <div class="user-role"><?php echo htmlspecialchars($employee_role ?: 'Employee'); ?></div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="sidebar-section-title">Main</div>
        <div class="sidebar-section-group">
        <a href="dashboard.php" class="sidebar-link"><i class="fa fa-gauge"></i> Dashboard</a>
        <a href="attendance.php" class="sidebar-link"><i class="fa fa-clock"></i> Attendance</a>
        <a href="attendance_history.php" class="sidebar-link"><i class="fa fa-history"></i> Attendance History</a>
        <a href="apply_leave.php" class="sidebar-link"><i class="fa fa-calendar-plus"></i> Apply Leave</a>
        <a href="leave_history.php" class="sidebar-link"><i class="fa fa-list"></i> Leave History</a>
        <a href="leave_balance.php" class="sidebar-link"><i class="fa fa-chart-pie"></i> Leave Balance</a>
        <a href="submit_adjustment.php" class="sidebar-link"><i class="fa fa-pen-alt"></i> Submit Adjustment</a>
        <a href="my_adjustments.php" class="sidebar-link"><i class="fa fa-clipboard-list"></i> My Adjustments</a>
        </div>

        <div class="sidebar-section-title">Profile</div>
        <div class="sidebar-section-group">
        <a href="edit_profile.php" class="sidebar-link"><i class="fa fa-user-edit"></i> Edit Profile</a>
        <a href="upload_photo.php" class="sidebar-link"><i class="fa fa-camera"></i> Upload Photo</a>
        <a href="change_password.php" class="sidebar-link"><i class="fa fa-key"></i> Change Password</a>
        </div>

        <div class="sidebar-section-title">Payroll</div>
        <div class="sidebar-section-group">
        <a href="my_payroll.php" class="sidebar-link active"><i class="fa-solid fa-money-bill-wave"></i> My Payroll</a>
        </div>

        <div class="sidebar-section-title">System</div>
        <div class="sidebar-section-group">
        <a href="logout.php" class="sidebar-link"><i class="fa fa-right-from-bracket"></i> Logout</a>
        </div>
    </nav>
</aside>

<!-- Main Content -->
<div class="main-content" id="mainContent">

    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                <i class="fa fa-bars"></i>
            </button>
            <h4>My Payroll <span>/ Salary Information</span></h4>
        </div>
        <div class="header-right">
            <span class="header-date"><i class="fa-regular fa-calendar"></i> <?=date('d M Y')?></span>
            <span class="header-admin-badge"><i class="fa fa-user"></i> <span><?php echo htmlspecialchars($employee_name); ?></span></span>
            <?php $darkModeInTopbar = true; include("../dark_mode.php"); ?>
            <a href="logout.php" class="btn btn-danger btn-sm rounded-pill px-3">
                <i class="fa fa-sign-out-alt"></i> <span>Logout</span>
            </a>
        </div>
    </header>

    <!-- Page Content -->
    <div class="page-content">

        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="welcome-banner-content">
                <img src="../uploads/<?php echo $photo; ?>" alt="Profile" class="profile-img" onerror="this.src='https://ui-avatars.com/api/?name=<?=urlencode($employee_name)?>&background=2563eb&color=fff&size=72'">
                <div class="welcome-text">
                    <h2>My Payroll Information</h2>
                    <p>Designation: <strong><?php echo htmlspecialchars($designation); ?></strong></p>
                    <p>Employee ID: <strong><?php echo htmlspecialchars($employee['employee_id']); ?></strong></p>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="salary-card">
                    <div class="card-icon earnings"><i class="fas fa-arrow-trend-up"></i></div>
                    <div class="card-label">Total Earnings</div>
                    <div class="card-value">Rs. <?php echo number_format($total_earnings, 2); ?></div>
                    <div class="card-sub">Last <?php echo $slip_count; ?> months</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="salary-card">
                    <div class="card-icon deductions"><i class="fas fa-arrow-trend-down"></i></div>
                    <div class="card-label">Total Deductions</div>
                    <div class="card-value">Rs. <?php echo number_format($total_deductions, 2); ?></div>
                    <div class="card-sub">Last <?php echo $slip_count; ?> months</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="salary-card">
                    <div class="card-icon net"><i class="fas fa-wallet"></i></div>
                    <div class="card-label">Net Salary Received</div>
                    <div class="card-value">Rs. <?php echo number_format($total_net_salary, 2); ?></div>
                    <div class="card-sub">Last <?php echo $slip_count; ?> months</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="salary-card">
                    <div class="card-icon count"><i class="fas fa-file-invoice"></i></div>
                    <div class="card-label">Salary Slips</div>
                    <div class="card-value"><?php echo $slip_count; ?></div>
                    <div class="card-sub">Available records</div>
                </div>
            </div>
        </div>

        <!-- Salary Structure -->
        <?php if ($structure): ?>
        <div class="structure-card">
            <div class="structure-header">
                <i class="fas fa-sitemap"></i>
                Salary Structure
            </div>
            <div class="structure-grid">
                <div class="structure-item">
                    <div class="structure-item-label">Basic Salary</div>
                    <div class="structure-item-value">Rs. <?php echo number_format($structure['basic_salary'] ?? 0, 2); ?></div>
                </div>
                <div class="structure-item">
                    <div class="structure-item-label">House Rent Allowance</div>
                    <div class="structure-item-value">Rs. <?php echo number_format($structure['hra'] ?? 0, 2); ?></div>
                </div>
                <div class="structure-item">
                    <div class="structure-item-label">Transport Allowance</div>
                    <div class="structure-item-value">Rs. <?php echo number_format($structure['transport_allowance'] ?? 0, 2); ?></div>
                </div>
                <div class="structure-item">
                    <div class="structure-item-label">Medical Allowance</div>
                    <div class="structure-item-value">Rs. <?php echo number_format($structure['medical_allowance'] ?? 0, 2); ?></div>
                </div>
                <div class="structure-item">
                    <div class="structure-item-label">Gross Salary</div>
                    <div class="structure-item-value">Rs. <?php echo number_format($structure['gross_salary'] ?? 0, 2); ?></div>
                </div>
                <div class="structure-item">
                    <div class="structure-item-label">Tax Deduction</div>
                    <div class="structure-item-value">Rs. <?php echo number_format($structure['tax_deduction'] ?? 0, 2); ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Salary Slips -->
        <div class="card-modern">
            <div class="card-header-custom">
                <h6><i class="fas fa-file-invoice"></i> Recent Salary Slips</h6>
            </div>
            <div class="card-body-custom">
                <?php if ($salary_slips && mysqli_num_rows($salary_slips) > 0): ?>
                    <?php while ($slip = mysqli_fetch_assoc($salary_slips)): ?>
                    <div class="slip-card">
                        <div class="slip-header">
                            <div>
                                <div class="slip-title"><?php echo date('F Y', strtotime($slip['salary_month'] . '-01')); ?></div>
                                <div class="slip-date">Generated on: <?php echo date('d M Y', strtotime($slip['created_at'])); ?></div>
                            </div>
                            <span class="badge bg-<?php echo ($slip['status'] == 'Paid') ? 'success' : 'warning'; ?>">
                                <?php echo htmlspecialchars($slip['status']); ?>
                            </span>
                        </div>
                        <div class="slip-details">
                            <div class="slip-detail-item">
                                <span class="slip-detail-label">Basic Salary</span>
                                <span class="slip-detail-value">Rs. <?php echo number_format($slip['basic_salary'] ?? 0, 2); ?></span>
                            </div>
                            <div class="slip-detail-item">
                                <span class="slip-detail-label">Allowances</span>
                                <span class="slip-detail-value">Rs. <?php echo number_format($slip['allowances'] ?? 0, 2); ?></span>
                            </div>
                            <div class="slip-detail-item">
                                <span class="slip-detail-label">Overtime</span>
                                <span class="slip-detail-value">Rs. <?php echo number_format($slip['overtime'] ?? 0, 2); ?></span>
                            </div>
                            <div class="slip-detail-item">
                                <span class="slip-detail-label">Bonus</span>
                                <span class="slip-detail-value">Rs. <?php echo number_format($slip['bonus'] ?? 0, 2); ?></span>
                            </div>
                            <div class="slip-detail-item">
                                <span class="slip-detail-label">Tax Deduction</span>
                                <span class="slip-detail-value">-Rs. <?php echo number_format($slip['tax_deduction'] ?? 0, 2); ?></span>
                            </div>
                            <div class="slip-detail-item">
                                <span class="slip-detail-label">Other Deductions</span>
                                <span class="slip-detail-value">-Rs. <?php echo number_format($slip['other_deductions'] ?? 0, 2); ?></span>
                            </div>
                        </div>
                        <div class="slip-total">
                            <span class="slip-total-label">Net Salary</span>
                            <span class="slip-total-value">Rs. <?php echo number_format($slip['net_salary'] ?? 0, 2); ?></span>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-file-invoice"></i>
                        <h5>No Salary Slips Available</h5>
                        <p>Your salary slips will appear here once generated by the payroll department.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer -->
        <footer class="text-center mt-5 mb-2 text-muted" style="font-size: 13px;">
            Employee Management System &copy; 2026 &mdash; Employee Portal
        </footer>

    </div>
</div>

<script>
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
    const sectionName = title.childNodes[0].textContent.trim();
    const icon = document.createElement('span');
    icon.className = 'section-collapse-icon';
    icon.textContent = '\u25BC';
    title.appendChild(icon);

    title.addEventListener('click', function(e) {
        if (e.target.tagName === 'A') return;
        const group = this.nextElementSibling;
        if (!group || !group.classList.contains('sidebar-section-group')) return;
        const isCollapsed = group.classList.toggle('collapsed');
        const ico = this.querySelector('.section-collapse-icon');
        if (ico) ico.classList.toggle('collapsed', isCollapsed);
        localStorage.setItem('sidebar_' + sectionName, isCollapsed ? 'collapsed' : 'expanded');
    });

    const saved = localStorage.getItem('sidebar_' + sectionName);
    const group = title.nextElementSibling;
    if (saved === 'collapsed' && group && group.classList.contains('sidebar-section-group')) {
        group.classList.add('collapsed');
        const ico = title.querySelector('.section-collapse-icon');
        if (ico) ico.classList.add('collapsed');
    }
});
</script>
<?php include __DIR__ . '/../config/back_dashboard.php'; ?>
</body>
</html>
<?php
mysqli_close($conn);
?>
