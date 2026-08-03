<?php
require_once __DIR__ . '/admincheck_role.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/audit.php';

if (($admin_role ?? '') !== 'Super Admin') {
    http_response_code(403);
    exit('Access denied. Company details can only be managed by the Super Admin.');
}

$conn->query("CREATE TABLE IF NOT EXISTS company_settings (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    company_name VARCHAR(150) NOT NULL DEFAULT '',
    email VARCHAR(190) NOT NULL DEFAULT '',
    phone VARCHAR(50) NOT NULL DEFAULT '',
    website VARCHAR(255) NOT NULL DEFAULT '',
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL DEFAULT '',
    country VARCHAR(100) NOT NULL DEFAULT '',
    logo_path VARCHAR(255) DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$conn->query("INSERT IGNORE INTO company_settings (id, company_name, address) VALUES (1, 'Employee Management System', '')");

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ems_verify_csrf();
    $companyName = trim((string)($_POST['company_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $website = trim((string)($_POST['website'] ?? ''));
    $address = trim((string)($_POST['address'] ?? ''));
    $city = trim((string)($_POST['city'] ?? ''));
    $country = trim((string)($_POST['country'] ?? ''));
    $removeLogo = isset($_POST['remove_logo']) && $_POST['remove_logo'] === '1';

    if ($companyName === '') $error = 'Company name is required.';
    elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Please enter a valid company email.';
    elseif ($website !== '' && !filter_var($website, FILTER_VALIDATE_URL)) $error = 'Website must be a complete URL, for example https://example.com.';

    $logoPath = null;
    $current = $conn->query('SELECT logo_path FROM company_settings WHERE id=1')->fetch_assoc();
    $logoPath = $current['logo_path'] ?? null;
    $oldLogoPath = $logoPath;

    if ($error === '' && $removeLogo) $logoPath = null;

    if ($error === '' && isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['logo']['error'] !== UPLOAD_ERR_OK || $_FILES['logo']['size'] > 2 * 1024 * 1024) {
            $error = 'Logo upload failed or exceeds the 2 MB limit.';
        } else {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['logo']['tmp_name']);
            $extensions = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
            if (!isset($extensions[$mime])) {
                $error = 'Logo must be a PNG, JPG or WebP image.';
            } else {
                $uploadDir = __DIR__ . '/../uploads/company';
                if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                    $error = 'Logo folder could not be created.';
                } else {
                    $filename = 'logo-' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
                    if (!move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . '/' . $filename)) {
                        $error = 'Logo could not be saved.';
                    } else {
                        $newPath = 'uploads/company/' . $filename;
                        $logoPath = $newPath;
                    }
                }
            }
        }
    }

    if ($error === '') {
        $adminId = (int)($_SESSION['admin_id'] ?? 0);
        $stmt = $conn->prepare('UPDATE company_settings SET company_name=?,email=?,phone=?,website=?,address=?,city=?,country=?,logo_path=?,updated_by=? WHERE id=1');
        $stmt->bind_param('ssssssssi', $companyName, $email, $phone, $website, $address, $city, $country, $logoPath, $adminId);
        $stmt->execute();
        if ($oldLogoPath && $oldLogoPath !== $logoPath && str_starts_with($oldLogoPath, 'uploads/company/')) {
            $oldFile = __DIR__ . '/../' . $oldLogoPath;
            if (is_file($oldFile)) unlink($oldFile);
        }
        ems_audit($conn, 'company_details_updated', 'Company', 1, ['company_name' => $companyName]);
        $message = 'Company details saved successfully.';
    }
}

