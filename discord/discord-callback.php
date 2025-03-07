<?php
// At the very top
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start tracking the process
echo "Step 1: Starting Discord callback process<br>";

// Let's check where files are being included from
echo "Current file: " . __FILE__ . "<br>";
echo "Current directory: " . __DIR__ . "<br>";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";

// Try to include discord-config.php
echo "Step 2: Trying to include discord-config.php<br>";
try {
    require_once 'discord-config.php';
    echo "discord-config.php included successfully<br>";
} catch (Exception $e) {
    echo "Error including discord-config.php: " . $e->getMessage() . "<br>";
    exit;
}

// Try to include db_connect.php
echo "Step 3: Trying to include db_connect.php<br>";
try {
    // Use the original include path but check it explicitly
    echo "About to include: " . __DIR__ . '/../config/db_connect.php' . "<br>";
    if (!file_exists(__DIR__ . '/../config/db_connect.php')) {
        echo "File doesn't exist at that path!<br>";
    }
    
    require_once '../config/db_connect.php';
    echo "db_connect.php included successfully<br>";
} catch (Exception $e) {
    echo "Error including db_connect.php: " . $e->getMessage() . "<br>";
    exit;
}

// Check for connection
echo "Step 4: Checking if connection is established<br>";
if (isset($conn) && $conn instanceof PDO) {
    echo "Database connection is good<br>";
} else {
    echo "Database connection not established<br>";
    exit;
}

// Now continue with the rest of your original code:

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

// Rest of your original discord-callback.php code goes here...
// Make sure you keep all the original functionality!
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
