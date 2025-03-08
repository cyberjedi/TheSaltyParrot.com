<?php
// File: discord/discord-login.php
// This file initiates the Discord OAuth process

require_once 'discord-config.php';

// Generate a random state parameter to prevent CSRF attacks
$state = bin2hex(random_bytes(16));
$_SESSION['discord_oauth_state'] = $state;

// Use the pre-generated OAuth URL from Discord Developer Portal with all required scopes
// Add the state parameter to the URL
$auth_url = "https://discord.com/oauth2/authorize?client_id=1347420579858350110&response_type=code&redirect_uri=https%3A%2F%2Fthesaltyparrot.com%2Fdiscord%2Fdiscord-callback.php&integration_type=0&scope=identify+guilds+guilds.channels.read+webhook.incoming&state=" . $state;

// Redirect the user to Discord's authorization page
header('Location: ' . $auth_url);
exit;
?>
