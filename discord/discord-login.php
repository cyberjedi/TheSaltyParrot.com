<?php
// File: discord/discord-login.php
// Clean implementation of Discord login functionality for the new UI
require_once 'discord-config.php';

// Store the return URL in session
$_SESSION['discord_ui_return'] = '../index.php';

// Use HTTP_REFERER if available
if (isset($_SERVER['HTTP_REFERER'])) {
    // Store the referring page
    $_SESSION['discord_ui_return'] = $_SERVER['HTTP_REFERER'];
}

// Set a special flag to identify this auth request source
$_SESSION['from_ui'] = true;

// Log the authentication attempt
error_log('Discord auth requested. Return URL: ' . $_SESSION['discord_ui_return']);

// Generate a random state parameter to prevent CSRF attacks
$state = bin2hex(random_bytes(16));
$_SESSION['discord_oauth_state'] = $state;

// Build the authorization URL
$auth_url = DISCORD_API_URL . '/oauth2/authorize';
$auth_url .= '?client_id=' . DISCORD_CLIENT_ID;
$auth_url .= '&redirect_uri=' . urlencode(DISCORD_REDIRECT_URI);
$auth_url .= '&response_type=code';
$auth_url .= '&state=' . $state;
$auth_url .= '&scope=identify%20guilds';
$auth_url .= '&prompt=consent'; // Always show consent screen for clarity

// Add tracking parameter
$auth_url .= '&custom_source=ui';

// Direct redirect to Discord
header('Location: ' . $auth_url);
exit;
?>