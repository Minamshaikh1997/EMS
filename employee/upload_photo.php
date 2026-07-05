<?php
session_start();

if(!isset($_SESSION['employee_id']))
{
    header("Location: ../index.php");
    exit();
}

include("../config/db.php");

$employee_id = $_SESSION['employee_id'];

$result = mysqli_query($conn,"
SELECT *
FROM employees
WHERE id='$employee_id'
LIMIT 1
");

$employee = mysqli_fetch_assoc($result);

if(isset($_POST['upload']))
{

    if(isset($_FILES['photo']) && $_FILES['photo']['error']==0)
    {

        $fileName = $_FILES['photo']['name'];
        $tmpName = $_FILES['photo']['tmp_name'];
        $size = $_FILES['photo']['size'];

        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowed = array("jpg","jpeg","png");

        if(!in_array($extension,$allowed))
        {

            echo "<script>
            alert('Only JPG, JPEG and PNG files are allowed.');
            </script>";

        }
        elseif($size > 2097152)
        {

            echo "<script>
            alert('Maximum file size is 2MB.');
            </script>";

        }
        else
        {

            $newName = "EMP_".$employee_id."_".time().".".$extension;

            $uploadPath = "../uploads/".$newName;

            if(move_uploaded_file($tmpName, $uploadPath))
            {

                // Delete Old Photo
                if(!empty($employee['photo']))
                {

                    $oldPhoto = "../uploads/".$employee['photo'];

                    if(file_exists($oldPhoto))
                    {
                        unlink($oldPhoto);
                    }

                }

                mysqli_query($conn,"
                UPDATE employees
                SET photo='$newName'
                WHERE id='$employee_id'
                ");

                echo "<script>
                alert('Profile Photo Updated Successfully');
                window.location='upload_photo.php';
                </script>";

                exit();

            }
            else
            {

                echo "<script>
                alert('Photo upload failed.');
                </script>";

            }

        }

    }

}

?>
<!DOCTYPE html>

<html>

<head>

<title>Upload Profile Photo</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<?php include("../dark_mode.php"); ?>

<body class="bg-light">

<div class="container mt-5">

<div class="card shadow">

<div class="card-header bg-primary text-white">

<h3>Upload Profile Photo</h3>

</div>

<div class="card-body">

<div class="text-center mb-4">

<?php

if(!empty($employee['photo']))
{

?>

<img
src="../uploads/<?php echo $employee['photo']; ?>"
width="180"
height="180"
class="rounded-circle border">

<?php

}
else
{

?>

<img
src="https://via.placeholder.com/180"
class="rounded-circle border">

<?php

}

?>

</div>

<form method="POST" enctype="multipart/form-data">

<div class="mb-3">

<label>Select Photo</label>

<input
type="file"
name="photo"
class="form-control"
accept=".jpg,.jpeg,.png"
required>

</div>

<button
type="submit"
name="upload"
class="btn btn-success">

Upload Photo

</button>

<a
href="dashboard.php"
class="btn btn-secondary">

Back Dashboard

</a>

</form>

</div>

</div>

</div>

</body>

</html>