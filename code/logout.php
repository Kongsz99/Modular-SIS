<?php
session_start();  // Start the session

// Destroy all session variables and destroy the session
session_unset();
session_destroy();

// Redirect to login page after logout
header('Location: login.php');
exit();
?>
