<?php
require_once("../config/security.php");
ems_start_secure_session();
ems_logout();

header("Location: ../index.html");
exit();
?>
