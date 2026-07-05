```php
<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

include("../config/db.php");

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Leave_Report.xls");

$department = isset($_GET['department']) ? mysqli_real_escape_string($conn,$_GET['department']) : "";
$status = isset($_GET['status']) ? mysqli_real_escape_string($conn,$_GET['status']) : "";

$sql = "SELECT
employees.employee_id,
employees.full_name,
employees.department,
leave_requests.leave_type,
leave_requests.start_date,
leave_requests.end_date,
leave_requests.total_days,
leave_requests.status
FROM leave_requests
INNER JOIN employees
ON leave_requests.employee_id = employees.id
WHERE 1=1";

if($department!="")
{
    $sql .= " AND employees.department='$department'";
}

if($status!="")
{
    $sql .= " AND leave_requests.status='$status'";
}

$sql .= " ORDER BY leave_requests.id DESC";

$result = mysqli_query($conn,$sql);

echo "<table border='1'>";

echo "<tr style='font-weight:bold;background:#cccccc;'>
<td>Employee ID</td>
<td>Employee Name</td>
<td>Department</td>
<td>Leave Type</td>
<td>Start Date</td>
<td>End Date</td>
<td>Total Days</td>
<td>Status</td>
</tr>";

while($row=mysqli_fetch_assoc($result))
{
    echo "<tr>";

    echo "<td>".$row['employee_id']."</td>";
    echo "<td>".$row['full_name']."</td>";
    echo "<td>".$row['department']."</td>";
    echo "<td>".$row['leave_type']."</td>";
    echo "<td>".$row['start_date']."</td>";
    echo "<td>".$row['end_date']."</td>";
    echo "<td>".$row['total_days']."</td>";
    echo "<td>".$row['status']."</td>";

    echo "</tr>";
}

echo "</table>";
?>
```
