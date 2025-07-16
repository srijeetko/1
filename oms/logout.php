<?php
session_start();

// Clear all OMS session variables
unset($_SESSION['oms_admin_id']);
unset($_SESSION['oms_admin_email']);
unset($_SESSION['oms_admin_name']);
unset($_SESSION['oms_admin_role']);

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php?logged_out=1');
exit();
?>
