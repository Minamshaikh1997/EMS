<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

include("../config/db.php");

if(isset($_GET['id']))
{
    $id = $_GET['id'];

    mysqli_query($conn,"UPDATE leave_requests SET status='Rejected' WHERE id='$id'");
}

header("Location: leave_requests.php");
exit();
?>