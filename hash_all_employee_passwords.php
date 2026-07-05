<?php
include("config/db.php");

$result = mysqli_query($conn, "SELECT * FROM employees");

while($row = mysqli_fetch_assoc($result))
{
    $id = $row['id'];
    $password = $row['password'];

    // Agar password pehle se hash hai to skip karega
    if(substr($password,0,4) != '$2y$')
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        mysqli_query($conn,
        "UPDATE employees SET password='$hash' WHERE id='$id'");
    }
}

echo "All Employee Passwords Updated Successfully";
?>