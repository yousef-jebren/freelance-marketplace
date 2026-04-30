<?php
require_once 'dp.php';
session_start();
// Clear all session data
session_unset();
session_destroy();

// Redirect to homepage
header("Location: main.php");
exit();
?>