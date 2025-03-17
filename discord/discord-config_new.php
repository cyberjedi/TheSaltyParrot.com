<?php
/**
 * Discord Configuration for New UI
 * 
 * This file maintains compatibility with the existing Discord configuration
 * while using clean new code for the new UI
 */

// Start the session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Use the existing configuration by including it
// This avoids duplicating sensitive credentials
if (file_exists(__DIR__ . '/discord-config.php')) {
    require_once __DIR__ . '/discord-config.php';
} else {
    // Define backup constants in case we can't find the original config
    // These won't work but prevent errors
    if (!defined('DISCORD_CLIENT_ID')) define('DISCORD_CLIENT_ID', '');
    if (!defined('DISCORD_CLIENT_SECRET')) define('DISCORD_CLIENT_SECRET', '');
    if (!defined('DISCORD_REDIRECT_URI')) define('DISCORD_REDIRECT_URI', '');
    if (!defined('DISCORD_API_URL')) define('DISCORD_API_URL', 'https://discord.com/api/v10');
    
    // Log the issue
    error_log('New UI could not find Discord configuration file');
}

/**
 * Make a Discord API request specifically for the new UI
 * 
 * @param string $endpoint API endpoint
 * @param string $method HTTP method
 * @param array $data Request data
 * @param string|null $token Access token
 * @return array Response data
 */
function discord_api_request_new($endpoint, $method = 'GET', $data = [], $token = null) {
    // If original function exists, use it
    if (function_exists('discord_api_request')) {
        return discord_api_request($endpoint, $method, $data, $token);
    }
    
    // Otherwise implement our own version
    $ch = curl_init();
    
    $headers = ['Accept: application/json', 'Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    $url = DISCORD_API_URL . $endpoint;
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } else if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Log response information
    error_log("New UI Discord API Response Code: $http_code");
    if ($curl_error) {
        error_log("New UI Discord API curl error: $curl_error");
    }
    
    $result = json_decode($response, true);
    
    if ($http_code < 200 || $http_code >= 300) {
        error_log("New UI Discord API Error ($http_code): " . ($result['message'] ?? $response));
    }
    
    return $result;
}
?>