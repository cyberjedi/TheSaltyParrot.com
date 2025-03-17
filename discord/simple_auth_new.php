<?php
/**
 * Simple Discord Auth for New UI
 * 
 * A minimal direct Discord authentication link with new UI redirect.
 */

// Start the session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include Discord configuration
require_once 'discord-config.php';

// Store the return URL directly as index_new.php
$_SESSION['discord_auth_referrer'] = '../index_new.php';

// Set a simple session flag to indicate this request came from the new UI
$_SESSION['from_new_ui'] = true;

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

// Direct redirect to Discord
header('Location: ' . $auth_url);
exit;
?>