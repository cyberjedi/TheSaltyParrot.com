<?php
// File: discord/get_channels.php
// This file fetches channels for a specific Discord guild/server

// Enable error reporting for troubleshooting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once 'discord-config.php';

// Check if user is logged in
if (!is_discord_authenticated()) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

// Check if guild ID is provided
if (!isset($_GET['guild_id']) || empty($_GET['guild_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Guild ID is required']);
    exit;
}

// Refresh token if needed
if (!refresh_discord_token_if_needed()) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Token refresh failed']);
    exit;
}

$guild_id = $_GET['guild_id'];
$access_token = $_SESSION['discord_access_token'];

// Fetch channels from Discord API
$channels = discord_api_request('/guilds/' . $guild_id . '/channels', 'GET', [], $access_token);

if (!is_array($channels)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch channels']);
    exit;
}

// Filter to include only text channels (type 0)
$text_channels = array_filter($channels, function($channel) {
    return isset($channel['type']) && $channel['type'] === 0; // 0 is text channel
});

header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'channels' => array_values($text_channels) // reset array keys
]);
exit;
?>
