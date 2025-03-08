<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database config
require_once 'config/db_connect.php';

// Check if discord-config exists
$config_found = file_exists('discord/discord-config.php');
if ($config_found) {
    require_once 'discord/discord-config.php';
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Discord Auth Debugger</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        h1, h2 { color: #5865F2; }
        .error { color: red; }
        .success { color: green; }
        .warning { color: orange; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }
        .card { border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin-bottom: 20px; }
        .actions { margin-top: 20px; }
        .btn { padding: 10px 15px; background: #5865F2; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin-right: 10px; }
        .btn-danger { background: #ED4245; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Discord Authentication Debugger</h1>";

// 1. Database Connection Check
echo "<div class='card'>";
echo "<h2>Database Connection</h2>";
if (isset($conn) && $conn instanceof PDO) {
    echo "<p class='success'>Connected to database ✓</p>";
    
    // Check token columns
    try {
        $stmt = $conn->prepare("DESCRIBE discord_users");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Token column details:</p><ul>";
        foreach ($columns as $column) {
            if ($column['Field'] == 'access_token' || $column['Field'] == 'refresh_token') {
                echo "<li>{$column['Field']}: {$column['Type']}</li>";
            }
        }
        echo "</ul>";
        
        // Show current token from database
        $stmt = $conn->prepare("SELECT * FROM discord_users LIMIT 1");
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<p>Current token in database for user <strong>{$user['username']}</strong>:</p>";
            echo "<p>Token length: " . strlen($user['access_token']) . " characters</p>";
            echo "<p>Expired: " . ($user['token_expires'] < time() ? 'Yes' : 'No') . "</p>";
            echo "<p>Last login: " . $user['last_login'] . "</p>";
        } else {
            echo "<p class='warning'>No users found in database</p>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>Error checking database: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p class='error'>Database connection failed</p>";
}
echo "</div>";

// 2. Discord Configuration Check
echo "<div class='card'>";
echo "<h2>Discord Configuration</h2>";
echo "<p>Config file exists: " . ($config_found ? "<span class='success'>Yes ✓</span>" : "<span class='error'>No ✗</span>") . "</p>";

if ($config_found) {
    echo "<p>Constants defined:</p><ul>";
    echo "<li>DISCORD_CLIENT_ID: " . (defined('DISCORD_CLIENT_ID') ? "<span class='success'>Yes ✓</span>" : "<span class='error'>No ✗</span>") . "</li>";
    echo "<li>DISCORD_CLIENT_SECRET: " . (defined('DISCORD_CLIENT_SECRET') ? "<span class='success'>Yes ✓</span>" : "<span class='error'>No ✗</span>") . "</li>";
    echo "<li>DISCORD_REDIRECT_URI: " . (defined('DISCORD_REDIRECT_URI') ? "<span class='success'>Yes ✓</span>" : "<span class='error'>No ✗</span>") . "</li>";
    echo "</ul>";
}
echo "</div>";

// 3. Session Data Check
echo "<div class='card'>";
echo "<h2>Current Session Data</h2>";

if (isset($_SESSION['discord_user'])) {
    echo "<p class='success'>Discord user found in session ✓</p>";
    echo "<p>Username: " . htmlspecialchars($_SESSION['discord_user']['username']) . "</p>";
    echo "<p>Discord ID: " . htmlspecialchars($_SESSION['discord_user']['id']) . "</p>";
    echo "<p>Has avatar: " . (isset($_SESSION['discord_user']['avatar']) ? 'Yes' : 'No') . "</p>";
} else {
    echo "<p class='warning'>No discord_user in session</p>";
}

if (isset($_SESSION['discord_access_token'])) {
    echo "<p class='success'>Access token found in session ✓</p>";
    echo "<p>Token length: " . strlen($_SESSION['discord_access_token']) . " characters</p>";
    echo "<p>First 10 chars: " . htmlspecialchars(substr($_SESSION['discord_access_token'], 0, 10)) . "...</p>";
    
    if (strlen($_SESSION['discord_access_token']) < 50) {
        echo "<p class='error'>WARNING: Token appears truncated! Discord tokens should be 100+ characters</p>";
    }
    
    if (isset($_SESSION['discord_token_expires'])) {
        echo "<p>Token expires: " . date('Y-m-d H:i:s', $_SESSION['discord_token_expires']) . "</p>";
        echo "<p>Token status: " . ($_SESSION['discord_token_expires'] > time() ? 
            "<span class='success'>Valid</span>" : 
            "<span class='error'>Expired</span>") . "</p>";
    }
} else {
    echo "<p class='warning'>No access token in session</p>";
}
echo "</div>";

// 4. Fix Actions
echo "<div class='card actions'>";
echo "<h2>Fix Actions</h2>";

// Create a clean login URL with state
$state = bin2hex(random_bytes(16));
$_SESSION['discord_oauth_state'] = $state;
$auth_url = "";

if (defined('DISCORD_API_URL') && defined('DISCORD_CLIENT_ID') && defined('DISCORD_REDIRECT_URI')) {
    $auth_url = DISCORD_API_URL . '/oauth2/authorize';
    $auth_url .= '?client_id=' . DISCORD_CLIENT_ID;
    $auth_url .= '&redirect_uri=' . urlencode(DISCORD_REDIRECT_URI);
    $auth_url .= '&response_type=code';
    $auth_url .= '&state=' . $state;
    $auth_url .= '&scope=identify%20guilds';
}

// Action buttons
echo "<p><a href='discord/discord-logout.php' class='btn btn-danger'>Clear Discord Session</a></p>";

if (!empty($auth_url)) {
    echo "<p><a href='{$auth_url}' class='btn'>Login with Discord (Direct)</a></p>";
}

echo "<p><a href='discord/discord-login.php' class='btn'>Standard Discord Login</a></p>";

// Extra debug utility
echo "<p>If you've already tried logging in and had issues, check the fix_discord_auth.php script for additional debugging information.</p>";

// 5. Provide code fix for discord-callback.php
echo "<h3>Fix for discord-callback.php</h3>";
echo "<p>Here's a suggested fix if the token is getting truncated in the callback:</p>";
echo "<pre>
// In discord-callback.php, look for this section and add the debug logs:

\$token_response = json_decode(\$response, true);

// Add this debug logging to see token details
if (isset(\$token_response['access_token'])) {
    error_log('Access token length: ' . strlen(\$token_response['access_token']));
    error_log('Access token first 10 chars: ' . substr(\$token_response['access_token'], 0, 10));
}

// Make sure token isn't getting modified when storing in session
\$_SESSION['discord_access_token'] = \$token_response['access_token'];
\$_SESSION['discord_refresh_token'] = \$token_response['refresh_token'];
\$_SESSION['discord_token_expires'] = time() + \$token_response['expires_in'];

// Double check it's stored correctly
error_log('Stored token length: ' . strlen(\$_SESSION['discord_access_token']));
</pre>";

echo "</div>";

echo "</div>
</body>
</html>";
?>
