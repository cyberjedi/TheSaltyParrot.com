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

// Debug log
error_log("Fetching channels for guild ID: $guild_id");

// Fetch channels from Discord API
$channels = discord_api_request('/guilds/' . $guild_id . '/channels', 'GET', [], $access_token);

// Debug log
error_log("API Response: " . json_encode($channels));

if (!is_array($channels)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch channels']);
    exit;
}

// Filter to include only text channels (type 0 or 5)
// Type 0 = Regular text channel
// Type 5 = Announcement channel (which can also receive webhooks)
$text_channels = [];

foreach ($channels as $channel) {
    // Debug each channel
    error_log("Channel: " . json_encode($channel));
    
    // Check if type exists and is a text channel type (0) or announcement channel (5)
    if (isset($channel['type']) && ($channel['type'] === 0 || $channel['type'] === 5)) {
        // Make sure we can create webhooks in this channel
        if (!isset($channel['permissions']) || 
            strpos($channel['permissions'], 'CREATE_WEBHOOKS') !== false || 
            strpos($channel['permissions'], 'ADMINISTRATOR') !== false) {
            $text_channels[] = $channel;
        }
    }
}

// If no channels found, check permissions
if (empty($text_channels)) {
    error_log("No text channels found with webhook permissions");
    
    // Get guild information to check permissions
    $guild = discord_api_request('/guilds/' . $guild_id, 'GET', [], $access_token);
    error_log("Guild info: " . json_encode($guild));
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'channels' => [],
        'message' => 'No text channels available where you have permission to create webhooks'
    ]);
    exit;
}

// Debug log
error_log("Found " . count($text_channels) . " valid text channels");

header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'channels' => array_values($text_channels) // reset array keys
]);
exit;
?>
