<?php
include("../config/db.php");

$email = "admin@company.com";
$newPassword = "123456";

$hash = password_hash($newPassword, PASSWORD_DEFAULT);

mysqli_query($conn, "UPDATE admin SET password='$hash' WHERE email='$email'");

if(mysqli_affected_rows($conn) > 0){
    echo "Password Updated Successfully";
} else {
    echo "Admin email not found";
}
?>