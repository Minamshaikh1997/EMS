<?php
session_start();

if(!isset($_SESSION['employee_id']))
{
    header("Location: ../index.php");
    exit();
}

include("../config/db.php");

$employee_id = $_SESSION['employee_id'];

$query = mysqli_query($conn,"
SELECT *
FROM leave_balance
WHERE employee_id='$employee_id'
LIMIT 1
");

if(mysqli_num_rows($query)>0)
{
    $balance = mysqli_fetch_assoc($query);
}
else
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

    $query = mysqli_query($conn,"
    SELECT *
    FROM leave_balance
    WHERE employee_id='$employee_id'
    LIMIT 1
    ");

    $balance = mysqli_fetch_assoc($query);
}
?>