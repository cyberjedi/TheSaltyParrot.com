<?php
// File: discord/get_channels.php
// Debug version

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'discord-config.php';

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

// Get token and guild ID
$guild_id = $_GET['guild_id'];
$access_token = $_SESSION['discord_access_token'];

$debug_output[] = "Guild ID: " . $guild_id;
$debug_output[] = "Token expiration: " . date('Y-m-d H:i:s', $_SESSION['discord_token_expires']);
$debug_output[] = "Current time: " . date('Y-m-d H:i:s', time());

// Print the actual token for debugging
// $debug_output[] = "Full token: " . $access_token;

// Make DIRECT call to Discord API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://discord.com/api/v10/guilds/' . $guild_id . '/channels');
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

$debug_output[] = "Raw Response: " . substr($response, 0, 150) . "...";

// Parse response
$response_data = json_decode($response, true);

if ($http_code >= 200 && $http_code < 300 && is_array($response_data)) {
    // Success - process and return channels
    $debug_output[] = "Successfully fetched " . count($response_data) . " channels";
    
    // Filter to text channels
    $text_channels = array_filter($response_data, function($channel) {
        return isset($channel['type']) && ($channel['type'] === 0 || $channel['type'] === 5);
    });
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'channels' => array_values($text_channels),
        'debug' => $debug_output
    ]);
} else {
    // Error - return debug info
    if (is_array($response_data) && isset($response_data['message'])) {
        $error_message = $response_data['message'];
    } else {
        $error_message = 'Unknown error';
    }
    
    $debug_output[] = "Discord API error: " . $error_message;
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Discord API error: ' . $error_message,
        'debug' => $debug_output
    ]);
}
?>
