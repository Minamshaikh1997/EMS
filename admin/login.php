<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<?php include("../dark_mode.php"); ?>

<body class="bg-light">
<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card shadow">
        <div class="card-header bg-primary text-white">
          <h3 class="mb-0">Admin Login</h3>
        </div>
        <div class="card-body">
          <form method="POST" action="../login.php">
            <div class="mb-3">
              <label>Email</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
              <label>Password</label>
              <input type="password" name="password" class="form-control" required>
            </div>
            <input type="hidden" name="role" value="admin">
            <button type="submit" class="btn btn-primary w-100">Login</button>
          </form>
          <div class="mt-3 text-center">
            <a href="../index.php">Back to main login</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
