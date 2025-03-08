<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

require_once 'discord/discord-config.php';

// Set your token - either from your session or directly from what we just saw
$token = "oKX9JA71AlmrQaduQW0a5WBGsl2nPH"; // Use the token from the OAuth response

// Try to make a simple API request
echo "<h1>Discord API Test</h1>";

// Test 1: Manual request
echo "<h2>Test 1: Manual API Request</h2>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://discord.com/api/v10/users/@me");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $token,
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: " . $http_code . "<br>";
echo "Response: <pre>" . htmlspecialchars($response) . "</pre>";

// Test 2: Using your discord_api_request function
if (function_exists('discord_api_request')) {
    echo "<h2>Test 2: Using discord_api_request Function</h2>";
    $result = discord_api_request('/users/@me', 'GET', [], $token);
    echo "Response: <pre>" . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)) . "</pre>";
}
?>
