<?php
// File: discord/discord-login_new.php
// Clean implementation of Discord login functionality
require_once 'discord-config.php';

// Store original page in session for post-auth redirect
$return_url = '../index_new.php';

// Use HTTP_REFERER if available 
if (isset($_SERVER['HTTP_REFERER'])) {
    $return_url = $_SERVER['HTTP_REFERER'];
}

// Store in session for callback
$_SESSION['discord_auth_referrer'] = $return_url;

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
$auth_url .= '&custom_source=new_ui';

// Direct redirect to Discord
header('Location: ' . $auth_url);
exit;
?>