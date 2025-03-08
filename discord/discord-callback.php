<?php
// File: discord/discord-callback.php
// This file handles the callback from Discord OAuth with improved debugging

require_once 'discord-config.php';
require_once '../config/db_connect.php';

// Get redirect URL (either referring page or default to index)
$redirect_url = isset($_SESSION['discord_auth_referrer']) ? $_SESSION['discord_auth_referrer'] : '../index.php';

// Check for errors or authorization denial
if (isset($_GET['error'])) {
    $_SESSION['discord_error'] = 'Authorization denied: ' . $_GET['error_description'];
    header('Location: ' . $redirect_url);
    exit;
}

// Verify state parameter to prevent CSRF attacks
if (!isset($_GET['state']) || !isset($_SESSION['discord_oauth_state']) || $_GET['state'] !== $_SESSION['discord_oauth_state']) {
    $_SESSION['discord_error'] = 'Invalid state parameter. Please try again.';
    header('Location: ' . $redirect_url);
    exit;
}

// Clear the state from session
unset($_SESSION['discord_oauth_state']);

// Check for the authorization code
if (!isset($_GET['code'])) {
    $_SESSION['discord_error'] = 'No authorization code received.';
    header('Location: ' . $redirect_url);
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

// Parse response to get token information
$token_response = json_decode($response, true);

// Check for errors in the response
if ($http_code < 200 || $http_code >= 300) {
    error_log('Discord token error response: ' . $response);
    $_SESSION['discord_error'] = 'Failed to exchange code for token (HTTP ' . $http_code . ').';
    header('Location: ' . $redirect_url);
    exit;
}

// Check for missing or malformed tokens
if (!isset($token_response['access_token']) || !isset($token_response['refresh_token']) || !isset($token_response['expires_in'])) {
    $error_message = 'Invalid token response from Discord: ' . substr($response, 0, 100) . '...';
    error_log($error_message);
    $_SESSION['discord_error'] = $error_message;
    header('Location: ' . $redirect_url);
    exit;
}

// Store original tokens for direct debugging and database storage
$original_access_token = $token_response['access_token'];
$original_refresh_token = $token_response['refresh_token'];

// Store tokens directly without any modification
$_SESSION['discord_access_token'] = $original_access_token;
$_SESSION['discord_refresh_token'] = $original_refresh_token;
$_SESSION['discord_token_expires'] = time() + $token_response['expires_in'];

// Fetch user information
$user_response = discord_api_request('/users/@me', 'GET', [], $_SESSION['discord_access_token']);

if (!isset($user_response['id'])) {
    error_log('Failed to fetch user information: ' . json_encode($user_response));
    $_SESSION['discord_error'] = 'Failed to fetch user information.';
    header('Location: ' . $redirect_url);
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
        $access_token = $original_access_token;
        $refresh_token = $original_refresh_token;
        $token_expires = $_SESSION['discord_token_expires'];
        
        $updateStmt->bindParam(':username', $username);
        $updateStmt->bindParam(':discriminator', $discriminator);
        $updateStmt->bindParam(':avatar', $avatar);
        $updateStmt->bindParam(':access_token', $access_token);
        $updateStmt->bindParam(':refresh_token', $refresh_token);
        $updateStmt->bindParam(':token_expires', $token_expires);
        $updateStmt->bindParam(':discord_id', $discord_id);
        
        $updateStmt->execute();
    } else {
        // Create new user
        $insertStmt = $conn->prepare("INSERT INTO discord_users 
            (discord_id, username, discriminator, avatar, access_token, refresh_token, token_expires, created_at, last_login) 
            VALUES 
            (:discord_id, :username, :discriminator, :avatar, :access_token, :refresh_token, :token_expires, NOW(), NOW())");
            
        $username = $user_response['username'];
        $discriminator = $user_response['discriminator'] ?? '';
        $avatar = $user_response['avatar'];
        $access_token = $original_access_token;
        $refresh_token = $original_refresh_token;
        $token_expires = $_SESSION['discord_token_expires'];
        
        $insertStmt->bindParam(':discord_id', $discord_id);
        $insertStmt->bindParam(':username', $username);
        $insertStmt->bindParam(':discriminator', $discriminator);
        $insertStmt->bindParam(':avatar', $avatar);
        $insertStmt->bindParam(':access_token', $access_token);
        $insertStmt->bindParam(':refresh_token', $refresh_token);
        $insertStmt->bindParam(':token_expires', $token_expires);
        
        $insertStmt->execute();
    }
    
    $_SESSION['discord_success'] = 'Successfully logged in with Discord!';
} catch (PDOException $e) {
    error_log('Discord login database error: ' . $e->getMessage());
    $_SESSION['discord_warning'] = 'Your login worked, but we had trouble saving your session.';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Discord Authentication</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 50px;
            background-color: #36393f;
            color: #ffffff;
        }
        .success-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
            background-color: #2f3136;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        h2 {
            color: #7289da;
        }
        .success-icon {
            color: #43b581;
            font-size: 48px;
            margin: 20px 0;
        }
        .redirect-text {
            margin: 20px 0;
        }
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(114,137,218,0.3);
            border-radius: 50%;
            border-top-color: #7289da;
            animation: spin 1s ease-in-out infinite;
            vertical-align: middle;
            margin-left: 10px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
    <script>
        // Set a timeout before redirecting to make sure the user sees the success message
        setTimeout(function() {
            // Redirect to the original referring page if available
            window.location.href = "<?php 
                $redirect = isset($_SESSION['discord_auth_referrer']) ? $_SESSION['discord_auth_referrer'] : '../index.php'; 
                // Clear the referrer from session to avoid reusing it
                unset($_SESSION['discord_auth_referrer']);
                echo $redirect;
            ?>";
        }, 2000);
    </script>
</head>
<body>
    <div class="success-container">
        <h2>Authentication Successful!</h2>
        <div class="success-icon">✓</div>
        <p>You've successfully connected your Discord account.</p>
        <p class="redirect-text">
            Redirecting to The Salty Parrot
            <span class="loading-spinner"></span>
        </p>
    </div>
</body>
</html>
