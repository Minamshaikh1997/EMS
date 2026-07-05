<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

include("../config/db.php");

if(isset($_GET['id']))
{

    $id = intval($_GET['id']);

    // Get Leave Request
    $leave_result = mysqli_query($conn,"
        SELECT *
        FROM leave_requests
        WHERE id='$id'
        LIMIT 1
    ");

    if(mysqli_num_rows($leave_result)==0)
    {
        header("Location: leave_requests.php");
        exit();
    }

    $leave = mysqli_fetch_assoc($leave_result);

    // Already Approved
    if($leave['status']!="Pending")
    {
        header("Location: leave_requests.php");
        exit();
    }

    $employee_id = $leave['employee_id'];
    $days = $leave['total_days'];
    $type = trim($leave['leave_type']);

    // Get Leave Balance
    $balance_result = mysqli_query($conn,"
        SELECT *
        FROM leave_balance
        WHERE employee_id='$employee_id'
        LIMIT 1
    ");

    if(mysqli_num_rows($balance_result)==0)
    {

        mysqli_query($conn,"
            INSERT INTO leave_balance
            (
                employee_id,
                casual_leave,
                sick_leave,
                annual_leave
            )
            VALUES
            (
                '$employee_id',
                12,
                10,
                20
            )
        ");

        $balance_result = mysqli_query($conn,"
            SELECT *
            FROM leave_balance
            WHERE employee_id='$employee_id'
            LIMIT 1
        ");

    }

    $balance = mysqli_fetch_assoc($balance_result);


    // ==========================
    // Annual Leave
    // ==========================

    if($type=="Annual")
    {

        if($balance['annual_leave'] < $days)
        {
            echo "<script>
            alert('Employee does not have enough Annual Leave.');
            window.location='leave_requests.php';
            </script>";
            exit();
        }

        mysqli_query($conn,"
        UPDATE leave_balance
        SET annual_leave = annual_leave - $days
        WHERE employee_id='$employee_id'
        ");

    }

    // ==========================
    // Sick Leave
    // ==========================

    elseif($type=="Sick")
    {

        if($balance['sick_leave'] < $days)
        {
            echo "<script>
            alert('Employee does not have enough Sick Leave.');
            window.location='leave_requests.php';
            </script>";
            exit();
        }

        mysqli_query($conn,"
        UPDATE leave_balance
        SET sick_leave = sick_leave - $days
        WHERE employee_id='$employee_id'
        ");

    }

    // ==========================
    // Casual Leave
    // ==========================

    elseif($type=="Casual")
    {

        if($balance['casual_leave'] < $days)
        {
            echo "<script>
            alert('Employee does not have enough Casual Leave.');
            window.location='leave_requests.php';
            </script>";
            exit();
        }

        mysqli_query($conn,"
        UPDATE leave_balance
        SET casual_leave = casual_leave - $days
        WHERE employee_id='$employee_id'
        ");

    }

    // ==========================
    // Invalid Leave Type
    // ==========================

    else
    {
        echo "<script>
        alert('Invalid Leave Type.');
        window.location='leave_requests.php';
        </script>";
        exit();
    }

    // ==========================
    // Approve Leave
    // ==========================

    mysqli_query($conn,"
    UPDATE leave_requests
    SET status='Approved'
    WHERE id='$id'
    ");

    echo "<script>
    alert('Leave Approved Successfully.');
    window.location='leave_requests.php';
    </script>";

    exit();

}

header("Location: leave_requests.php");
exit();

?>