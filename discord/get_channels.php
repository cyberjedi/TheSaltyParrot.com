<?php
// File: discord/get_channels.php

// Enable error reporting for troubleshooting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once 'discord-config.php';

// Check if user is logged in
if (!is_discord_authenticated()) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error', 
        'message' => 'Not authenticated with Discord',
        'needs_reauth' => true
    ]);
    exit;
}

// Force token refresh to ensure fresh permissions
if (!force_discord_token_refresh()) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error', 
        'message' => 'Failed to refresh token',
        'needs_reauth' => true
    ]);
    exit;
}

// Check if guild ID is provided
if (!isset($_GET['guild_id']) || empty($_GET['guild_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Guild ID is required']);
    exit;
}

// Get guild ID and token
$guild_id = $_GET['guild_id'];
$access_token = $_SESSION['discord_access_token'];

// Set up debug information
$debug_info = [
    'token_length' => strlen($access_token),
    'token_preview' => substr($access_token, 0, 10) . '...',
    'token_expires' => date('Y-m-d H:i:s', $_SESSION['discord_token_expires']),
    'current_time' => date('Y-m-d H:i:s', time())
];

// Make request to get channels
$url = DISCORD_API_URL . '/guilds/' . $guild_id . '/channels';

// Set the correct Authorization header
// For user OAuth tokens, use Bearer
$headers = [
    'Authorization: Bearer ' . $access_token,
    'Content-Type: application/json'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_HEADER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// Add HTTP response info to debug
$debug_info['http_code'] = $http_code;
if ($curl_error) {
    $debug_info['curl_error'] = $curl_error;
}

// Process response
if ($http_code >= 200 && $http_code < 300) {
    $response_data = json_decode($response, true);
    
    if (is_array($response_data)) {
        // Filter text channels (type 0 = text, type 5 = announcement channel)
        $text_channels = array_filter($response_data, function($channel) {
            return isset($channel['type']) && ($channel['type'] === 0 || $channel['type'] === 5);
        });
        
        // Convert to indexed array
        $text_channels = array_values($text_channels);
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'channels' => $text_channels,
            'debug' => $debug_info
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid response format from Discord API',
            'debug' => $debug_info
        ]);
    }
} else {
    // Error response
    $error_message = 'Unknown error';
    $needs_reauth = false;
    
    // Try to extract error message from response
    $response_data = json_decode($response, true);
    if (is_array($response_data) && isset($response_data['message'])) {
        $error_message = $response_data['message'];
        
        // Check if we need to re-authenticate (common error codes)
        if ($http_code === 401 || strpos($error_message, 'unauthorized') !== false || 
            strpos($error_message, 'invalid token') !== false) {
            $needs_reauth = true;
        }
    }
    
    // Log the error for debugging
    error_log("Discord get_channels error ($http_code): $error_message");
    error_log("Response: $response");
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Discord API error: ' . $error_message,
        'http_code' => $http_code,
        'needs_reauth' => $needs_reauth,
        'debug' => $debug_info
    ]);
}
?>
