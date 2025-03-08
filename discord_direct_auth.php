<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

require_once 'discord/discord-config.php';
require_once 'config/db_connect.php';

// Generate a random state parameter
$state = bin2hex(random_bytes(16));
$_SESSION['discord_oauth_state'] = $state;

// If we have a code, process it
if(isset($_GET['code'])) {
    // Exchange the code for an access token
    $token_data = [
        'client_id' => DISCORD_CLIENT_ID,
        'client_secret' => DISCORD_CLIENT_SECRET,
        'grant_type' => 'authorization_code',
        'code' => $_GET['code'],
        'redirect_uri' => "https://thesaltyparrot.com/discord_direct_auth.php"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, DISCORD_API_URL . '/oauth2/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "<h1>OAuth Response</h1>";
    echo "HTTP Status: " . $http_code . "<br>";
    
    // Display the raw response for debugging
    echo "<h2>Raw Response:</h2>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    // Parse the response
    $token_response = json_decode($response, true);
    
    if(isset($token_response['access_token'])) {
        $token = $token_response['access_token'];
        
        echo "<h2>Token Information:</h2>";
        echo "Token length: " . strlen($token) . "<br>";
        echo "Token first 10 chars: " . substr($token, 0, 10) . "...<br>";
        
        // Store in session and check
        $_SESSION['discord_direct_token'] = $token;
        echo "After storing in session, length: " . strlen($_SESSION['discord_direct_token']) . "<br>";
        
        // Store in database
        try {
            $stmt = $conn->prepare("UPDATE discord_users SET 
                access_token = :access_token 
                WHERE discord_id = '297737666215149569'");
            $stmt->bindParam(':access_token', $token);
            $stmt->execute();
            
            // Verify it was stored correctly
            $stmt = $conn->prepare("SELECT access_token FROM discord_users WHERE discord_id = '297737666215149569'");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "After storing in database, length: " . strlen($result['access_token']) . "<br>";
        } catch(PDOException $e) {
            echo "Database error: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "<h2>Error:</h2>";
        echo "No access token found in response<br>";
    }
    
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Direct Discord Auth</title>
</head>
<body>
    <h1>Direct Discord Authentication Test</h1>
    <p>This is a simplified test that bypasses your regular authentication flow.</p>
    
    <p>
        <a href="<?php 
            $auth_url = DISCORD_API_URL . '/oauth2/authorize';
            $auth_url .= '?client_id=' . DISCORD_CLIENT_ID;
            $auth_url .= '&redirect_uri=' . urlencode("https://thesaltyparrot.com/discord_direct_auth.php");
            $auth_url .= '&response_type=code';
            $auth_url .= '&state=' . $state;
            $auth_url .= '&scope=identify%20guilds';
            echo $auth_url;
        ?>">Login with Discord (Direct Test)</a>
    </p>
    
    <p><a href="index.php">Back to Main Site</a></p>
</body>
</html>
