<?php
// Show all errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Test basic PHP functionality
echo "PHP is working!";

// Test session functionality (often a source of errors)
session_start();
echo "<br>Session started successfully.";

// Show PHP info
phpinfo();
?>
