<?php
// File: discord/reauth.php
// This file forces Discord re-authentication to get fresh tokens with proper scopes

require_once 'discord-config.php';

// Clear Discord session data
unset($_SESSION['discord_user']);
unset($_SESSION['discord_access_token']);
unset($_SESSION['discord_refresh_token']);
unset($_SESSION['discord_token_expires']);

// Set a message to inform the user
$_SESSION['discord_message'] = 'You have been logged out of Discord to refresh your permissions. Please log in again.';

// Generate a random state parameter to prevent CSRF attacks
$state = bin2hex(random_bytes(16));
$_SESSION['discord_oauth_state'] = $state;

// Build the authorization URL with expanded scopes
$auth_url = DISCORD_API_URL . '/oauth2/authorize';
$auth_url .= '?client_id=' . DISCORD_CLIENT_ID;
$auth_url .= '&redirect_uri=' . urlencode(DISCORD_REDIRECT_URI);
$auth_url .= '&response_type=code';
$auth_url .= '&state=' . $state;

// Ensure we have all the scopes we might need
$auth_url .= '&scope=identify%20guilds%20bot%20webhook.incoming';

// Redirect the user to Discord's authorization page
header('Location: ' . $auth_url);
exit;
?>
