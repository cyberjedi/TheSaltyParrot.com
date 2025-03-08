<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discord Integration Test</title>
    <style>
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1, h2, h3 {
            color: #5865F2;
            margin-bottom: 15px;
        }
        h1 {
            font-size: 28px;
            border-bottom: 2px solid #5865F2;
            padding-bottom: 10px;
        }
        h2 {
            font-size: 22px;
            margin-top: 30px;
        }
        h3 {
            font-size: 18px;
            margin-top: 25px;
        }
        hr {
            border: 0;
            height: 1px;
            background-color: #ddd;
            margin: 20px 0;
        }
        .success {
            color: #28a745;
        }
        .error {
            color: #dc3545;
        }
        .warning {
            color: #ffc107;
        }
        .check {
            display: inline-block;
            width: 20px;
            height: 20px;
            line-height: 20px;
            text-align: center;
            border-radius: 50%;
            font-weight: bold;
            color: white;
        }
        .check.pass {
            background-color: #28a745;
        }
        .check.fail {
            background-color: #dc3545;
        }
        .check.warn {
            background-color: #ffc107;
            color: #333;
        }
        .section {
            margin-bottom: 40px;
            padding: 20px;
            border-radius: 8px;
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
        }
        pre {
            background-color: #f0f0f0;
            padding: 12px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 13px;
            border: 1px solid #ddd;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #5865F2;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px 10px 0;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        .btn:hover {
            background-color: #4752c4;
        }
        .btn.danger {
            background-color: #dc3545;
        }
        .btn.danger:hover {
            background-color: #c82333;
        }
        .btn.success {
            background-color: #28a745;
        }
        .btn.success:hover {
            background-color: #218838;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin: 15px 0;
            background-color: white;
        }
        table, th, td {
            border: 1px solid #dee2e6;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        tr:hover {
            background-color: #f2f2f2;
        }
        .result-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .result-row:last-child {
            border-bottom: none;
        }
        .result-key {
            font-weight: 500;
            flex: 1;
        }
        .result-value {
            flex: 2;
        }
        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-label {
            color: #6c757d;
            font-size: 14px;
        }
        .tab-container {
            margin: 20px 0;
        }
        .tab-buttons {
            display: flex;
            border-bottom: 1px solid #dee2e6;
        }
        .tab-button {
            padding: 10px 20px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-bottom: none;
            border-radius: 5px 5px 0 0;
            margin-right: 5px;
            cursor: pointer;
        }
        .tab-button.active {
            background-color: white;
            border-bottom: 1px solid white;
            margin-bottom: -1px;
            font-weight: bold;
        }
        .tab-content {
            padding: 20px;
            border: 1px solid #dee2e6;
            border-top: none;
            border-radius: 0 0 5px 5px;
            background-color: white;
        }
        .tab-panel {
            display: none;
        }
        .tab-panel.active {
            display: block;
        }
        .legend {
            display: flex;
            gap: 20px;
            margin: 20px 0;
            justify-content: center;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
    </style>
</head>
<body>
    <div class="container">';

echo "<h1>Discord Integration Test Dashboard</h1>";

echo '<div class="legend">
    <div class="legend-item"><span class="check pass">✓</span> Pass</div>
    <div class="legend-item"><span class="check fail">✗</span> Fail</div>
    <div class="legend-item"><span class="check warn">!</span> Warning</div>
</div>';

// DASHBOARD SUMMARY
echo '<div class="dashboard">';

// Session Token Status
$session_token_status = isset($_SESSION['discord_access_token']) ? (strlen($_SESSION['discord_access_token']) > 50 ? 'pass' : 'fail') : 'warn';
echo '<div class="stat-card">
    <span class="check ' . $session_token_status . '">' . ($session_token_status == 'pass' ? '✓' : ($session_token_status == 'fail' ? '✗' : '!')) . '</span>
    <div class="stat-value">' . (isset($_SESSION['discord_access_token']) ? strlen($_SESSION['discord_access_token']) : 'None') . '</div>
    <div class="stat-label">Session Token Length</div>
</div>';

// Database Connection
try {
    require_once 'config/db_connect.php';
    $db_status = (isset($conn) && $conn instanceof PDO) ? 'pass' : 'fail';
} catch (Exception $e) {
    $db_status = 'fail';
}
echo '<div class="stat-card">
    <span class="check ' . $db_status . '">' . ($db_status == 'pass' ? '✓' : '✗') . '</span>
    <div class="stat-value">' . ($db_status == 'pass' ? 'Connected' : 'Error') . '</div>
    <div class="stat-label">Database Connection</div>
</div>';

// Config File
$config_path = '/home/theshfmb/private/secure_variables.php';
$config_status = file_exists($config_path) ? 'pass' : 'fail';
echo '<div class="stat-card">
    <span class="check ' . $config_status . '">' . ($config_status == 'pass' ? '✓' : '✗') . '</span>
    <div class="stat-value">' . ($config_status == 'pass' ? 'Found' : 'Missing') . '</div>
    <div class="stat-label">Config File</div>
</div>';

// Discord API Connection
$api_status = 'warn';
if (isset($_SESSION['discord_access_token'])) {
    if (file_exists('discord/discord-config.php')) {
        require_once 'discord/discord-config.php';
        if (function_exists('discord_api_request')) {
            $response = discord_api_request('/users/@me', 'GET', [], $_SESSION['discord_access_token']);
            $api_status = isset($response['id']) ? 'pass' : 'fail';
        }
    }
}
echo '<div class="stat-card">
    <span class="check ' . $api_status . '">' . ($api_status == 'pass' ? '✓' : ($api_status == 'fail' ? '✗' : '!')) . '</span>
    <div class="stat-value">' . ($api_status == 'pass' ? 'Connected' : ($api_status == 'fail' ? 'Failed' : 'Not Tested')) . '</div>
    <div class="stat-label">Discord API Connection</div>
</div>';

echo '</div>'; // End dashboard

// TAB NAVIGATION
echo '<div class="tab-container">
    <div class="tab-buttons">
        <div class="tab-button active" data-tab="config">Configuration</div>
        <div class="tab-button" data-tab="token">Token Tests</div>
        <div class="tab-button" data-tab="api">API Tests</div>
        <div class="tab-button" data-tab="action">Actions</div>
    </div>
    <div class="tab-content">';

// TAB 1: CONFIGURATION
echo '<div class="tab-panel active" id="config-panel">';
echo "<h2>Configuration Tests</h2>";

// Config File Test
echo '<table>';
echo '<tr><th>Test</th><th>Result</th><th>Details</th></tr>';

// Check if config file exists
echo '<tr>';
echo '<td>Config File Exists</td>';
if (file_exists($config_path)) {
    echo '<td><span class="check pass">✓</span></td>';
    echo '<td>Found at: ' . $config_path . '</td>';
} else {
    echo '<td><span class="check fail">✗</span></td>';
    echo '<td>Not found at: ' . $config_path . '</td>';
}
echo '</tr>';

// Check if config file is valid
if (file_exists($config_path)) {
    try {
        $result = require($config_path);
        echo '<tr>';
        echo '<td>Config File Valid</td>';
        if (is_array($result)) {
            echo '<td><span class="check pass">✓</span></td>';
            echo '<td>Config is an array with keys: ' . implode(", ", array_keys($result)) . '</td>';
        } else {
            echo '<td><span class="check fail">✗</span></td>';
            echo '<td>Config is not an array: ' . gettype($result) . '</td>';
        }
        echo '</tr>';
        
        // Check for Discord config
        echo '<tr>';
        echo '<td>Discord Configuration</td>';
        if (isset($result['discord']) && is_array($result['discord'])) {
            echo '<td><span class="check pass">✓</span></td>';
            echo '<td>Found with keys: ' . implode(", ", array_keys($result['discord'])) . '</td>';
        } else {
            echo '<td><span class="check fail">✗</span></td>';
            echo '<td>Discord configuration missing or invalid</td>';
        }
        echo '</tr>';
        
        // Check Discord credentials
        if (isset($result['discord'])) {
            echo '<tr>';
            echo '<td>Discord Credentials</td>';
            if (
                isset($result['discord']['client_id']) && !empty($result['discord']['client_id']) &&
                isset($result['discord']['client_secret']) && !empty($result['discord']['client_secret']) &&
                (isset($result['discord']['redirect_uri']) || isset($result['discord']['dev_redirect_uri']))
            ) {
                echo '<td><span class="check pass">✓</span></td>';
                echo '<td>All required credentials present</td>';
            } else {
                echo '<td><span class="check fail">✗</span></td>';
                echo '<td>Missing some credentials</td>';
            }
            echo '</tr>';
        }
    } catch (Exception $e) {
        echo '<tr>';
        echo '<td>Config File Valid</td>';
        echo '<td><span class="check fail">✗</span></td>';
        echo '<td>Error: ' . $e->getMessage() . '</td>';
        echo '</tr>';
    }
}

// File structure tests
if (file_exists($config_path)) {
    $contents = file_get_contents($config_path);
    $lines = file($config_path);
    $first_line = trim($lines[0]);
    $last_line = trim($lines[count($lines)-1]);
    
    echo '<tr>';
    echo '<td>File Structure</td>';
    if ($first_line == '<?php' && $last_line == '];') {
        echo '<td><span class="check pass">✓</span></td>';
        echo '<td>Correct PHP structure</td>';
    } else {
        echo '<td><span class="check warn">!</span></td>';
        echo '<td>Unexpected structure: First line: "' . htmlspecialchars($first_line) . '", Last line: "' . htmlspecialchars($last_line) . '"</td>';
    }
    echo '</tr>';
    
    echo '<tr>';
    echo '<td>PHP Closing Tag</td>';
    if (substr(trim($contents), -2) != '?>') {
        echo '<td><span class="check pass">✓</span></td>';
        echo '<td>No PHP closing tag (good practice)</td>';
    } else {
        echo '<td><span class="check warn">!</span></td>';
        echo '<td>Contains PHP closing tag (may cause issues)</td>';
    }
    echo '</tr>';
}

echo '</table>';

// Database Structure
if ($db_status == 'pass') {
    echo "<h3>Database Structure</h3>";
    try {
        $stmt = $conn->prepare("DESCRIBE discord_users");
        $stmt->execute();
        $table_structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo '<table>';
        echo '<tr><th>Field</th><th>Type</th><th>Status</th></tr>';
        
        foreach ($table_structure as $column) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($column['Field']) . '</td>';
            echo '<td>' . htmlspecialchars($column['Type']) . '</td>';
            
            if ($column['Field'] === 'access_token' || $column['Field'] === 'refresh_token') {
                if (strpos(strtolower($column['Type']), 'varchar(2000)') !== false) {
                    echo '<td><span class="check pass">✓</span> Sufficient size</td>';
                } else {
                    echo '<td><span class="check fail">✗</span> Size too small! Should be VARCHAR(2000)</td>';
                }
            } else {
                echo '<td>-</td>';
            }
            
            echo '</tr>';
        }
        
        echo '</table>';
    } catch (PDOException $e) {
        echo "<p class='error'>Error checking table structure: " . $e->getMessage() . "</p>";
    }
}

