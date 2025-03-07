<?php
// File: discord/discord-config.php
// This file contains Discord API configuration
// Store this file outside the web root or secure it with .htaccess

// Discord API credentials
define('DISCORD_CLIENT_ID', 'YOUR_CLIENT_ID'); // Replace with your actual client ID
define('DISCORD_CLIENT_SECRET', 'YOUR_CLIENT_SECRET'); // Replace with your actual client secret
define('DISCORD_REDIRECT_URI', 'https://thesaltyparrot.com/auth/discord-callback.php'); // Change for local testing if needed
define('DISCORD_API_URL', 'https://discord.com/api/v10');

// Sessions configuration
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}

// Helper function for making Discord API requests
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
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($http_code < 200 || $http_code >= 300) {
        error_log("Discord API Error: " . ($result['message'] ?? $response));
    }
    
    return $result;
}

// Function to check if a user is logged in with Discord
function is_discord_authenticated() {
    return isset($_SESSION['discord_user']) && isset($_SESSION['discord_access_token']) && isset($_SESSION['discord_token_expires']);
}

// Function to check if token needs refreshing
function refresh_discord_token_if_needed() {
    // If not authenticated, no token to refresh
    if (!is_discord_authenticated()) {
        return false;
    }
    
    // If token is still valid (with 5 minute buffer), do nothing
    if ($_SESSION['discord_token_expires'] > (time() + 300)) {
        return true;
    }
    
    // If we have a refresh token, try to refresh
    if (isset($_SESSION['discord_refresh_token'])) {
        $data = [
            'client_id' => DISCORD_CLIENT_ID,
            'client_secret' => DISCORD_CLIENT_SECRET,
            'grant_type' => 'refresh_token',
            'refresh_token' => $_SESSION['discord_refresh_token']
        ];
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, DISCORD_API_URL . '/oauth2/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code >= 200 && $http_code < 300) {
            $result = json_decode($response, true);
            
            $_SESSION['discord_access_token'] = $result['access_token'];
            $_SESSION['discord_refresh_token'] = $result['refresh_token'];
            $_SESSION['discord_token_expires'] = time() + $result['expires_in'];
            
            return true;
        } else {
            // If refresh fails, clear session and return false
            session_unset();
            return false;
        }
    }
    
    // No refresh token or refresh failed
    return false;
}
?>

<?php
// File: auth/discord-login.php
// This file handles initiating the Discord OAuth flow

require_once 'discord-config.php';

// Generate a state parameter to prevent CSRF attacks
$state = bin2hex(random_bytes(16));
$_SESSION['discord_oauth_state'] = $state;

// Construct the authorization URL
$auth_url = DISCORD_API_URL . '/oauth2/authorize' .
    '?client_id=' . DISCORD_CLIENT_ID .
    '&redirect_uri=' . urlencode(DISCORD_REDIRECT_URI) .
    '&response_type=code' .
    '&scope=' . urlencode('identify guilds webhook.incoming') .
    '&state=' . $state;

// Redirect the user to Discord's authorization page
header('Location: ' . $auth_url);
exit;
?>

<?php
// File: discord/discord-callback.php
// This file handles the callback from Discord OAuth

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

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code < 200 || $http_code >= 300) {
    $_SESSION['discord_error'] = 'Failed to exchange code for token.';
    header('Location: ../index.php');
    exit;
}

$token_response = json_decode($response, true);

// Store the access token in session
$_SESSION['discord_access_token'] = $token_response['access_token'];
$_SESSION['discord_refresh_token'] = $token_response['refresh_token'];
$_SESSION['discord_token_expires'] = time() + $token_response['expires_in'];

// Fetch user information
$user_response = discord_api_request('/users/@me', 'GET', [], $token_response['access_token']);

if (!isset($user_response['id'])) {
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
    $checkStmt->bindParam(':discord_id', $user_response['id']);
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
            
        $updateStmt->bindParam(':username', $user_response['username']);
        $updateStmt->bindParam(':discriminator', $user_response['discriminator'] ?? '');
        $updateStmt->bindParam(':avatar', $user_response['avatar']);
        $updateStmt->bindParam(':access_token', $token_response['access_token']);
        $updateStmt->bindParam(':refresh_token', $token_response['refresh_token']);
        $updateStmt->bindParam(':token_expires', $_SESSION['discord_token_expires']);
        $updateStmt->bindParam(':discord_id', $user_response['id']);
        
        $updateStmt->execute();
    } else {
        // Create new user
        $insertStmt = $conn->prepare("INSERT INTO discord_users 
            (discord_id, username, discriminator, avatar, access_token, refresh_token, token_expires, created_at, last_login) 
            VALUES 
            (:discord_id, :username, :discriminator, :avatar, :access_token, :refresh_token, :token_expires, NOW(), NOW())");
            
        $insertStmt->bindParam(':discord_id', $user_response['id']);
        $insertStmt->bindParam(':username', $user_response['username']);
        $insertStmt->bindParam(':discriminator', $user_response['discriminator'] ?? '');
        $insertStmt->bindParam(':avatar', $user_response['avatar']);
        $insertStmt->bindParam(':access_token', $token_response['access_token']);
        $insertStmt->bindParam(':refresh_token', $token_response['refresh_token']);
        $insertStmt->bindParam(':token_expires', $_SESSION['discord_token_expires']);
        
        $insertStmt->execute();
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

<?php
// File: auth/discord-logout.php
// This file handles Discord logout

require_once 'discord-config.php';

// Clear Discord-specific session variables
unset($_SESSION['discord_user']);
unset($_SESSION['discord_access_token']);
unset($_SESSION['discord_refresh_token']);
unset($_SESSION['discord_token_expires']);

// Optional: invalidate the token on Discord's side
// This isn't strictly necessary but is good practice
if (isset($_SESSION['discord_access_token'])) {
    $data = [
        'client_id' => DISCORD_CLIENT_ID,
        'client_secret' => DISCORD_CLIENT_SECRET,
        'token' => $_SESSION['discord_access_token']
    ];
    
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, DISCORD_API_URL . '/oauth2/token/revoke');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    curl_exec($ch);
    curl_close($ch);
}

$_SESSION['discord_message'] = 'You have been logged out from Discord.';

// Redirect to the main page
header('Location: ../index.php');
exit;
?>
