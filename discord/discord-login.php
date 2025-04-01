<?php
/**
 * Discord Login Handler
 * 
 * Initiates the Discord OAuth flow
 */

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include Discord configuration
require_once 'discord-config.php';

// Get state from request if provided
$state = $_GET['state'] ?? bin2hex(random_bytes(16));

// Store state in session
$_SESSION['discord_oauth_state'] = $state;

// Build the authorization URL
$auth_url = DISCORD_API_URL . '/oauth2/authorize';
$auth_url .= '?client_id=' . DISCORD_CLIENT_ID;
$auth_url .= '&redirect_uri=' . urlencode(DISCORD_REDIRECT_URI);
$auth_url .= '&response_type=code';
$auth_url .= '&state=' . $state;
$auth_url .= '&scope=identify%20guilds';
$auth_url .= '&prompt=consent';

// Log the authentication attempt
error_log('Discord auth initiated. State: ' . $state);

// Redirect to Discord
header('Location: ' . $auth_url);
exit;