echo '</div>'; // End config panel

// TAB 2: TOKEN TESTS
echo '<div class="tab-panel" id="token-panel">';
echo "<h2>Token Tests</h2>";

// Test 1: Session storage capacity
echo "<h3>Session Storage Test</h3>";
$testToken = str_repeat("ABCDEFGHIJKLMNOPQRSTUVWXYZ", 10); // 260 characters
$_SESSION['test_token'] = $testToken;

echo '<table>';
echo '<tr><th>Test</th><th>Result</th><th>Details</th></tr>';

echo '<tr>';
echo '<td>Session Token Storage</td>';
if (strlen($_SESSION['test_token']) === strlen($testToken)) {
    echo '<td><span class="check pass">✓</span></td>';
    echo '<td>Original length: ' . strlen($testToken) . ', Stored length: ' . strlen($_SESSION['test_token']) . '</td>';
} else {
    echo '<td><span class="check fail">✗</span></td>';
    echo '<td>Token truncated! Original: ' . strlen($testToken) . ', Stored: ' . strlen($_SESSION['test_token']) . '</td>';
}
echo '</tr>';

// Test 2: Database storage test
if ($db_status == 'pass') {
    try {
        // Insert test token to database
        $stmt = $conn->prepare("
            INSERT INTO discord_users 
            (discord_id, username, discriminator, avatar, access_token, refresh_token, token_expires, created_at, last_login) 
            VALUES 
            ('TEST_USER', 'test_user', '0000', 'test_avatar', :access_token, :refresh_token, :token_expires, NOW(), NOW())
            ON DUPLICATE KEY UPDATE 
            access_token = :access_token2, 
            refresh_token = :refresh_token2, 
            token_expires = :token_expires2,
            last_login = NOW()
        ");
        
        $stmt->bindParam(':access_token', $testToken);
        $stmt->bindParam(':refresh_token', $testToken);
        $currentTime = time();
        $stmt->bindParam(':token_expires', $currentTime);
        $stmt->bindParam(':access_token2', $testToken);
        $stmt->bindParam(':refresh_token2', $testToken);
        $stmt->bindParam(':token_expires2', $currentTime);
        
        $success = $stmt->execute();
        
        // Retrieve the token back
        $stmt = $conn->prepare("SELECT access_token FROM discord_users WHERE discord_id = 'TEST_USER'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo '<tr>';
        echo '<td>Database Token Storage</td>';
        
        if ($result && isset($result['access_token']) && strlen($result['access_token']) === strlen($testToken)) {
            echo '<td><span class="check pass">✓</span></td>';
            echo '<td>Token stored and retrieved correctly</td>';
        } else {
            echo '<td><span class="check fail">✗</span></td>';
            echo '<td>Token not stored correctly or truncated</td>';
        }
        echo '</tr>';
        
        // Clean up test data
        $stmt = $conn->prepare("DELETE FROM discord_users WHERE discord_id = 'TEST_USER'");
        $stmt->execute();
        
    } catch (PDOException $e) {
        echo '<tr>';
        echo '<td>Database Token Storage</td>';
        echo '<td><span class="check fail">✗</span></td>';
        echo '<td>Error: ' . $e->getMessage() . '</td>';
        echo '</tr>';
    }
}

// Test 3: Current Token Check
if (isset($_SESSION['discord_access_token'])) {
    echo '<tr>';
    echo '<td>Current Session Token</td>';
    
    if (strlen($_SESSION['discord_access_token']) > 50) {
        echo '<td><span class="check pass">✓</span></td>';
        echo '<td>Length: ' . strlen($_SESSION['discord_access_token']) . ' characters</td>';
    } else {
        echo '<td><span class="check fail">✗</span></td>';
        echo '<td>Token too short! Length: ' . strlen($_SESSION['discord_access_token']) . ' characters (expected 100+)</td>';
    }
    echo '</tr>';
    
    // Token Structure Analysis
    $token = $_SESSION['discord_access_token'];
    $parts = explode('.', $token);
    
    echo '<tr>';
    echo '<td>Token Structure</td>';
    
    if (count($parts) == 3) {
        echo '<td><span class="check pass">✓</span></td>';
        echo '<td>Token has JWT structure (header.payload.signature)</td>';
    } else {
        echo '<td><span class="check warn">!</span></td>';
        echo '<td>Non-standard token format: ' . count($parts) . ' parts found (JWT has 3)</td>';
    }
    echo '</tr>';
    
    // Character set check
    echo '<tr>';
    echo '<td>Character Set</td>';
    
    if (preg_match('/^[A-Za-z0-9._-]+$/', $token)) {
        echo '<td><span class="check pass">✓</span></td>';
        echo '<td>Valid OAuth2 character set</td>';
    } else {
        echo '<td><span class="check warn">!</span></td>';
        echo '<td>Unusual characters in token</td>';
    }
    echo '</tr>';
    
    // Token in database
    if ($db_status == 'pass') {
        try {
            $stmt = $conn->prepare("SELECT * FROM discord_users WHERE discord_id = :discord_id LIMIT 1");
            $discord_id = $_SESSION['discord_user']['id'] ?? '';
            $stmt->bindParam(':discord_id', $discord_id);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo '<tr>';
            echo '<td>Database Token Match</td>';
            
            if ($user && isset($user['access_token'])) {
                if ($user['access_token'] === $_SESSION['discord_access_token']) {
                    echo '<td><span class="check pass">✓</span></td>';
                    echo '<td>Session token matches database token</td>';
                } else {
                    echo '<td><span class="check fail">✗</span></td>';
                    echo '<td>Session token differs from database token</td>';
                }
            } else {
                echo '<td><span class="check warn">!</span></td>';
                echo '<td>No matching user found in database</td>';
            }
            echo '</tr>';
            
        } catch (PDOException $e) {
            echo '<tr>';
            echo '<td>Database Token Match</td>';
            echo '<td><span class="check fail">✗</span></td>';
            echo '<td>Error: ' . $e->getMessage() . '</td>';
            echo '</tr>';
        }
    }
} else {
    echo '<tr>';
    echo '<td>Current Session Token</td>';
    echo '<td><span class="check warn">!</span></td>';
    echo '<td>No Discord token in session</td>';
    echo '</tr>';
}

echo '</table>';

echo '</div>'; // End token panel

// TAB 3: API TESTS
echo '<div class="tab-panel" id="api-panel">';
echo "<h2>API Tests</h2>";

// Network & DNS Test
echo "<h3>Network Connectivity</h3>";

$discord_endpoints = [
    'Discord API' => 'discord.com',
    'CDN' => 'cdn.discordapp.com'
];

echo '<table>';
echo '<tr><th>Endpoint</th><th>DNS Lookup</th><th>Connection</th><th>HTTPS</th></tr>';

foreach ($discord_endpoints as $name => $host) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($name) . '</td>';
    
    // DNS resolution
    $ip = gethostbyname($host);
    if ($ip != $host) {
        echo '<td><span class="check pass">✓</span> ' . htmlspecialchars($ip) . '</td>';
    } else {
        echo '<td><span class="check fail">✗</span> Failed</td>';
    }
    
    // Connection test
    $conn = @fsockopen($host, 443, $errno, $errstr, 5);
    if ($conn) {
        echo '<td><span class="check pass">✓</span></td>';
        fclose($conn);
    } else {
        echo '<td><span class="check fail">✗</span></td>';
    }
    
    // HTTPS request test
    $ch = curl_init("https://" . $host);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $result = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpcode >= 200 && $httpcode < 400) {
        echo '<td><span class="check pass">✓</span> HTTP ' . $httpcode . '</td>';
    } else {
        echo '<td><span class="check fail">✗</span> HTTP ' . $httpcode . '</td>';
    }
    
    echo '</tr>';
}

