<?php
// File: discord/discord-callback.php
// This file handles the callback from Discord OAuth with improved debugging

require_once 'discord-config.php';
require_once '../config/db_connect.php';

// Check for errors or authorization denial
if (isset($_GET['error'])) {
    $_SESSION['discord_error'] = 'Authorization denied: ' . $_GET['error_description'];
    header('Location: ../index.php');
    exit;
}

// Verify state parameter to prevent CSRF attacks
if (!isset($_GET['state']) || !isset($_SESSION['discord_oauth_state']) || $_GET['state'] !== $_SESSION['discord_oauth_state']) {
    $_SESSION['discord_error'] = 'Invalid state parameter. Please try again.';
    header('Location: ../index.php');
    exit;
}

// Clear the state from session
unset($_SESSION['discord_oauth_state']);

// Check for the authorization code
if (!isset($_GET['code'])) {
    $_SESSION['discord_error'] = 'No authorization code received.';
    header('Location: ../index.php');
    exit;
}

// Exchange the code for an access token
$token_data = [
    'client_id' => DISCORD_CLIENT_ID,
    'client_secret' => DISCORD_CLIENT_SECRET,
    'grant_type' => 'authorization_code',
    'code' => $_GET['code'],
    'redirect_uri' => DISCORD_REDIRECT_URI
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
$curl_error = curl_error($ch);
curl_close($ch);

// Enhanced logging for debugging
error_log('Discord token response code: ' . $http_code);
if ($curl_error) {
    error_log('Discord token curl error: ' . $curl_error);
}

// Log raw response (first 100 chars to avoid massive logs)
error_log('Discord token raw response preview: ' . substr($response, 0, 100) . '...');

if ($http_code < 200 || $http_code >= 300) {
    error_log('Discord token error response: ' . $response);
    $_SESSION['discord_error'] = 'Failed to exchange code for token (HTTP ' . $http_code . ').';
    header('Location: ../index.php');
    exit;
}

$token_response = json_decode($response, true);

// Extended logging for token debugging
error_log('Discord token after decode: ' . json_encode($token_response));

// Validate the token response
if (!isset($token_response['access_token']) || !isset($token_response['refresh_token']) || !isset($token_response['expires_in'])) {
    error_log('Invalid token response: ' . $response);
    $_SESSION['discord_error'] = 'Invalid token response from Discord.';
    header('Location: ../index.php');
    exit;
}

// Log token details for debugging
error_log('Discord access token length: ' . strlen($token_response['access_token']));
error_log('Discord access token first 10 chars: ' . substr($token_response['access_token'], 0, 10));
error_log('Discord token scopes: ' . ($token_response['scope'] ?? 'none'));
error_log('Discord token type: ' . ($token_response['token_type'] ?? 'none'));
error_log('Discord token expires_in: ' . $token_response['expires_in']);

// Store the access token in session - make sure we don't truncate it
$_SESSION['discord_access_token'] = $token_response['access_token'];
$_SESSION['discord_refresh_token'] = $token_response['refresh_token'];
$_SESSION['discord_token_expires'] = time() + $token_response['expires_in'];

// Double check token was stored correctly
error_log('Session token length after storing: ' . strlen($_SESSION['discord_access_token']));

// Fetch user information
$user_response = discord_api_request('/users/@me', 'GET', [], $_SESSION['discord_access_token']);

if (!isset($user_response['id'])) {
    error_log('Failed to fetch user information: ' . json_encode($user_response));
    $_SESSION['discord_error'] = 'Failed to fetch user information.';
    header('Location: ../index.php');
    exit;
}

// Store basic user data in session
$_SESSION['discord_user'] = [
    'id' => $user_response['id'],
    'username' => $user_response['username'],
    'discriminator' => $user_response['discriminator'] ?? '',
    'avatar' => $user_response['avatar']
];

// Store the user in the database
try {
    // Check if user exists first
    $checkStmt = $conn->prepare("SELECT * FROM discord_users WHERE discord_id = :discord_id");
    $discord_id = $user_response['id'];
    $checkStmt->bindParam(':discord_id', $discord_id);
    $checkStmt->execute();
    
    $user = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Update existing user
        $updateStmt = $conn->prepare("UPDATE discord_users SET 
            username = :username, 
            discriminator = :discriminator, 
            avatar = :avatar, 
            access_token = :access_token, 
            refresh_token = :refresh_token, 
            token_expires = :token_expires, 
            last_login = NOW() 
            WHERE discord_id = :discord_id");
            
        $username = $user_response['username'];
        $discriminator = $user_response['discriminator'] ?? '';
        $avatar = $user_response['avatar'];
        $access_token = $token_response['access_token'];
        $refresh_token = $token_response['refresh_token'];
        $token_expires = $_SESSION['discord_token_expires'];
        
        $updateStmt->bindParam(':username', $username);
        $updateStmt->bindParam(':discriminator', $discriminator);
        $updateStmt->bindParam(':avatar', $avatar);
        $updateStmt->bindParam(':access_token', $access_token);
        $updateStmt->bindParam(':refresh_token', $refresh_token);
        $updateStmt->bindParam(':token_expires', $token_expires);
        $updateStmt->bindParam(':discord_id', $discord_id);
        
        $updateStmt->execute();
        
        // Log database update
        error_log('Updated existing Discord user: ' . $discord_id);
    } else {
        // Create new user
        $insertStmt = $conn->prepare("INSERT INTO discord_users 
            (discord_id, username, discriminator, avatar, access_token, refresh_token, token_expires, created_at, last_login) 
            VALUES 
            (:discord_id, :username, :discriminator, :avatar, :access_token, :refresh_token, :token_expires, NOW(), NOW())");
            
        $username = $user_response['username'];
        $discriminator = $user_response['discriminator'] ?? '';
        $avatar = $user_response['avatar'];
        $access_token = $token_response['access_token'];
        $refresh_token = $token_response['refresh_token'];
        $token_expires = $_SESSION['discord_token_expires'];
        
        $insertStmt->bindParam(':discord_id', $discord_id);
        $insertStmt->bindParam(':username', $username);
        $insertStmt->bindParam(':discriminator', $discriminator);
        $insertStmt->bindParam(':avatar', $avatar);
        $insertStmt->bindParam(':access_token', $access_token);
        $insertStmt->bindParam(':refresh_token', $refresh_token);
        $insertStmt->bindParam(':token_expires', $token_expires);
        
        $insertStmt->execute();
        
        // Log database insert
        error_log('Created new Discord user: ' . $discord_id);
    }
    
    $_SESSION['discord_success'] = 'Successfully logged in with Discord!';
} catch (PDOException $e) {
    // Still allow login even if DB storage fails, but log the error
    error_log('Discord login database error: ' . $e->getMessage());
    $_SESSION['discord_warning'] = 'Your login worked, but we had trouble saving your session. Some features may be unavailable.';
}

// Redirect to dashboard or main page
header('Location: ../index.php');
exit;
?>
