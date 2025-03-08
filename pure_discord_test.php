<?php
// Save as pure_discord_test.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Define constants directly here, not using any existing code
define('DISCORD_CLIENT_ID', 'YOUR_CLIENT_ID'); // Add your actual client ID
define('DISCORD_CLIENT_SECRET', 'YOUR_CLIENT_SECRET'); // Add your actual client secret
define('DISCORD_REDIRECT_URI', 'http://yourdomain.com/pure_discord_callback.php');
define('DISCORD_API_URL', 'https://discord.com/api/v10');

// Generate a state parameter for CSRF protection
$state = bin2hex(random_bytes(16));
$_SESSION['pure_discord_state'] = $state;

// Build the authorization URL
$auth_url = DISCORD_API_URL . '/oauth2/authorize';
$auth_url .= '?client_id=' . DISCORD_CLIENT_ID;
$auth_url .= '&redirect_uri=' . urlencode(DISCORD_REDIRECT_URI);
$auth_url .= '&response_type=code';
$auth_url .= '&state=' . $state;
$auth_url .= '&scope=' . urlencode('identify guilds');

echo "<h1>Pure Discord Authentication Test</h1>";
echo "<p>This test bypasses all existing authentication code to test Discord directly.</p>";
echo "<a href='$auth_url'>Authenticate with Discord</a>";

// Also create the callback file (pure_discord_callback.php) with this content:
/*
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

define('DISCORD_CLIENT_ID', 'YOUR_CLIENT_ID');
define('DISCORD_CLIENT_SECRET', 'YOUR_CLIENT_SECRET');
define('DISCORD_REDIRECT_URI', 'http://yourdomain.com/pure_discord_callback.php');
define('DISCORD_API_URL', 'https://discord.com/api/v10');

echo "<h1>Pure Discord Callback</h1>";

// Verify state
if (!isset($_GET['state']) || !isset($_SESSION['pure_discord_state']) || $_GET['state'] !== $_SESSION['pure_discord_state']) {
    echo "<p>Error: Invalid state parameter</p>";
    exit;
}

// Check for code
if (!isset($_GET['code'])) {
    echo "<p>Error: No code received</p>";
    exit;
}

// Exchange code for token with no dependencies on existing code
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, DISCORD_API_URL . '/oauth2/token');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'client_id' => DISCORD_CLIENT_ID,
    'client_secret' => DISCORD_CLIENT_SECRET,
    'grant_type' => 'authorization_code',
    'code' => $_GET['code'],
    'redirect_uri' => DISCORD_REDIRECT_URI
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h2>Token Response:</h2>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Show token details
$token_data = json_decode($response, true);
if (isset($token_data['access_token'])) {
    echo "<h2>Token Details:</h2>";
    echo "Access Token Length: " . strlen($token_data['access_token']) . "<br>";
    echo "Access Token: " . htmlspecialchars($token_data['access_token']) . "<br>";
    echo "Token Type: " . htmlspecialchars($token_data['token_type']) . "<br>";
    echo "Expires In: " . htmlspecialchars($token_data['expires_in']) . "<br>";
    echo "Scope: " . htmlspecialchars($token_data['scope']) . "<br>";
    
    // Test the token by making a request to the Discord API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, DISCORD_API_URL . '/users/@me');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token_data['access_token']
    ]);
    
    $user_response = curl_exec($ch);
    $user_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<h2>User API Response (HTTP $user_http_code):</h2>";
    echo "<pre>" . htmlspecialchars($user_response) . "</pre>";
}
?>
*/
