<?php
// File: discord/discord-login.php
require_once 'discord-config.php';

// Get return URL from cookie if available, otherwise use HTTP_REFERER or default to index
$return_url = '../index.php';

// Try to get from cookie first
if (isset($_COOKIE['discord_return_url']) && !empty($_COOKIE['discord_return_url'])) {
    $return_url = $_COOKIE['discord_return_url'];
    // Clear the cookie
    setcookie('discord_return_url', '', time() - 3600, '/');
} 
// Fall back to HTTP_REFERER if available
else if (isset($_SERVER['HTTP_REFERER'])) {
    $return_url = $_SERVER['HTTP_REFERER'];
}

// Store in session for callback
$_SESSION['discord_auth_referrer'] = $return_url;

// Generate a random state parameter to prevent CSRF attacks
$state = bin2hex(random_bytes(16));
$_SESSION['discord_oauth_state'] = $state;

// Build the authorization URL with all necessary scopes
$auth_url = DISCORD_API_URL . '/oauth2/authorize';
$auth_url .= '?client_id=' . DISCORD_CLIENT_ID;
$auth_url .= '&redirect_uri=' . urlencode(DISCORD_REDIRECT_URI);
$auth_url .= '&response_type=code';
$auth_url .= '&state=' . $state;
// Use only the basic scopes that are definitely supported
$auth_url .= '&scope=identify%20guilds';

// Add a custom parameter to track that we're coming from our login page
$auth_url .= '&custom_source=direct_login';

// Log the authentication attempt with the return URL for debugging
error_log('Discord auth attempt. Return URL: ' . $return_url);

// Direct redirection without showing an intermediate page
header('Location: ' . $auth_url);
exit;
?>
