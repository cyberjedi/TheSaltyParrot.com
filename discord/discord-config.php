<?php
// File: discord/discord-config.php
// This file contains Discord API configuration

// Possible config file locations with priority
$possible_config_paths = [
    $_SERVER['DOCUMENT_ROOT'] . '/../../private/secure_variables.php',
    $_SERVER['DOCUMENT_ROOT'] . '/../private/secure_variables.php',
    $_SERVER['DOCUMENT_ROOT'] . '/private/secure_variables.php',
    dirname(__FILE__) . '/../../private/secure_variables.php'
];

// Find and load the config file
$config = null;
foreach ($possible_config_paths as $path) {
    if (file_exists($path)) {
        $config = require_once($path);
        break;
    }
}

// If no config file found, throw an error
if ($config === null) {
    error_log('Discord configuration file not found. Checked paths: ' . implode(', ', $possible_config_paths));
    // Define defaults to prevent errors
    define('DISCORD_CLIENT_ID', '');
    define('DISCORD_CLIENT_SECRET', '');
    define('DISCORD_REDIRECT_URI', '');
    define('DISCORD_API_URL', 'https://discord.com/api/v10');
} else {
    // Define constants from loaded configuration
    // Check if 'discord' key exists and is an array
    if (isset($config['discord']) && is_array($config['discord'])) {
        define('DISCORD_CLIENT_ID', isset($config['discord']['client_id']) ? $config['discord']['client_id'] : '');
        define('DISCORD_CLIENT_SECRET', isset($config['discord']['client_secret']) ? $config['discord']['client_secret'] : '');
        
        // Determine which redirect URI to use based on current hostname
        if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'dev.') === 0) {
            define('DISCORD_REDIRECT_URI', isset($config['discord']['dev_redirect_uri']) ? $config['discord']['dev_redirect_uri'] : '');
        } else {
            define('DISCORD_REDIRECT_URI', isset($config['discord']['redirect_uri']) ? $config['discord']['redirect_uri'] : '');
        }
        
        define('DISCORD_API_URL', isset($config['discord']['api_url']) ? $config['discord']['api_url'] : 'https://discord.com/api/v10');
    } else {
        // Discord section doesn't exist in config
        error_log('Discord section not found in configuration file');
        define('DISCORD_CLIENT_ID', '');
        define('DISCORD_CLIENT_SECRET', '');
        define('DISCORD_REDIRECT_URI', '');
        define('DISCORD_API_URL', 'https://discord.com/api/v10');
    }
}

// Sessions configuration
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters - always apply these security settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    
    // Set secure flag in production or when HTTPS is detected
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' || 
        getenv('ENVIRONMENT') !== 'development') {
        ini_set('session.cookie_secure', 1);
    }
    
    // Set session lifetime (30 days)
    ini_set('session.gc_maxlifetime', 2592000);
    ini_set('session.cookie_lifetime', 2592000);
    
    // Start the session
    session_start();
}