$company = $conn->query('SELECT * FROM company_settings WHERE id=1')->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Company Details - EMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="admin_panel.css" rel="stylesheet">
<style>
.settings-wrap{max-width:1050px;margin:0 auto}.settings-card{background:#fff;border:1px solid var(--gray-200);border-radius:16px;box-shadow:var(--shadow);overflow:hidden}.settings-head{padding:24px 28px;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff}.settings-head h3{font-weight:800;margin:0 0 5px}.settings-body{padding:28px}.form-label{font-weight:650;color:var(--gray-700)}.form-control{border-radius:10px;padding:11px 13px}.logo-preview{width:120px;height:120px;border-radius:18px;border:2px dashed var(--gray-300);object-fit:contain;padding:8px;background:#f8fafc}.logo-placeholder{display:flex;align-items:center;justify-content:center;font-size:40px;color:#94a3b8}.save-btn{border:0;border-radius:10px;padding:11px 22px;font-weight:700;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff}.dark-mode .settings-card{background:#1e293b;border-color:#334155}.dark-mode .form-label{color:#cbd5e1}.dark-mode .form-control{background:#0f172a;border-color:#334155;color:#fff}
</style>
</head>
<body>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<aside class="sidebar" id="sidebar">
 <div class="sidebar-brand"><div class="brand-icon"><i class="fa-solid fa-building"></i></div><div class="brand-text">EMS<small>Employee Management</small></div></div>
 <div class="sidebar-user"><div class="user-avatar"><?=htmlspecialchars(strtoupper(substr($admin_name,0,1)))?></div><div class="user-info"><div class="user-name"><?=htmlspecialchars($admin_name)?></div><div class="user-role"><?=htmlspecialchars($admin_role)?></div></div></div>
 <nav class="sidebar-nav">
  <div class="sidebar-section-title">Main</div><div class="sidebar-section-group">
  <a href="dashboard.php" class="sidebar-link"><i class="fa fa-gauge"></i> Dashboard</a><a href="employee.php" class="sidebar-link"><i class="fa fa-users"></i> Employees</a><a href="reports.php" class="sidebar-link"><i class="fa fa-chart-column"></i> Reports</a></div>
  <div class="sidebar-section-title">System</div><div class="sidebar-section-group">
  <a href="add_notice.php" class="sidebar-link"><i class="fa fa-bullhorn"></i> Notices</a><a href="add_holiday.php" class="sidebar-link"><i class="fa fa-plane"></i> Holidays</a><a href="send_email.php" class="sidebar-link"><i class="fa fa-envelope"></i> Send Email</a><a href="security_audit.php" class="sidebar-link"><i class="fa fa-shield-halved"></i> Security Audit</a><a href="company_details.php" class="sidebar-link active"><i class="fa fa-building-circle-check"></i> Company Details</a><a href="change_password.php" class="sidebar-link"><i class="fa fa-key"></i> Change Password</a><a href="logout.php" class="sidebar-link"><i class="fa fa-right-from-bracket"></i> Logout</a></div>
 </nav>
</aside>
<div class="main-content" id="mainContent">
 <header class="header"><div class="header-left"><button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><i class="fa fa-bars"></i></button><h4>Company Details <span>/ Settings</span></h4></div><div class="header-right"><span class="header-admin-badge"><i class="fa fa-crown"></i> Super Admin</span><?php $darkModeInTopbar=true; include __DIR__.'/../dark_mode.php'; ?><a href="logout.php" class="btn btn-danger btn-sm rounded-pill px-3"><i class="fa fa-right-from-bracket"></i> Logout</a></div></header>
 <div class="page-content"><div class="settings-wrap">
  <?php if($message): ?><div class="alert alert-success"><i class="fa fa-circle-check me-2"></i><?=htmlspecialchars($message)?></div><?php endif; ?>
  <?php if($error): ?><div class="alert alert-danger"><i class="fa fa-circle-exclamation me-2"></i><?=htmlspecialchars($error)?></div><?php endif; ?>
  <div class="settings-card"><div class="settings-head"><h3><i class="fa fa-building me-2"></i>Company Profile</h3><div>Details used for your organization and official records.</div></div>
  <form class="settings-body" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars(ems_csrf_token())?>">
   <div class="row g-4"><div class="col-md-3"><label class="form-label">Company Logo</label><div class="mb-3"><?php if(!empty($company['logo_path'])): ?><img class="logo-preview" src="../<?=htmlspecialchars($company['logo_path'])?>" alt="Company logo"><?php else: ?><div class="logo-preview logo-placeholder"><i class="fa fa-building"></i></div><?php endif; ?></div><input class="form-control" type="file" name="logo" accept="image/png,image/jpeg,image/webp"><small class="text-muted">PNG, JPG or WebP. Max 2 MB.</small><?php if(!empty($company['logo_path'])): ?><div class="form-check mt-3"><input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="removeLogo"><label class="form-check-label text-danger fw-semibold" for="removeLogo"><i class="fa fa-trash-can me-1"></i>Remove current logo</label></div><?php endif; ?></div>
   <div class="col-md-9"><div class="row g-3"><div class="col-md-6"><label class="form-label">Company Name *</label><input class="form-control" name="company_name" required maxlength="150" value="<?=htmlspecialchars($company['company_name'])?>"></div><div class="col-md-6"><label class="form-label">Company Email</label><input class="form-control" type="email" name="email" maxlength="190" value="<?=htmlspecialchars($company['email'])?>"></div><div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" maxlength="50" value="<?=htmlspecialchars($company['phone'])?>"></div><div class="col-md-6"><label class="form-label">Website</label><input class="form-control" type="url" name="website" placeholder="https://example.com" value="<?=htmlspecialchars($company['website'])?>"></div><div class="col-12"><label class="form-label">Address</label><textarea class="form-control" name="address" rows="3"><?=htmlspecialchars($company['address'])?></textarea></div><div class="col-md-6"><label class="form-label">City</label><input class="form-control" name="city" maxlength="100" value="<?=htmlspecialchars($company['city'])?>"></div><div class="col-md-6"><label class="form-label">Country</label><input class="form-control" name="country" maxlength="100" value="<?=htmlspecialchars($company['country'])?>"></div></div></div></div>
   <div class="text-end mt-4"><button class="save-btn" type="submit"><i class="fa fa-floppy-disk me-2"></i>Save Company Details</button></div>
  </form></div>
 </div></div>
</div>
<script>
const sidebar=document.getElementById('sidebar'),toggle=document.getElementById('sidebarToggle'),backdrop=document.getElementById('sidebarBackdrop');toggle?.addEventListener('click',()=>{if(innerWidth<=1024){sidebar.classList.toggle('open');backdrop.classList.toggle('show')}else document.body.classList.toggle('sidebar-collapsed')});backdrop?.addEventListener('click',()=>{sidebar.classList.remove('open');backdrop.classList.remove('show')});document.querySelectorAll('.sidebar-section-title').forEach(t=>t.addEventListener('click',()=>t.nextElementSibling?.classList.toggle('collapsed')));
</script>
</body></html>
