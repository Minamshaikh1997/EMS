<?php
include("config/db.php");

// Naya admin password
$newPassword = "Admin123@";

// Password ko hash karein
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// Admin table update karein
$sql = "UPDATE admin SET password='$hashedPassword' WHERE id=1";

if(mysqli_query($conn, $sql))
{
    echo "✅ Admin password has been reset successfully.<br><br>";
    echo "Email: admin@example.com <br>";
    echo "Password: Admin123@";
}
else
{
    echo "❌ Error: " . mysqli_error($conn);
}
?>