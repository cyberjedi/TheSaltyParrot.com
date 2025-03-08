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

// Get guild ID and token
$guild_id = $_GET['guild_id'];
$access_token = $_SESSION['discord_access_token'];

$debug_output[] = "Guild ID: " . $guild_id;
$debug_output[] = "Token Type: " . gettype($access_token);
$debug_output[] = "Token Length: " . strlen($access_token);
$debug_output[] = "Token Expiration: " . date('Y-m-d H:i:s', $_SESSION['discord_token_expires']);
$debug_output[] = "Current Time: " . date('Y-m-d H:i:s', time());

// Debug request details
$url = 'https://discord.com/api/v10/guilds/' . $guild_id . '/channels';
$debug_output[] = "Request URL: " . $url;
$debug_output[] = "Authorization: Bearer " . substr($access_token, 0, 10) . '...';

// Make request directly with curl
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $access_token,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

$debug_output[] = "HTTP Code: " . $http_code;
if (!empty($curl_error)) {
    $debug_output[] = "CURL Error: " . $curl_error;
}

// Check if response is valid JSON
$response_data = json_decode($response, true);
$json_error = json_last_error();
if ($json_error !== JSON_ERROR_NONE) {
    $debug_output[] = "JSON Error: " . json_last_error_msg();
    $debug_output[] = "Raw Response: " . substr($response, 0, 200) . "...";
}

// Process response
if ($http_code >= 200 && $http_code < 300 && is_array($response_data)) {
    // Success!
    $text_channels = [];
    foreach ($response_data as $channel) {
        if (isset($channel['type']) && ($channel['type'] === 0 || $channel['type'] === 5)) {
            $text_channels[] = $channel;
        }
    }
    
    $debug_output[] = "Successfully found " . count($text_channels) . " text channels";
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'channels' => $text_channels,
        'debug' => $debug_output
    ]);
} else {
    // Error response
    $error_message = is_array($response_data) && isset($response_data['message']) 
        ? $response_data['message'] 
        : 'Unknown error';
    
    $debug_output[] = "Error Message: " . $error_message;
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Discord API error: ' . $error_message,
        'debug' => $debug_output
    ]);
}
?>