echo '</table>';

// CURL Configuration
echo "<h3>CURL Configuration</h3>";

if (function_exists('curl_version')) {
    $curl_info = curl_version();
    
    echo '<table>';
    echo '<tr><th>Component</th><th>Status</th><th>Details</th></tr>';
    
    echo '<tr>';
    echo '<td>CURL Version</td>';
    echo '<td><span class="check pass">✓</span></td>';
    echo '<td>' . htmlspecialchars($curl_info['version']) . '</td>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<td>SSL Version</td>';
    echo '<td><span class="check pass">✓</span></td>';
    echo '<td>' . htmlspecialchars($curl_info['ssl_version']) . '</td>';
    echo '</tr>';
    
    // Check if CURL allows redirects
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://httpbin.org/redirect-to?url=https://httpbin.org/get");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $result = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirect_count = curl_getinfo($ch, CURLINFO_REDIRECT_COUNT);
    curl_close($ch);
    
    echo '<tr>';
    echo '<td>Redirects Support</td>';
    
    if ($httpcode == 200 && $redirect_count > 0) {
        echo '<td><span class="check pass">✓</span></td>';
        echo '<td>CURL correctly follows redirects</td>';
    } else {
        echo '<td><span class="check fail">✗</span></td>';
        echo '<td>CURL does not follow redirects</td>';
    }
    echo '</tr>';
    
    echo '</table>';
} else {
    echo "<p class='error'>CURL is not available on this server</p>";
}

