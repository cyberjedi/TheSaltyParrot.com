<?php
// File: discord/discord-login.php
// This file initiates the Discord OAuth process

require_once 'discord-config.php';

// Generate a random state parameter to prevent CSRF attacks
$state = bin2hex(random_bytes(16));
$_SESSION['discord_oauth_state'] = $state;

// Build the authorization URL with just the basic scopes needed for login
// We only need identify and guilds for authentication
$auth_url = DISCORD_API_URL . '/oauth2/authorize';
$auth_url .= '?client_id=' . DISCORD_CLIENT_ID;
$auth_url .= '&redirect_uri=' . urlencode(DISCORD_REDIRECT_URI);
$auth_url .= '&response_type=code';
$auth_url .= '&state=' . $state;
$auth_url .= '&scope=identify%20guilds';

// Redirect the user to Discord's authorization page
header('Location: ' . $auth_url);
exit;
?>
