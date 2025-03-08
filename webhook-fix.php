<?php
// File: webhook-fix.php
session_start();

// Force a redirect to the webhooks page
header('Location: discord/webhooks.php');
exit;
?>
