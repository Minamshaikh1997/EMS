<?php
require_once("../config/security.php");
ems_start_secure_session();

if (!isset($_SESSION['employee_id'])) {
    header("Location: login.php");
    exit();
}

include("../config/db.php");
include_once("../config/employee_rights.php");
include_once("../config/audit.php");

$employee_id = $_SESSION['employee_id'];
requireEmployeeRight($conn, $employee_id, 'can_edit_profile');
$result = mysqli_query($conn, "SELECT * FROM employees WHERE id='$employee_id'");
$row = mysqli_fetch_assoc($result);

if(isset($_POST['update']))
{
    ems_verify_csrf();
    $full_name = trim((string)($_POST['full_name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    if ($full_name === '' || strlen($full_name)>100 || !filter_var($email,FILTER_VALIDATE_EMAIL)) { http_response_code(422); exit('Invalid profile details.'); }
    try { $stmt=$conn->prepare('UPDATE employees SET full_name=?,email=? WHERE id=?');$stmt->bind_param('ssi',$full_name,$email,$employee_id);$stmt->execute();$stmt->close();ems_audit($conn,'employee.profile_updated','employee',(int)$employee_id); }
    catch(Throwable $error) { error_log('Employee profile update failed: '.$error->getMessage()); http_response_code(409); exit('Profile could not be updated. The email may already be in use.'); }

    header("Location: profile.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Profile</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<?php include("../dark_mode.php"); ?>

<body class="bg-light">

<div class="container mt-5">

<div class="card shadow">

<div class="card-header bg-primary text-white">

<h3>Edit Profile</h3>

</div>

<div class="card-body">

<form method="POST">
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(ems_csrf_token()); ?>">

<div class="mb-3">
<label>Full Name</label>
<input type="text" name="full_name" class="form-control"
value="<?php echo htmlspecialchars($row['full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
</div>

<div class="mb-3">
<label>Email</label>
<input type="email" name="email" class="form-control"
value="<?php echo htmlspecialchars($row['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
</div>

<div class="mb-3">
<label>Department</label>
<input type="text" name="department" class="form-control" readonly
value="<?php echo htmlspecialchars($row['department'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
</div>

<div class="mb-3">
<label>Designation</label>
<input type="text" name="designation" class="form-control" readonly
value="<?php echo htmlspecialchars($row['designation'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
</div>

<button type="submit" name="update" class="btn btn-success">
Update Profile
</button>

<a href="profile.php" class="btn btn-secondary">Back to Profile</a>
<a href="dashboard.php" class="btn btn-primary"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>

</form>

</div>

</div>

</div>

</body>
</html>
