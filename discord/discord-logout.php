<?php
// File: discord/discord-logout.php
// This file logs the user out of Discord

require_once 'discord-config.php';

// Clear Discord session data
unset($_SESSION['discord_user']);
unset($_SESSION['discord_access_token']);
unset($_SESSION['discord_refresh_token']);
unset($_SESSION['discord_token_expires']);

// Set a success message
$_SESSION['discord_message'] = 'Successfully logged out of Discord.';

// Get referring page if available
$redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../index.php';

// Redirect back to where the user came from
header('Location: ' . $redirect_url);
exit;
?>