// Improved helper function for making Discord API requests
function discord_api_request($endpoint, $method = 'GET', $data = [], $token = null) {
    $ch = curl_init();
    
    $headers = ['Accept: application/json', 'Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    $url = DISCORD_API_URL . $endpoint;
    
    // Only log detailed API information in development environment
    if (getenv('ENVIRONMENT') == 'development') {
        error_log("Discord API Request: $method $url");
        if ($token) {
            error_log("Using token (first 10 chars): " . substr($token, 0, 10) . "...");
            error_log("Token length: " . strlen($token));
        }
    }
    
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
    error_log("Discord API Response Code: $http_code");
    if ($curl_error) {
        error_log("Discord API curl error: $curl_error");
    }
    
    $result = json_decode($response, true);
    
    if ($http_code < 200 || $http_code >= 300) {
        error_log("Discord API Error ($http_code): " . ($result['message'] ?? $response));
        // Log the full error response if debugging
        if (isset($result['message'])) {
            error_log("Error details: " . json_encode($result));
        }
    }
    
    return $result;
}

// Function to check if a user is logged in with Discord
function is_discord_authenticated() {
    return isset($_SESSION['discord_user']) && 
           isset($_SESSION['discord_access_token']) && 
           isset($_SESSION['discord_token_expires']);
}

// Function to check if token needs refreshing
function refresh_discord_token_if_needed() {
    // If not authenticated, no token to refresh
    if (!is_discord_authenticated()) {
        error_log("Discord token refresh attempted while not authenticated");
        return false;
    }
    
    // If token is still valid (with 5 minute buffer), do nothing
    if ($_SESSION['discord_token_expires'] > (time() + 300)) {
        return true;
    }
    
    error_log("Discord token expired. Attempting refresh...");
    
    return force_discord_token_refresh();
}

// Function to force a token refresh regardless of expiration time
function force_discord_token_refresh() {
    // Check if we're authenticated first
    if (!is_discord_authenticated()) {
        error_log("Token refresh attempted without authentication");
        return false;
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
        
        // Detailed error logging for token refresh
        error_log("Attempting to refresh Discord token for user: " . $_SESSION['discord_user']['id'] . 
                  " with refresh token starting with: " . substr($_SESSION['discord_refresh_token'], 0, 10) . "...");
        
        curl_setopt($ch, CURLOPT_URL, DISCORD_API_URL . '/oauth2/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        // Log response details
        if ($curl_error) {
            error_log("Discord token refresh curl error: " . $curl_error);
        }
        
        if ($http_code >= 200 && $http_code < 300) {
            $result = json_decode($response, true);
            
            if (!isset($result['access_token']) || !isset($result['refresh_token']) || !isset($result['expires_in'])) {
                error_log("Discord token refresh invalid response: " . substr($response, 0, 100) . "...");
                return false;
            }
            
            // Update session with new tokens
            $_SESSION['discord_access_token'] = $result['access_token'];
            $_SESSION['discord_refresh_token'] = $result['refresh_token'];
            $_SESSION['discord_token_expires'] = time() + $result['expires_in'];
            
            error_log("Discord token refreshed successfully. New token starts with: " . 
                      substr($_SESSION['discord_access_token'], 0, 10) . "...");
            
            // Update database with new tokens
            try {
                require_once dirname(__FILE__) . '/../config/db_connect.php';
                if (isset($conn) && isset($_SESSION['discord_user']['id'])) {
                    $stmt = $conn->prepare("UPDATE discord_users SET 
                        access_token = :access_token, 
                        refresh_token = :refresh_token, 
                        token_expires = :token_expires
                        WHERE discord_id = :discord_id");
                        
                    $access_token = $result['access_token'];
                    $refresh_token = $result['refresh_token'];
                    $token_expires = $_SESSION['discord_token_expires'];
                    $discord_id = $_SESSION['discord_user']['id'];
                    
                    $stmt->bindParam(':access_token', $access_token);
                    $stmt->bindParam(':refresh_token', $refresh_token);
                    $stmt->bindParam(':token_expires', $token_expires);
                    $stmt->bindParam(':discord_id', $discord_id);
                    
                    $stmt->execute();
                    error_log("Updated tokens in database for user " . $discord_id);
                }
            } catch (Exception $e) {
                error_log("Error updating database with refreshed tokens: " . $e->getMessage());
                // Continue even if database update fails
            }
            
            return true;
        } else {
            // Log the error response
            error_log("Discord token refresh failed (HTTP " . $http_code . "): " . $response);
            
            // Only clear the session if there was an actual auth error (not a network issue)
            if ($http_code == 400 || $http_code == 401) {
                error_log("Auth error detected, clearing Discord session");
                unset($_SESSION['discord_user']);
                unset($_SESSION['discord_access_token']);
                unset($_SESSION['discord_refresh_token']);
                unset($_SESSION['discord_token_expires']);
            }
            
            return false;
        }
    }
    
    error_log("No refresh token available");
    return false;
}

/**
 * Renders a Discord login button
 * 
 * @param string $size Button size (small, medium, large)
 * @param string $color Button color (dark, light)
 * @param string $text Button text (defaults to "Login with Discord")
 * @return void Outputs the HTML button
 */
function renderDiscordLoginButton($size = 'medium', $color = 'dark', $text = 'Login with Discord') {
    $sizeClass = '';
    switch ($size) {
        case 'small':
            $sizeClass = 'discord-btn-sm';
            break;
        case 'large':
            $sizeClass = 'discord-btn-lg';
            break;
        default:
            $sizeClass = 'discord-btn-md';
    }
    
    $colorClass = $color === 'light' ? 'discord-btn-light' : 'discord-btn-dark';
    
    echo '<a href="discord/discord-login.php" class="discord-btn ' . $sizeClass . ' ' . $colorClass . '">';
    echo '<i class="fab fa-discord"></i> ' . htmlspecialchars($text);
    echo '</a>';
}

/**
 * Renders the Discord user profile section
 * 
 * @param array $user Discord user data
 * @return void Outputs the HTML component
 */
function renderDiscordUserProfile($user) {
    if (empty($user)) {
        return;
    }
    
    // Construct avatar URL
    $avatarUrl = $user['avatar'] 
        ? 'https://cdn.discordapp.com/avatars/' . $user['id'] . '/' . $user['avatar'] . '.png' 
        : 'https://cdn.discordapp.com/embed/avatars/0.png';
    
    // Format username
    $usernameDisplay = htmlspecialchars($user['username']);
    if (!empty($user['discriminator']) && $user['discriminator'] !== '0') {
        $usernameDisplay .= '#' . htmlspecialchars($user['discriminator']);
    }
    
    echo '<div class="discord-profile">';
    echo '<img src="' . $avatarUrl . '" alt="Discord Avatar" class="discord-avatar">';
    echo '<div class="discord-info">';
    echo '<div class="discord-username">' . $usernameDisplay . '</div>';
    echo '<div class="discord-buttons">';
    echo '<a href="discord/webhooks.php" class="discord-btn-sm discord-btn-light">Manage Webhooks</a>';
    echo '<a href="discord/discord-logout.php" class="discord-btn-sm discord-btn-light">Logout</a>';
    echo '</div>'; // End discord-buttons
    echo '</div>'; // End discord-info
    echo '</div>'; // End discord-profile
}

/**
 * Renders the Discord connection status in dashboard
 * 
 * @return void Outputs the HTML component
 */
function renderDiscordConnectionStatus() {
    // Check if user is logged in with Discord
    if (is_discord_authenticated()) {
        $user = $_SESSION['discord_user'];
        
        // Format avatar URL
        $avatarUrl = $user['avatar'] 
            ? 'https://cdn.discordapp.com/avatars/' . $user['id'] . '/' . $user['avatar'] . '.png' 
            : 'https://cdn.discordapp.com/embed/avatars/0.png';
        
        // Format username
        $usernameDisplay = htmlspecialchars($user['username']);
        if (!empty($user['discriminator']) && $user['discriminator'] !== '0') {
            $usernameDisplay .= '#' . htmlspecialchars($user['discriminator']);
        }
        
        echo '<div class="discord-connection-status connected">';
        echo '<div class="discord-status-content">';
        echo '<img src="' . $avatarUrl . '" alt="Discord Avatar" class="discord-status-avatar">';
        echo '<div class="discord-status-info">';
        echo '<div class="discord-status-title">Connected as <strong>' . $usernameDisplay . '</strong></div>';
        echo '<div class="discord-status-text">Send generated content to your Discord channels.</div>';
        echo '</div>'; // End discord-status-info
        echo '</div>'; // End discord-status-content
        echo '</div>'; // End discord-connection-status
    } else {
        // Not connected - show simple status message
        echo '<div class="discord-connection-status not-connected">';
        echo '<div class="discord-status-content">';
        echo '<i class="fab fa-discord discord-status-icon"></i>';
        echo '<div class="discord-status-info">';
        echo '<div class="discord-status-title">Discord Not Connected</div>';
        echo '<div class="discord-status-text">Connect Discord using the button in the sidebar to share content to your channels.</div>';
        echo '</div>'; // End discord-status-info
        echo '</div>'; // End discord-status-content
        echo '</div>'; // End discord-connection-status
    }
    
// Display any pending messages
    if (isset($_SESSION['discord_error'])) {
        echo '<div class="discord-message error">' . htmlspecialchars($_SESSION['discord_error']) . '</div>';
        unset($_SESSION['discord_error']);
    }
    
    if (isset($_SESSION['discord_success'])) {
        echo '<div class="discord-message success">' . htmlspecialchars($_SESSION['discord_success']) . '</div>';
        unset($_SESSION['discord_success']);
    }
    
    if (isset($_SESSION['discord_warning'])) {
        echo '<div class="discord-message warning">' . htmlspecialchars($_SESSION['discord_warning']) . '</div>';
        unset($_SESSION['discord_warning']);
    }
    
    if (isset($_SESSION['discord_message'])) {
        echo '<div class="discord-message info">' . htmlspecialchars($_SESSION['discord_message']) . '</div>';
        unset($_SESSION['discord_message']);
    }
}

function debug_discord_token() {
    if (!is_discord_authenticated()) {
        return "Not authenticated";
    }
    
    $token = $_SESSION['discord_access_token'];
    $token_parts = explode('.', $token);
    if (count($token_parts) != 3) {
        return "Invalid token format";
    }
    
    // Decode the middle part of the JWT token
    $payload = json_decode(base64_decode(str_replace(
        ['-', '_'], 
        ['+', '/'], 
        $token_parts[1]
    )), true);
    
    return $payload;
}

?>
