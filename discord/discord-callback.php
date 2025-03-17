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

// Get return URL from session or default to new UI index
$return_url = isset($_SESSION['discord_ui_return']) ? $_SESSION['discord_ui_return'] : '../index.php';

// Clear this session variable
unset($_SESSION['discord_ui_return']);

// Check if this was initiated from new UI
$from = isset($_SESSION['from']) && $_SESSION['from'] === true;

// Helper function to render error page
function renderErrorPage($error_message) {
    global $return_url;
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
            .button {
                display: inline-block;
                background-color: #7289da;
                color: white;
                padding: 10px 20px;
                text-decoration: none;
                border-radius: 4px;
                margin-top: 20px;
            }
        </style>
        <script>
            window.onload = function() {
                setTimeout(function() {
                    if (window.opener && !window.opener.closed) {
                        window.close();
                    } else {
                        window.location.href = '<?php echo $return_url; ?>';
                    }
                }, 3000);
            };
        </script>
    </head>
    <body>
        <div class="container">
            <h2>Authentication Failed</h2>
            <div class="error-icon">✕</div>
            <div class="message">
                <p><?php echo htmlspecialchars($error_message); ?></p>
                <p>This window will close automatically...</p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ===== Begin Discord authentication logic =====

// Check for errors or authorization denial
if (isset($_GET['error'])) {
    $_SESSION['discord_error'] = 'Authorization denied: ' . $_GET['error_description'];
    renderErrorPage('Authorization denied: ' . $_GET['error_description']);
}

// Verify state parameter to prevent CSRF attacks
if (!isset($_GET['state']) || !isset($_SESSION['discord_oauth_state']) || $_GET['state'] !== $_SESSION['discord_oauth_state']) {
    $_SESSION['discord_error'] = 'Invalid state parameter. Please try again.';
    renderErrorPage('Invalid state parameter. Please try again.');
}

// Clear the state from session
unset($_SESSION['discord_oauth_state']);
unset($_SESSION['from']);

// Check for the authorization code
if (!isset($_GET['code'])) {
    $_SESSION['discord_error'] = 'No authorization code received.';
    renderErrorPage('No authorization code received. Please try again.');
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
    $_SESSION['discord_error'] = 'Failed to exchange code for token.';
    renderErrorPage('Failed to exchange code for token. Please try again.');
}

// Check for missing or malformed tokens
if (!isset($token_response['access_token']) || !isset($token_response['refresh_token']) || !isset($token_response['expires_in'])) {
    $error_message = 'Invalid token response from Discord';
    error_log($error_message . ': ' . substr($response, 0, 100) . '...');
    $_SESSION['discord_error'] = $error_message;
    renderErrorPage('Invalid response from Discord. Please try again.');
}

// Store tokens in session
$_SESSION['discord_access_token'] = $token_response['access_token'];
$_SESSION['discord_refresh_token'] = $token_response['refresh_token'];
$_SESSION['discord_token_expires'] = time() + $token_response['expires_in'];

// Fetch user information using new UI API request function
$user_response = discord_api_request('/users/@me', 'GET', [], $_SESSION['discord_access_token']);

if (!isset($user_response['id'])) {
    error_log('Failed to fetch user information: ' . json_encode($user_response));
    $_SESSION['discord_error'] = 'Failed to fetch user information.';
    renderErrorPage('Failed to fetch your Discord user information. Please try again.');
}

// Store basic user data in session
$_SESSION['discord_user'] = [
    'id' => $user_response['id'],
    'username' => $user_response['username'],
    'discriminator' => $user_response['discriminator'] ?? '',
    'avatar' => $user_response['avatar']
];

// Store the user in the database - use new DB connection
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
    
    $_SESSION['discord_success'] = 'Successfully logged in with Discord!';
} catch (Exception $e) {
    error_log('Discord login database error: ' . $e->getMessage());
    $_SESSION['discord_warning'] = 'Your login worked, but we had trouble saving your session.';
}

// Add a clear message about the success for new UI
$_SESSION['discord_success'] = 'Successfully connected to Discord! You can now send content to your Discord servers.';

// Display a success page with JavaScript to close popup and reload parent
?>
<!DOCTYPE html>
<html>
<head>
    <title>Authentication Successful</title>
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
            color: #7289da;
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
        // Close popup and reload parent window after a short delay
        window.onload = function() {
            // Display success message for a moment
            setTimeout(function() {
                // If this window was opened by another window, close it and refresh parent
                if (window.opener && !window.opener.closed) {
                    // Try to redirect the parent window to the New UI
                    try {
                        window.opener.location.href = '<?php echo $return_url; ?>';
                    } catch(e) {
                        console.error("Could not redirect parent window:", e);
                    }
                    // Close this popup
                    window.close();
                } else {
                    // If not in a popup, redirect to the New UI
                    window.location.href = '<?php echo $return_url; ?>';
                }
            }, 1500);
        };
    </script>
</head>
<body>
    <div class="container">
        <h2>Authentication Successful!</h2>
        <div class="success-icon">✓</div>
        <div class="message">
            <p>You've successfully connected to Discord.</p>
            <p>This window will close automatically...</p>
        </div>
    </div>
</body>
</html>