// Discord API Request Test
echo "<h3>Discord API Request</h3>";

if (isset($_SESSION['discord_access_token'])) {
    echo "<p>Testing API request with current token:</p>";
    
    if (function_exists('discord_api_request')) {
        // Test with a simple request to the Discord API
        $response = discord_api_request('/users/@me', 'GET', [], $_SESSION['discord_access_token']);
        
        echo '<table>';
        echo '<tr><th>Field</th><th>Value</th></tr>';
        
        if (is_array($response)) {
            if (isset($response['id'])) {
                echo '<tr><td>Status</td><td><span class="check pass">✓</span> Success</td></tr>';
                echo '<tr><td>User ID</td><td>' . htmlspecialchars($response['id']) . '</td></tr>';
                echo '<tr><td>Username</td><td>' . htmlspecialchars($response['username']) . '</td></tr>';
                if (isset($response['avatar'])) {
                    echo '<tr><td>Avatar</td><td>' . htmlspecialchars($response['avatar']) . '</td></tr>';
                }
            } else if (isset($response['message'])) {
                echo '<tr><td>Status</td><td><span class="check fail">✗</span> Error</td></tr>';
                echo '<tr><td>Message</td><td>' . htmlspecialchars($response['message']) . '</td></tr>';
                if (isset($response['code'])) {
                    echo '<tr><td>Code</td><td>' . htmlspecialchars($response['code']) . '</td></tr>';
                }
            } else {
                echo '<tr><td>Status</td><td><span class="check warn">!</span> Unknown</td></tr>';
                echo '<tr><td>Response</td><td><pre>' . htmlspecialchars(json_encode($response, JSON_PRETTY_PRINT)) . '</pre></td></tr>';
            }
        } else {
            echo '<tr><td>Status</td><td><span class="check fail">✗</span> Invalid Response</td></tr>';
        }
        
        echo '</table>';
    } else {
        echo "<p class='error'>discord_api_request function not found. Cannot test API.</p>";
    }
    
// Token Refresh Test
        if (isset($_SESSION['discord_refresh_token'])) {
            echo "<h3>Token Refresh Test</h3>";
            echo "<p>You can test token refresh by clicking the button below:</p>";
            
            echo "<form method='post' action=''>";
            echo "<input type='hidden' name='action' value='refresh_token'>";
            echo "<button type='submit' class='btn success'>Test Token Refresh</button>";
            echo "</form>";
            
            // Process token refresh if requested
            if (isset($_POST['action']) && $_POST['action'] === 'refresh_token') {
                echo "<h4>Refresh Token Results:</h4>";
                
                if (defined('DISCORD_CLIENT_ID') && defined('DISCORD_CLIENT_SECRET') && defined('DISCORD_API_URL')) {
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
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/x-www-form-urlencoded'
                    ]);
                    
                    $response = curl_exec($ch);
                    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    echo '<table>';
                    echo '<tr><th>Field</th><th>Value</th></tr>';
                    echo '<tr><td>HTTP Status</td><td>' . $http_code . '</td></tr>';
                    
                    if ($response) {
                        $json = json_decode($response, true);
                        if ($json) {
                            if (isset($json['access_token'])) {
                                echo '<tr><td>Status</td><td><span class="check pass">✓</span> Success</td></tr>';
                                echo '<tr><td>Access Token</td><td>' . substr($json['access_token'], 0, 10) . '...</td></tr>';
                                echo '<tr><td>Token Length</td><td>' . strlen($json['access_token']) . ' characters</td></tr>';
                                if (isset($json['expires_in'])) {
                                    echo '<tr><td>Expires In</td><td>' . $json['expires_in'] . ' seconds</td></tr>';
                                }
                                
                                // Update session tokens
                                $_SESSION['discord_access_token'] = $json['access_token'];
                                if (isset($json['refresh_token'])) {
                                    $_SESSION['discord_refresh_token'] = $json['refresh_token'];
                                }
                                if (isset($json['expires_in'])) {
                                    $_SESSION['discord_token_expires'] = time() + $json['expires_in'];
                                }
                                
                                echo '<tr><td colspan="2"><div class="success">Token refreshed and updated in session!</div></td></tr>';
                            } else if (isset($json['error'])) {
                                echo '<tr><td>Status</td><td><span class="check fail">✗</span> Error</td></tr>';
                                echo '<tr><td>Error</td><td>' . htmlspecialchars($json['error']) . '</td></tr>';
                                if (isset($json['error_description'])) {
                                    echo '<tr><td>Description</td><td>' . htmlspecialchars($json['error_description']) . '</td></tr>';
                                }
                            } else {
                                echo '<tr><td>Status</td><td><span class="check warn">!</span> Unknown</td></tr>';
                                echo '<tr><td>Response</td><td><pre>' . htmlspecialchars(json_encode($json, JSON_PRETTY_PRINT)) . '</pre></td></tr>';
                            }
                        } else {
                            echo '<tr><td>Status</td><td><span class="check fail">✗</span> Invalid JSON</td></tr>';
                            echo '<tr><td>Raw Response</td><td>' . htmlspecialchars(substr($response, 0, 100)) . '...</td></tr>';
                        }
                    } else {
                        echo '<tr><td>Status</td><td><span class="check fail">✗</span> No Response</td></tr>';
                    }
                    
                    echo '</table>';
                } else {
                    echo "<p class='error'>Discord credentials not properly defined</p>";
                }
            }
        }
    } else {
        echo "<p>No Discord access token found in session. Please log in first.</p>";
    }

