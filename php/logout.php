<?php
// php/logout.php
session_start();
$_SESSION = array(); // Unset all of the session variables
session_destroy(); // Destroy the session.
header("location: ../index.html"); // Redirect to login page
exit;
?>