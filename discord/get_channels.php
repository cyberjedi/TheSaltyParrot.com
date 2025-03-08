<?php
// File: discord/get_channels.php
// Enhanced debugging version

// Enable error reporting for troubleshooting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once 'discord-config.php';

// Start debugging output - this will be visible in the response
$debug_output = [];
$debug_output[] = "Debug mode enabled for get_channels.php";

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

$debug_output[] = "Authentication check passed";

// Force token refresh instead of just checking
if (!force_discord_token_refresh()) {
    // If forced refresh fails, try the normal refresh
    if (!refresh_discord_token_if_needed()) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Token refresh failed']);
        exit;
    }
}

$guild_id = $_GET['guild_id'];
$access_token = $_SESSION['discord_access_token'];

$debug_output[] = "Guild ID: " . $guild_id;
$debug_output[] = "Token (first 10 chars): " . substr($access_token, 0, 10) . "...";
$debug_output[] = "Token expiration: " . date('Y-m-d H:i:s', $_SESSION['discord_token_expires']);
$debug_output[] = "Current time: " . date('Y-m-d H:i:s', time());

// Test the Discord API with a basic request
$user_info = discord_api_request('/users/@me', 'GET', [], $access_token);
if (!empty($user_info) && isset($user_info['id'])) {
    $debug_output[] = "API test successful - authenticated as user: " . $user_info['username'];
} else {
    $debug_output[] = "API test failed - could not fetch user info";
}

// Fetch channels from Discord API
$debug_output[] = "Attempting to fetch channels...";
$channels = discord_api_request('/guilds/' . $guild_id . '/channels', 'GET', [], $access_token);

// Check for direct error in response
if (isset($channels['message']) && isset($channels['code'])) {
    $debug_output[] = "Discord API error: Code " . $channels['code'] . " - " . $channels['message'];
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error', 
        'message' => 'Discord API error: ' . $channels['message'],
        'debug' => $debug_output
    ]);
    exit;
}

// Check if we got a valid array response
if (!is_array($channels)) {
    $debug_output[] = "Invalid response format from Discord API";
    $debug_output[] = "Response type: " . gettype($channels);
    $debug_output[] = "Response (truncated): " . substr(json_encode($channels), 0, 100) . "...";
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error', 
        'message' => 'Failed to fetch channels - invalid response format',
        'debug' => $debug_output
    ]);
    exit;
}

$debug_output[] = "Received " . count($channels) . " channels from API";

// Examine each channel
$channel_details = [];
foreach ($channels as $channel) {
    $channel_info = [
        'id' => $channel['id'] ?? 'unknown',
        'name' => $channel['name'] ?? 'unnamed',
        'type' => $channel['type'] ?? 'unknown',
    ];
    $channel_details[] = $channel_info;
    $debug_output[] = "Channel: " . json_encode($channel_info);
}

// Filter to include text channels (type 0) and announcement channels (type 5)
$text_channels = [];
foreach ($channels as $channel) {
    if (isset($channel['type'])) {
        $debug_output[] = "Checking channel: " . ($channel['name'] ?? 'unnamed') . " - Type: " . $channel['type'];
        
        if ($channel['type'] === 0 || $channel['type'] === 5) {
            $debug_output[] = "Found valid text channel: " . ($channel['name'] ?? 'unnamed');
            $text_channels[] = $channel;
        }
    } else {
        $debug_output[] = "Channel is missing 'type' property: " . json_encode($channel);
    }
}

$debug_output[] = "Filtered " . count($text_channels) . " text channels";

// Return results with debug info
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'channels' => array_values($text_channels),
    'allChannels' => $channel_details,
    'debug' => $debug_output
]);
exit;
?>
