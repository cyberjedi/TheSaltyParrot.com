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

// Force a reauth by clearing the token and redirecting
if (isset($_GET['force_reauth']) && $_GET['force_reauth'] == 1) {
    // Save the guild_id to come back to
    $_SESSION['last_guild_id'] = $_GET['guild_id'];
    
    // Clear Discord session data
    unset($_SESSION['discord_access_token']);
    unset($_SESSION['discord_token_expires']);
    
    // Redirect to login
    header('Content-Type: application/json');
    echo json_encode(['status' => 'reauth', 'redirect' => 'discord-login.php']);
    exit;
}

// Try a different approach - instead of using the API to get channels,
// hardcode a request to Discord using curl directly
$guild_id = $_GET['guild_id'];
$access_token = $_SESSION['discord_access_token'];

$debug_output[] = "Guild ID: " . $guild_id;
$debug_output[] = "Token (first 10 chars): " . substr($access_token, 0, 10) . "...";
$debug_output[] = "Token expiration: " . date('Y-m-d H:i:s', $_SESSION['discord_token_expires']);
$debug_output[] = "Current time: " . date('Y-m-d H:i:s', time());

// Make the request directly with curl
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://discord.com/api/v10/guilds/' . $guild_id . '/channels');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $access_token,
    'Accept: application/json',
    'Content-Type: application/json',
    'User-Agent: The Salty Parrot (Discord OAuth2)'
]);

$response = curl_exec($ch);
$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

$debug_output[] = "Direct CURL request sent";
$debug_output[] = "Status code: " . $status_code;

if (!empty($curl_error)) {
    $debug_output[] = "CURL error: " . $curl_error;
}

// If the request was successful, process the channels
if ($status_code >= 200 && $status_code < 300) {
    $channels = json_decode($response, true);
    
    if (is_array($channels)) {
        $debug_output[] = "Successfully fetched " . count($channels) . " channels";
        
        // Filter to include text channels (type 0) and announcement channels (type 5)
        $text_channels = [];
        foreach ($channels as $channel) {
            if (isset($channel['type']) && ($channel['type'] === 0 || $channel['type'] === 5)) {
                $text_channels[] = $channel;
            }
        }
        
        $debug_output[] = "Filtered to " . count($text_channels) . " text channels";
        
        // Return success response
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'channels' => $text_channels,
            'debug' => $debug_output
        ]);
        exit;
    } else {
        $debug_output[] = "Invalid response format: not an array";
    }
} else {
    // If we got a 401 Unauthorized error, suggest force reauth
    if ($status_code === 401) {
        $debug_output[] = "Got 401 Unauthorized - suggesting reauth";
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Discord API error: 401: Unauthorized - Try reconnecting Discord',
            'needs_reauth' => true,
            'debug' => $debug_output
        ]);
        exit;
    }
    
    $debug_output[] = "Error fetching channels: HTTP code " . $status_code;
    $debug_output[] = "Response: " . substr($response, 0, 500); // Show first 500 chars of response
}

// Return error response
header('Content-Type: application/json');
echo json_encode([
    'status' => 'error',
    'message' => 'Discord API error: ' . $status_code,
    'debug' => $debug_output
]);
exit;
?>
