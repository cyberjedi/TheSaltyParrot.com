<?php
/**
 * Discord Redirect Helper for New UI
 * 
 * Handles redirecting between Discord OAuth and the new UI
 */

// Start the session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set cookie for 5 minutes to ensure we return to new UI after OAuth flow
setcookie('tsp_new_ui_redirect', 'true', time() + 300, '/');

// Store original URL to return to after authentication
if (isset($_GET['return_to'])) {
    $_SESSION['discord_new_ui_return'] = $_GET['return_to'];
} else {
    $_SESSION['discord_new_ui_return'] = '/index_new.php';
}

// Log the redirect
error_log('Setting up new UI redirect to Discord OAuth. Return path: ' . $_SESSION['discord_new_ui_return']);

// Redirect to the existing Discord login flow
header('Location: discord-login.php');
exit;
?>