echo '</div>'; // End API panel

// TAB 4: ACTIONS
echo '<div class="tab-panel" id="action-panel">';
echo "<h2>Actions</h2>";

echo "<p>The following actions will help you test and troubleshoot your Discord integration:</p>";

echo '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">';

// Discord Login
echo '<div class="section" style="margin-bottom: 0;">';
echo "<h3>Discord Authentication</h3>";
if (file_exists('discord/discord-login.php')) {
    echo "<p>Test the Discord OAuth2 flow:</p>";
    echo "<a href='discord/discord-login.php' class='btn'>Login with Discord</a>";
    
    if (isset($_SESSION['discord_access_token'])) {
        echo "<p>Clear your current Discord session:</p>";
        echo "<a href='discord/discord-logout.php' class='btn danger'>Logout from Discord</a>";
    }
} else {
    echo "<p class='error'>discord-login.php not found. Login test unavailable.</p>";
}
echo '</div>';

// Database Actions
if ($db_status == 'pass') {
    echo '<div class="section" style="margin-bottom: 0;">';
    echo "<h3>Database Actions</h3>";
    
    echo "<form method='post' action=''>";
    echo "<input type='hidden' name='action' value='fix_token_columns'>";
    echo "<p>Fix token column sizes in database:</p>";
    echo "<button type='submit' class='btn'>Fix Token Columns</button>";
    echo "</form>";
    
    if (isset($_POST['action']) && $_POST['action'] === 'fix_token_columns') {
        try {
            $conn->exec("ALTER TABLE discord_users 
                MODIFY COLUMN access_token VARCHAR(2000) NOT NULL,
                MODIFY COLUMN refresh_token VARCHAR(2000) NOT NULL");
            
            echo "<p class='success'>Token columns updated successfully!</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>Error updating token columns: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<form method='post' action='' style='margin-top: 15px;'>";
    echo "<input type='hidden' name='action' value='check_tokens'>";
    echo "<p>Check token storage in database:</p>";
    echo "<button type='submit' class='btn'>Check Tokens</button>";
    echo "</form>";
    
    if (isset($_POST['action']) && $_POST['action'] === 'check_tokens') {
        try {
            $stmt = $conn->prepare("SELECT discord_id, username, LENGTH(access_token) AS token_length, 
                                   token_expires, last_login FROM discord_users ORDER BY last_login DESC LIMIT 10");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($users) > 0) {
                echo "<table style='margin-top: 15px;'>";
                echo "<tr><th>User</th><th>Token Length</th><th>Expires</th><th>Last Login</th></tr>";
                
                foreach ($users as $user) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($user['username']) . " (" . htmlspecialchars($user['discord_id']) . ")</td>";
                    
                    if ($user['token_length'] < 50) {
                        echo "<td><span class='check fail'>✗</span> " . $user['token_length'] . "</td>";
                    } else {
                        echo "<td><span class='check pass'>✓</span> " . $user['token_length'] . "</td>";
                    }
                    
                    echo "<td>" . date('Y-m-d H:i:s', $user['token_expires']) . "</td>";
                    echo "<td>" . htmlspecialchars($user['last_login']) . "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            } else {
                echo "<p>No users found in database</p>";
            }
        } catch (PDOException $e) {
            echo "<p class='error'>Error checking tokens: " . $e->getMessage() . "</p>";
        }
    }
    echo '</div>';
}

// Discord Config Info
echo '<div class="section" style="margin-bottom: 0;">';
echo "<h3>Discord Configuration</h3>";

if (file_exists('discord/discord-config.php')) {
    require_once 'discord/discord-config.php';
    
    echo "<p>Discord configuration details:</p>";
    
    echo '<table>';
    echo '<tr><th>Setting</th><th>Value</th></tr>';
    
    if (defined('DISCORD_CLIENT_ID')) {
        echo '<tr><td>Client ID</td><td>' . substr(DISCORD_CLIENT_ID, 0, 4) . '...' . substr(DISCORD_CLIENT_ID, -4) . '</td></tr>';
    } else {
        echo '<tr><td>Client ID</td><td><span class="check fail">✗</span> Not defined</td></tr>';
    }
    
    if (defined('DISCORD_CLIENT_SECRET')) {
        echo '<tr><td>Client Secret</td><td>' . substr(DISCORD_CLIENT_SECRET, 0, 2) . '...' . substr(DISCORD_CLIENT_SECRET, -2) . '</td></tr>';
    } else {
        echo '<tr><td>Client Secret</td><td><span class="check fail">✗</span> Not defined</td></tr>';
    }
    
    if (defined('DISCORD_REDIRECT_URI')) {
        echo '<tr><td>Redirect URI</td><td>' . htmlspecialchars(DISCORD_REDIRECT_URI) . '</td></tr>';
    } else {
        echo '<tr><td>Redirect URI</td><td><span class="check fail">✗</span> Not defined</td></tr>';
    }
    
    if (defined('DISCORD_API_URL')) {
        echo '<tr><td>API URL</td><td>' . htmlspecialchars(DISCORD_API_URL) . '</td></tr>';
    } else {
        echo '<tr><td>API URL</td><td><span class="check fail">✗</span> Not defined</td></tr>';
    }
    
    echo '</table>';
} else {
    echo "<p class='error'>discord-config.php not found.</p>";
}
echo '</div>';

echo '</div>'; // End grid of action sections

echo '</div>'; // End action panel

echo '</div>'; // End tab content
echo '</div>'; // End tab container

// RECOMMENDATIONS
echo '<div class="section">';
echo "<h2>Recommendations</h2>";

echo "<ul>";

// Database recommendations
if (isset($table_structure)) {
    $token_fields_ok = true;
    foreach ($table_structure as $column) {
        if (($column['Field'] === 'access_token' || $column['Field'] === 'refresh_token') &&
            strpos(strtolower($column['Type']), 'varchar(2000)') === false) {
            $token_fields_ok = false;
        }
    }
    
    if (!$token_fields_ok) {
        echo "<li class='error'><strong>Critical:</strong> Update your database token fields: <pre>ALTER TABLE discord_users 
        MODIFY COLUMN access_token VARCHAR(2000) NOT NULL,
        MODIFY COLUMN refresh_token VARCHAR(2000) NOT NULL;</pre></li>";
    }
}

// Token status recommendations
if (isset($_SESSION['discord_access_token']) && strlen($_SESSION['discord_access_token']) < 50) {
    echo "<li class='error'><strong>Critical:</strong> Your current token appears to be truncated. Try logging out and logging back in after fixing database columns.</li>";
}

// Check discord-callback.php
$callback_path = 'discord/discord-callback.php';
if (file_exists($callback_path)) {
    $callback_content = file_get_contents($callback_path);
    if (strpos($callback_content, 'COMPLETE TOKEN RESPONSE') === false) {
        echo "<li class='warning'><strong>Recommended:</strong> Update your discord-callback.php to include better token debugging.</li>";
    }
}

// Check config.php
if (!$config_status == 'pass') {
    echo "<li class='error'><strong>Critical:</strong> Fix your configuration file at $config_path.</li>";
} else if (isset($result['discord']) && (!isset($result['discord']['client_id']) || !isset($result['discord']['client_secret']))) {
    echo "<li class='error'><strong>Critical:</strong> Your Discord configuration is missing required credentials.</li>";
}

echo "</ul>";

echo '</div>'; // End recommendations section

echo '</div>
<script>
// Tab switching
document.addEventListener("DOMContentLoaded", function() {
    const tabButtons = document.querySelectorAll(".tab-button");
    
    tabButtons.forEach(button => {
        button.addEventListener("click", function() {
            // Remove active class from all buttons and panels
            document.querySelectorAll(".tab-button").forEach(btn => btn.classList.remove("active"));
            document.querySelectorAll(".tab-panel").forEach(panel => panel.classList.remove("active"));
            
            // Add active class to clicked button
            this.classList.add("active");
            
            // Show corresponding panel
            const tabId = this.getAttribute("data-tab");
            document.getElementById(tabId + "-panel").classList.add("active");
        });
    });
});
</script>
</body>
</html>';
?>
