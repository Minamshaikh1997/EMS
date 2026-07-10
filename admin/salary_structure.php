<?php
session_start();

include("admincheck_role.php");
include("../config/db.php");

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.html");
    exit();
}

// Delete Salary Structure
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    mysqli_query($conn, "DELETE FROM salary_structure_components WHERE salary_structure_id='$id'");
    mysqli_query($conn, "DELETE FROM salary_structure WHERE id='$id'");

    header("Location: salary_structure.php");
    exit();
}

// Save Salary Structure
if (isset($_POST['save'])) {
    $employee_id = intval($_POST['employee_id']);
    $basic_salary = floatval($_POST['basic_salary']);
    $component_amounts = $_POST['component_amount'] ?? [];

    $check = mysqli_query($conn, "SELECT id FROM salary_structure WHERE employee_id='$employee_id'");

    if (mysqli_num_rows($check) > 0) {
        $salaryRow = mysqli_fetch_assoc($check);
        $salary_structure_id = $salaryRow['id'];

        mysqli_query($conn, "
            UPDATE salary_structure SET
            basic_salary='$basic_salary',
            house_allowance='0',
            medical_allowance='0',
            transport_allowance='0',
            other_allowance='0',
            tax_deduction='0',
            other_deduction='0'
            WHERE id='$salary_structure_id'
        ");

        mysqli_query($conn, "DELETE FROM salary_structure_components WHERE salary_structure_id='$salary_structure_id'");

        $message = "<div class='alert alert-success'>Salary Updated Successfully.</div>";
    } else {
        mysqli_query($conn, "
            INSERT INTO salary_structure
            (employee_id, basic_salary, house_allowance, medical_allowance, transport_allowance, other_allowance, tax_deduction, other_deduction)
            VALUES
            ('$employee_id', '$basic_salary', '0', '0', '0', '0', '0', '0')
        ");

        $salary_structure_id = mysqli_insert_id($conn);

        $message = "<div class='alert alert-success'>Salary Saved Successfully.</div>";
    }

    foreach ($component_amounts as $component_id => $amount) {
        $component_id = intval($component_id);
        $amount = floatval($amount);

        if ($amount > 0) {
            mysqli_query($conn, "
                INSERT INTO salary_structure_components
                (salary_structure_id, component_id, amount)
                VALUES
                ('$salary_structure_id', '$component_id', '$amount')
            ");
        }
    }
}

// Employee List
$employees = mysqli_query($conn, "SELECT id, employee_id, full_name FROM employees ORDER BY full_name ASC");

// Salary Components
$allowanceComponents = mysqli_query($conn, "
    SELECT * FROM salary_components
    WHERE component_type='Allowance'
    AND status='Active'
    ORDER BY component_name ASC
");

$deductionComponents = mysqli_query($conn, "
    SELECT * FROM salary_components
    WHERE component_type='Deduction'
    AND status='Active'
    ORDER BY component_name ASC
");

// Salary Structure List
$salaryList = mysqli_query($conn, "
    SELECT
        s.id,
        s.basic_salary,
        e.employee_id,
        e.full_name,

        IFNULL((
            SELECT SUM(ssc.amount)
            FROM salary_structure_components ssc
            INNER JOIN salary_components sc ON ssc.component_id = sc.id
            WHERE ssc.salary_structure_id = s.id
            AND sc.component_type = 'Allowance'
        ), 0) AS total_allowance,

        IFNULL((
            SELECT SUM(ssc.amount)
            FROM salary_structure_components ssc
            INNER JOIN salary_components sc ON ssc.component_id = sc.id
            WHERE ssc.salary_structure_id = s.id
            AND sc.component_type = 'Deduction'
        ), 0) AS total_deduction

    FROM salary_structure s
    INNER JOIN employees e ON s.employee_id = e.id
    ORDER BY e.full_name ASC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Salary Structure</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

<style>
body{
    background:#f4f7fc;
}

.sidebar{
    position:fixed;
    left:0;
    top:0;
    width:260px;
    height:100vh;
    background:#1f2937;
    overflow-y:auto;
}

.sidebar h3{
    color:white;
    text-align:center;
    padding:20px;
}

.sidebar a{
    display:block;
    color:white;
    text-decoration:none;
    padding:14px 20px;
}

.sidebar a:hover{
    background:#2563eb;
}

.main{
    margin-left:260px;
}

.topbar{
    background:white;
    padding:15px 25px;
    box-shadow:0 2px 8px rgba(0,0,0,.1);
}

.card{
    border:none;
    border-radius:15px;
}
</style>
<link href="admin_panel.css" rel="stylesheet">

</head>

<body>

<div class="sidebar" id="adminSidebar">

<h3><i class="fa-solid fa-user-shield"></i> <?php echo htmlspecialchars($admin_name); ?></h3>

<div class="sidebar-section-title">Main</div>
<a href="dashboard.php"><i class="fa fa-gauge"></i> Dashboard</a>
<a href="employee.php"><i class="fa fa-users"></i> Employees</a>
<a href="add_employee.php"><i class="fa fa-user-plus"></i> Add Employee</a>
<a href="leave_requests.php"><i class="fa fa-calendar-check"></i> Leave Requests</a>
<a href="attendance_report.php"><i class="fa fa-clock"></i> Attendance</a>
<a href="reports.php"><i class="fa fa-chart-column"></i> Reports</a>

<div class="sidebar-section-title">Payroll</div>

<a href="payroll_dashboard.php"><i class="fa-solid fa-money-bill-wave"></i> Payroll Dashboard</a>
<a href="generate_payroll.php"><i class="fa fa-file-invoice-dollar"></i> Generate Payroll</a>
<a href="payroll_history.php"><i class="fa fa-clock-rotate-left"></i> Payroll History</a>
<a href="salary_structure.php" class="active"><i class="fa fa-money-bill-wave"></i> Salary Structure</a>
<a href="salary_components.php">
<i class="fa fa-list-check"></i> Salary Components
</a>
<a href="salary_slips.php">
<i class="fa fa-file-invoice-dollar"></i> Salary Slips
</a>
<a href="payroll_reports.php">
<i class="fa fa-chart-line"></i> Payroll Report
</a>
<a href="monthly_payroll.php"><i class="fa fa-calendar"></i> Monthly Payroll</a>

<div class="sidebar-section-title">System</div>
<a href="change_password.php"><i class="fa fa-key"></i> Change Password</a>
<a href="logout.php"><i class="fa fa-right-from-bracket"></i> Logout</a>

</div>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<div class="main">

<div class="topbar">
<div class="topbar-left">
<button type="button" class="btn btn-outline-primary sidebar-toggle-btn" id="sidebarToggle" aria-label="Toggle sidebar" aria-expanded="true">
<i class="fa fa-bars"></i>
</button>
<h3>Payroll Management</h3>
</div>
<div class="topbar-actions">
<span class="topbar-date"><?php echo date('d M Y'); ?></span>
<span class="admin-pill"><i class="fa fa-user-shield"></i> <?php echo htmlspecialchars($admin_name); ?></span>
<a href="logout.php" class="btn btn-danger btn-sm">
<i class="fa fa-right-from-bracket"></i> Logout
</a>
</div>
</div>

<div class="container-fluid mt-4">

<div class="card shadow">

<div class="card-header bg-success text-white">
<h4><i class="fa fa-money-bill-wave"></i> Salary Structure</h4>
</div>

<div class="card-body">

<?php
if (isset($message)) {
    echo $message;
}
?>

<form method="POST">

<div class="row">

<div class="col-md-4 mb-3">
<label class="form-label">Employee</label>

<select name="employee_id" class="form-select" required>
<option value="">Select Employee</option>

<?php while($emp = mysqli_fetch_assoc($employees)){ ?>
<option value="<?php echo $emp['id']; ?>">
<?php echo $emp['employee_id']; ?> - <?php echo $emp['full_name']; ?>
</option>
<?php } ?>

</select>
</div>

<div class="col-md-4 mb-3">
<label class="form-label">Basic Salary</label>
<input type="number" step="0.01" name="basic_salary" class="form-control" required>
</div>

</div>

<hr>

<h5 class="text-success mb-3">Allowances</h5>

<div class="row">

<?php while($component = mysqli_fetch_assoc($allowanceComponents)){ ?>

<div class="col-md-4 mb-3">
<label class="form-label"><?php echo $component['component_name']; ?></label>
<input
type="number"
step="0.01"
name="component_amount[<?php echo $component['id']; ?>]"
class="form-control"
value="0">
</div>

<?php } ?>

</div>

<hr>

<h5 class="text-danger mb-3">Deductions</h5>

<div class="row">

<?php while($component = mysqli_fetch_assoc($deductionComponents)){ ?>

<div class="col-md-4 mb-3">
<label class="form-label"><?php echo $component['component_name']; ?></label>
<input
type="number"
step="0.01"
name="component_amount[<?php echo $component['id']; ?>]"
class="form-control"
value="0">
</div>

<?php } ?>

</div>

<div class="row">

<div class="col-md-4 d-grid">
<button type="submit" name="save" class="btn btn-success">
<i class="fa fa-save"></i> Save Salary
</button>
</div>

</div>

</form>

<hr>

<div class="card mt-4">

<div class="card-header bg-primary text-white">
<h5><i class="fa fa-list"></i> Salary Structure List</h5>
</div>

<div class="card-body">

<div class="row mb-3">
<div class="col-md-4">
<input
type="text"
id="searchEmployee"
class="form-control"
placeholder="Search Employee...">
</div>
</div>

<div class="table-responsive">

<table class="table table-bordered table-hover">

<thead class="table-dark">
<tr>
<th>Employee ID</th>
<th>Name</th>
<th>Basic</th>
<th>Allowance</th>
<th>Deduction</th>
<th>Gross Salary</th>
<th>Net Salary</th>
<th>Action</th>
</tr>
</thead>

<tbody>

<?php while($row = mysqli_fetch_assoc($salaryList)){ ?>

<?php
$allowance = $row['total_allowance'];
$deduction = $row['total_deduction'];
$gross = $row['basic_salary'] + $allowance;
$net = $gross - $deduction;
?>

<tr>

<td><?php echo $row['employee_id']; ?></td>
<td><?php echo $row['full_name']; ?></td>
<td><?php echo number_format($row['basic_salary'], 2); ?></td>
<td><?php echo number_format($allowance, 2); ?></td>
<td><?php echo number_format($deduction, 2); ?></td>

<td class="text-success fw-bold">
<?php echo number_format($gross, 2); ?>
</td>

<td class="text-primary fw-bold">
<?php echo number_format($net, 2); ?>
</td>

<td>
<a
href="salary_structure.php?delete=<?php echo $row['id']; ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Are you sure you want to delete this salary structure?');">
<i class="fa fa-trash"></i>
</a>
</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

</div>

</div>

</div>

</div>

</div>

<script>
document
.getElementById("searchEmployee")
.addEventListener("keyup", function(){

    var value = this.value.toLowerCase();

    document.querySelectorAll("tbody tr").forEach(function(row){
        row.style.display = row.innerText.toLowerCase().includes(value) ? "" : "none";
    });

});
</script>

<script>
const sidebar = document.getElementById("adminSidebar");
const sidebarToggle = document.getElementById("sidebarToggle");
const sidebarBackdrop = document.getElementById("sidebarBackdrop");

if (sidebarToggle) {
    sidebarToggle.addEventListener("click", function(){
        const isMobile = window.matchMedia("(max-width: 991px)").matches;
        if (isMobile) {
            const isOpen = sidebar.classList.toggle("open");
            sidebarBackdrop.classList.toggle("show", isOpen);
            return;
        }
        const isCollapsed = sidebar.classList.toggle("sidebar-collapsed");
        sidebar.style.transform = isCollapsed ? "translateX(-100%)" : "translateX(0)";
        document.querySelector(".main").style.marginLeft = isCollapsed ? "0" : "260px";
    });
}

if (sidebarBackdrop) {
    sidebarBackdrop.addEventListener("click", function(){
        sidebar.classList.remove("open");
        sidebarBackdrop.classList.remove("show");
    });
}
</script>

</body>
</html>
