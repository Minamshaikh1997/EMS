<?php
session_start();

if (!isset($_SESSION['employee_id'])) {
    header("Location: login.php");
    exit();
}

include("../config/db.php");

if(isset($_POST['apply']))
{
    $employee_id = $_SESSION['employee_id'];
    $leave_type = $_POST['leave_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = $_POST['reason'];

    // Check if End Date is before Start Date
    if(strtotime($end_date) < strtotime($start_date))
    {
        $msg = "End Date cannot be earlier than Start Date.";
    }
    else
    {
        // Calculate Total Days
        $days = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) + 1;

        // Get Employee Leave Balance
        $result = mysqli_query($conn, "SELECT * FROM employees WHERE id='$employee_id'");
        $emp = mysqli_fetch_assoc($result);

        if($leave_type == "Annual")
        {
            $balance = $emp['annual_leave'];
        }
        elseif($leave_type == "Sick")
        {
            $balance = $emp['sick_leave'];
        }
        else
        {
            $balance = $emp['casual_leave'];
        }

        // Check Leave Balance
        if($days > $balance)
        {
            $msg = "You only have $balance $leave_type Leave(s) remaining.";
        }
        else
        {
            $sql = "INSERT INTO leave_requests
            (employee_id, leave_type, start_date, end_date, total_days, reason)
            VALUES
            ('$employee_id','$leave_type','$start_date','$end_date','$days','$reason')";

            if(mysqli_query($conn, $sql))
            {
                $msg = "Leave Applied Successfully.";
            }
            else
            {
                $msg = "Error: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>

    <title>Apply Leave</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<?php include("../dark_mode.php"); ?>

<body class="bg-light">

<div class="container mt-5">

<div class="card shadow">

<div class="card-header bg-primary text-white">
<h3>Apply Leave</h3>
</div>

<div class="card-body">

<?php
if(isset($msg))
{
    if(strpos($msg, "Successfully") !== false)
    {
        echo "<div class='alert alert-success'>$msg</div>";
    }
    else
    {
        echo "<div class='alert alert-danger'>$msg</div>";
    }
}
?>

<form method="POST">

<div class="mb-3">
<label>Leave Type</label>

<select name="leave_type" class="form-control" required>

<option value="">Select Leave</option>
<option value="Annual">Annual Leave</option>
<option value="Sick">Sick Leave</option>
<option value="Casual">Casual Leave</option>

</select>

</div>

<div class="mb-3">

<label>Start Date</label>

<input type="date" name="start_date" class="form-control" required>

</div>

<div class="mb-3">

<label>End Date</label>

<input type="date" name="end_date" class="form-control" required>

</div>

<div class="mb-3">

<label>Reason</label>

<textarea name="reason" class="form-control" rows="4" required></textarea>

</div>

<button type="submit" name="apply" class="btn btn-success">
Apply Leave
</button>

<a href="dashboard.php" class="btn btn-secondary">
Back
</a>

</form>

</div>

</div>

</div>

</body>
</html>