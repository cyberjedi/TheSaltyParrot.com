<?php
/**
 * Discord OAuth Callback for New UI
 * 
 * This script handles the OAuth callback from Discord for the new UI.
 * It's a complete replacement for the original callback, specifically for the new UI.
 */

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Use our new configuration
require_once 'discord-config.php';
require_once '../config/db_connect.php';

// Handle both GET and POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data || !isset($data['code']) || !isset($data['state'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request data']);
        exit;
    }
    
    $code = $data['code'];
    $state = $data['state'];
} else {
    // Handle GET request (direct callback from Discord)
    if (!isset($_GET['code'])) {
        http_response_code(400);
        echo json_encode(['error' => 'No authorization code received']);
        exit;
    }
    
    $code = $_GET['code'];
    $state = $_GET['state'] ?? '';
}

// Verify state parameter to prevent CSRF attacks
if (!isset($_SESSION['discord_oauth_state']) || $state !== $_SESSION['discord_oauth_state']) {
    // Log the state mismatch for debugging
    error_log('State mismatch. Session state: ' . ($_SESSION['discord_oauth_state'] ?? 'not set') . ', Received state: ' . $state);
    
    // Return error page that works in popup
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Authentication Failed</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                text-align: center;
                margin: 0;
                padding: 20px;
                background-color: #36393f;
                color: #ffffff;
            }
            .container {
                max-width: 500px;
                margin: 0 auto;
                padding: 20px;
            }
            h2 {
                color: #f04747;
                margin-bottom: 20px;
            }
            .error-icon {
                font-size: 48px;
                color: #f04747;
                margin: 20px 0;
            }
            .message {
                margin: 20px 0;
                line-height: 1.5;
            }
        </style>
        <script>
            window.onload = function() {
                // Close popup and reload parent after delay
                setTimeout(function() {
                    if (window.opener) {
                        window.opener.location.reload();
                    }
                    window.close();
                }, 3000);
            };
        </script>
    </head>
    <body>
        <div class="container">
            <h2>Authentication Failed</h2>
            <div class="error-icon">✕</div>
            <div class="message">
                <p>Invalid authentication state. Please try again.</p>
                <p>This window will close automatically...</p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Clear the state from session
unset($_SESSION['discord_oauth_state']);

// Exchange the code for an access token
$token_data = [
    'client_id' => DISCORD_CLIENT_ID,
    'client_secret' => DISCORD_CLIENT_SECRET,
    'grant_type' => 'authorization_code',
    'code' => $code,
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
    http_response_code($http_code);
    echo json_encode(['error' => 'Failed to exchange code for token']);
    exit;
}

// Check for missing or malformed tokens
if (!isset($token_response['access_token']) || !isset($token_response['refresh_token']) || !isset($token_response['expires_in'])) {
    $error_message = 'Invalid token response from Discord';
    error_log($error_message . ': ' . substr($response, 0, 100) . '...');
    http_response_code(500);
    echo json_encode(['error' => $error_message]);
    exit;
}

// Store tokens in session
$_SESSION['discord_access_token'] = $token_response['access_token'];
$_SESSION['discord_refresh_token'] = $token_response['refresh_token'];
$_SESSION['discord_token_expires'] = time() + $token_response['expires_in'];

// Fetch user information using new UI API request function
$user_response = discord_api_request('/users/@me', 'GET', [], $_SESSION['discord_access_token']);

if (!isset($user_response['id'])) {
    error_log('Failed to fetch user information: ' . json_encode($user_response));
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch user information']);
    exit;
}

// Store basic user data in session
$_SESSION['discord_user'] = [
    'id' => $user_response['id'],
    'username' => $user_response['username'],
    'discriminator' => $user_response['discriminator'] ?? '',
    'avatar' => $user_response['avatar'],
    'avatar_url' => isset($user_response['avatar']) 
        ? "https://cdn.discordapp.com/avatars/{$user_response['id']}/{$user_response['avatar']}.png" 
        : "https://cdn.discordapp.com/embed/avatars/0.png"
];

// Store the user in the database
try {
    global $conn;
    
    if (!$conn) {
        throw new Exception('Database connection not available');
    }
    
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
        $access_token = $_SESSION['discord_access_token'];
        $refresh_token = $_SESSION['discord_refresh_token'];
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
        $access_token = $_SESSION['discord_access_token'];
        $refresh_token = $_SESSION['discord_refresh_token'];
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
} catch (Exception $e) {
    error_log('Discord login database error: ' . $e->getMessage());
    // Continue even if database update fails
}

// If this was a POST request, return JSON response
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo json_encode([
        'success' => true,
        'user' => $_SESSION['discord_user']
    ]);
    exit;
}

// Otherwise, display success page and close popup
?>
<!DOCTYPE html>
<html>
<head>
    <title>Discord Authentication Success</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin: 0;
            padding: 20px;
            background-color: #36393f;
            color: #ffffff;
        }
        .container {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
        }
        h2 {
            color: #43b581;
            margin-bottom: 20px;
        }
        .success-icon {
            font-size: 48px;
            color: #43b581;
            margin: 20px 0;
        }
        .message {
            margin: 20px 0;
            line-height: 1.5;
        }
    </style>
    <script>
        window.onload = function() {
            setTimeout(function() {
                if (window.opener && !window.opener.closed) {
                    window.opener.location.reload();
                    window.close();
                } else {
                    window.location.href = '../index.php';
                }
            }, 2000);
        };
    </script>
</head>
<body>
    <div class="container">
        <h2>Authentication Successful</h2>
        <div class="success-icon">✓</div>
        <div class="message">
            <p>Successfully connected to Discord!</p>
            <p>This window will close automatically...</p>
        </div>
    </div>
</body>
</html>