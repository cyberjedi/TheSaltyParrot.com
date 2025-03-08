<?php
// File: discord/reauth.php
// This file forces Discord re-authentication with proper scopes

require_once 'discord-config.php';

// Clear Discord session data
unset($_SESSION['discord_user']);
unset($_SESSION['discord_access_token']);
unset($_SESSION['discord_refresh_token']);
unset($_SESSION['discord_token_expires']);

// Set a message to inform the user
$_SESSION['discord_message'] = 'You have been logged out of Discord to refresh your permissions. Please log in again.';

// Use the pre-generated OAuth URL from Discord Developer Portal with all required scopes
$auth_url = "https://discord.com/oauth2/authorize?client_id=1347420579858350110&response_type=code&redirect_uri=https%3A%2F%2Fthesaltyparrot.com%2Fdiscord%2Fdiscord-callback.php&integration_type=0&scope=identify+guilds+guilds.channels.read+webhook.incoming";

// Redirect the user to Discord's authorization page
header('Location: ' . $auth_url);
exit;
?>
