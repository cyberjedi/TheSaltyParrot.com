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

// Define API URL constant
if (!defined('DISCORD_API_URL')) {
    define('DISCORD_API_URL', 'https://discord.com/api/v10');
}

// Load configuration from secure variables file
$config_paths = [
    $_SERVER['DOCUMENT_ROOT'] . '/../../private/secure_variables.php',
    $_SERVER['DOCUMENT_ROOT'] . '/../private/secure_variables.php',
    $_SERVER['DOCUMENT_ROOT'] . '/private/secure_variables.php',
    dirname(__FILE__) . '/../../private/secure_variables.php'
];

$config = null;
foreach ($config_paths as $path) {
    if (file_exists($path)) {
        $config = require_once($path);
        break;
    }
}

// Define Discord constants
if ($config && isset($config['discord']) && is_array($config['discord'])) {
    if (!defined('DISCORD_CLIENT_ID')) {
        define('DISCORD_CLIENT_ID', $config['discord']['client_id'] ?? '');
    }
    if (!defined('DISCORD_CLIENT_SECRET')) {
        define('DISCORD_CLIENT_SECRET', $config['discord']['client_secret'] ?? '');
    }
    if (!defined('DISCORD_REDIRECT_URI')) {
        if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'dev.') === 0) {
            define('DISCORD_REDIRECT_URI', $config['discord']['dev_redirect_uri'] ?? '');
        } else {
            define('DISCORD_REDIRECT_URI', $config['discord']['redirect_uri'] ?? '');
        }
    }
} else {
    // Define backup constants in case we can't find the config
    if (!defined('DISCORD_CLIENT_ID')) define('DISCORD_CLIENT_ID', '');
    if (!defined('DISCORD_CLIENT_SECRET')) define('DISCORD_CLIENT_SECRET', '');
    if (!defined('DISCORD_REDIRECT_URI')) define('DISCORD_REDIRECT_URI', '');
    
    // Log the issue
    error_log('Discord configuration not found in secure variables file');
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
function discord_api_request($endpoint, $method = 'GET', $data = [], $token = null) {
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