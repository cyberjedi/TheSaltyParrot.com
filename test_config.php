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
    <title>Config Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1, h2, h3 {
            color: #333;
        }
        hr {
            border: 0;
            border-top: 1px solid #ddd;
            margin: 20px 0;
        }
        .success {
            color: green;
        }
        .error {
            color: red;
        }
        .warning {
            color: orange;
        }
        .check {
            color: green;
            font-weight: bold;
        }
        .section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #5865F2;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin: 15px 0;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="container">';

echo "<h1>Configuration & Discord Token Test</h1>";

// SECTION 1: Configuration File Test
echo '<div class="section">';
echo "<h2>1. Configuration File Test</h2>";

echo "Checking configuration file directly:<br>";
$path = '/home/theshfmb/private/secure_variables.php';
echo "Looking for file at: " . $path . "<br>";
echo "File exists: " . (file_exists($path) ? "<span class='success'>YES</span>" : "<span class='error'>NO</span>") . "<br>";

if (file_exists($path)) {
    echo "Attempting to load it:<br>";
    try {
        // Use a temp variable to see what's returned
        $result = require($path);
        echo "Type returned: " . gettype($result) . "<br>";
        
        if (is_array($result)) {
            echo "Config is an array with keys: " . implode(", ", array_keys($result)) . "<br>";
            
            // Check for Discord configuration
            if (isset($result['discord'])) {
                echo "Discord configuration: <span class='success'>FOUND</span><br>";
                echo "Discord config keys: " . implode(", ", array_keys($result['discord'])) . "<br>";
            } else {
                echo "Discord configuration: <span class='error'>NOT FOUND</span><br>";
            }
        } else {
            echo "Config is NOT an array, it's a: " . gettype($result) . "<br>";
            echo "Value: ";
            var_dump($result);
        }
    } catch (Exception $e) {
        echo "<span class='error'>Error loading file: " . $e->getMessage() . "</span>";
    }
}

echo "<hr>Checking file contents:<br>";
$contents = file_get_contents($path);
echo "File size: " . strlen($contents) . " bytes<br>";
echo "Contains userStyle tag: " . (strpos($contents, '<userStyle>Normal</userStyle>') !== false ? "<span class='success'>YES</span>" : "<span class='error'>NO</span>") . "<br>";

// Check for common syntax issues
echo "Last character: '" . substr($contents, -1) . "' (ASCII: " . ord(substr($contents, -1)) . ")<br>";
echo "Ends with ?>: " . (substr(trim($contents), -2) == '?>' ? "<span class='warning'>YES</span>" : "<span class='success'>NO</span>") . "<br>";

// Look for other potential issues
echo "<hr>File contents preview (first 100 chars):<br>";
echo "<pre>" . htmlspecialchars(substr($contents, 0, 100)) . "...</pre>";

echo "<hr>File contents preview (last 100 chars):<br>";
echo "<pre>" . htmlspecialchars(substr($contents, -100)) . "</pre>";

// Check if file has the correct structure
echo "<hr>Checking file structure:<br>";
$lines = file($path);
$first_line = trim($lines[0]);
$last_line = trim($lines[count($lines)-1]);

echo "First line: <code>" . htmlspecialchars($first_line) . "</code><br>";
echo "Last line: <code>" . htmlspecialchars($last_line) . "</code><br>";
echo "First line is '<?php': " . ($first_line == '<?php' ? "<span class='success'>YES</span>" : "<span class='error'>NO</span>") . "<br>";
echo "Last line is '];': " . ($last_line == '];' ? "<span class='success'>YES</span>" : "<span class='error'>NO</span>") . "<br>";
echo '</div>';

// SECTION 2: Discord Token Test
echo '<div class="section">';
echo "<h2>2. Discord Token Tests</h2>";

// Test 1: Session storage capacity
echo "<h3>Test 1: Session Storage Test</h3>";
$testToken = str_repeat("ABCDEFGHIJKLMNOPQRSTUVWXYZ", 10); // 260 characters
$_SESSION['test_token'] = $testToken;

echo "Original token length: " . strlen($testToken) . "<br>";
echo "Stored token length: " . strlen($_SESSION['test_token']) . "<br>";

if (strlen($_SESSION['test_token']) === strlen($testToken)) {
    echo "<p class='success'>Session storage test PASSED <span class='check'>✓</span></p>";
} else {
    echo "<p class='error'>Session storage test FAILED ✗</p>";
}

// Test 2: Database storage test
echo "<h3>Test 2: Database Storage Test</h3>";

try {
    require_once 'config/db_connect.php';
    
    // Check database connection
    echo "Database connection: ";
    if (isset($conn) && $conn instanceof PDO) {
        echo "<span class='success'>Connected <span class='check'>✓</span></span><br>";
    } else {
        echo "<span class='error'>Failed ✗</span><br>";
        throw new Exception("Database connection failed");
    }
    
    // Check database structure
    echo "Checking discord_users table structure:<br>";
    try {
        $stmt = $conn->prepare("DESCRIBE discord_users");
        $stmt->execute();
        $table_structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        
        foreach ($table_structure as $column) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
            echo "</tr>";
            
            if ($column['Field'] === 'access_token' || $column['Field'] === 'refresh_token') {
                // Check if field is large enough
                if (strpos(strtolower($column['Type']), 'varchar(2000)') !== false) {
                    echo "<tr><td colspan='4' class='success'>Column '" . htmlspecialchars($column['Field']) . "' has sufficient size <span class='check'>✓</span></td></tr>";
                } else {
                    echo "<tr><td colspan='4' class='error'>Column '" . htmlspecialchars($column['Field']) . "' may not be large enough! ✗</td></tr>";
                }
            }
        }
        
        echo "</table>";
    } catch (PDOException $e) {
        echo "<p class='error'>Error checking table structure: " . $e->getMessage() . "</p>";
    }
    
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
    
    if ($success) {
        echo "Test insertion: <span class='success'>Success <span class='check'>✓</span></span><br>";
    } else {
        echo "Test insertion: <span class='error'>Failed ✗</span><br>";
        throw new Exception("Failed to insert test token");
    }
    
    // Retrieve the token back
    $stmt = $conn->prepare("SELECT access_token FROM discord_users WHERE discord_id = 'TEST_USER'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && isset($result['access_token'])) {
        $retrievedToken = $result['access_token'];
        echo "Original token length: " . strlen($testToken) . "<br>";
        echo "Retrieved token length: " . strlen($retrievedToken) . "<br>";
        
        if (strlen($retrievedToken) === strlen($testToken)) {
            echo "<p class='success'>Database storage test PASSED <span class='check'>✓</span></p>";
        } else {
            echo "<p class='error'>Database storage test FAILED ✗</p>";
            echo "First 20 chars of original: " . substr($testToken, 0, 20) . "<br>";
            echo "First 20 chars of retrieved: " . substr($retrievedToken, 0, 20) . "<br>";
        }
    } else {
        echo "<p class='error'>Couldn't retrieve test token ✗</p>";
    }
    
    // Test existing token in database
    echo "<h3>Test 3: Current Database Token</h3>";
    $stmt = $conn->prepare("SELECT * FROM discord_users WHERE discord_id != 'TEST_USER' LIMIT 1");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "Found user: " . htmlspecialchars($user['username']) . "<br>";
        echo "Access token length: " . strlen($user['access_token']) . "<br>";
        echo "First 10 chars: " . htmlspecialchars(substr($user['access_token'], 0, 10)) . "...<br>";
        echo "Refresh token length: " . strlen($user['refresh_token']) . "<br>";
        
        if (strlen($user['access_token']) < 50) {
            echo "<p class='error'>WARNING: Current token appears to be truncated or invalid! ✗</p>";
            echo "<p>Discord OAuth tokens should typically be 100+ characters long.</p>";
        }
    } else {
        echo "<p>No users found in database</p>";
    }
    
    // Clean up test data
    $stmt = $conn->prepare("DELETE FROM discord_users WHERE discord_id = 'TEST_USER'");
    $stmt->execute();
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . " ✗</p>";
}

// Test 4: Session token
echo "<h3>Test 4: Current Session Token</h3>";
if (isset($_SESSION['discord_access_token'])) {
    echo "Current session token length: " . strlen($_SESSION['discord_access_token']) . "<br>";
    echo "First 10 chars: " . htmlspecialchars(substr($_SESSION['discord_access_token'], 0, 10)) . "...<br>";
    
    if (strlen($_SESSION['discord_access_token']) < 50) {
        echo "<p class='error'>WARNING: Current session token appears to be truncated or invalid! ✗</p>";
    }
} else {
    echo "<p>No Discord token in session</p>";
}
echo '</div>';

// SECTION 3: Discord Login Test
echo '<div class="section">';
echo "<h2>3. Discord Login Test</h2>";

// Check if discord-config.php exists
if (file_exists('discord/discord-config.php')) {
    echo "<p>You can test Discord login by clicking below:</p>";
    echo "<a href='discord/discord-login.php' class='btn'>Login with Discord</a>";
    
    // Add a link to directly log out and clear tokens for testing
    echo "<p style='margin-top: 15px;'>Or clear your current Discord session:</p>";
    echo "<a href='discord/discord-logout.php' class='btn' style='background-color: #ff5252;'>Logout from Discord</a>";
} else {
    echo "<p class='error'>discord-config.php not found. Login test unavailable. ✗</p>";
}
echo '</div>';

// SECTION 4: Discord API Request Test
echo '<div class="section">';
echo "<h2>4. Discord API Request Test</h2>";

if (isset($_SESSION['discord_access_token'])) {
    echo "<p>Using current token to test Discord API...</p>";
    
    try {
        // Check if we have the discord_api_request function
        if (!function_exists('discord_api_request')) {
            // Include discord-config.php if exists
            if (file_exists('discord/discord-config.php')) {
                require_once 'discord/discord-config.php';
            }
        }
        
        if (function_exists('discord_api_request')) {
            // Test with a simple request to the Discord API
            $response = discord_api_request('/users/@me', 'GET', [], $_SESSION['discord_access_token']);
            
            echo "<h3>API Response:</h3>";
            echo "<pre>";
            if (is_array($response)) {
                if (isset($response['id'])) {
                    echo "User ID: " . htmlspecialchars($response['id']) . "\n";
                    echo "Username: " . htmlspecialchars($response['username']) . "\n";
                    echo "Response successfully received! <span class='success'>✓</span>";
                } else if (isset($response['message'])) {
                    echo "Error: " . htmlspecialchars($response['message']) . "\n";
                    echo "Code: " . (isset($response['code']) ? htmlspecialchars($response['code']) : 'Unknown') . "\n";
                    
                    if (isset($response['message']) && (
                        strpos($response['message'], 'invalid token') !== false || 
                        strpos($response['message'], '401') !== false ||
                        strpos($response['message'], 'Unauthorized') !== false
                    )) {
                        echo "\nYour token appears to be invalid. Try logging out and logging back in.";
                    }
                } else {
                    print_r($response);
                }
            } else {
                echo "No valid response received\n";
                var_dump($response);
            }
            echo "</pre>";
        } else {
            echo "<p class='error'>discord_api_request function not found. Cannot test API. ✗</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>Error testing Discord API: " . $e->getMessage() . " ✗</p>";
    }
} else {
    echo "<p>No Discord token in session. Login first to test API.</p>";
}
echo '</div>';

// SECTION 5: Recommendations
echo '<div class="section">';
echo "<h2>5. Recommendations Based on Test Results</h2>";

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
        echo "<li class='error'>Update your database token fields: <pre>ALTER TABLE discord_users 
        MODIFY COLUMN access_token VARCHAR(2000) NOT NULL,
        MODIFY COLUMN refresh_token VARCHAR(2000) NOT NULL;</pre></li>";
    }
}

// Token status recommendations
if (isset($_SESSION['discord_access_token']) && strlen($_SESSION['discord_access_token']) < 50) {
    echo "<li class='error'>Your current token appears to be truncated. Replace discord-callback.php with the updated version.</li>";
    echo "<li class='error'>After updating, log out and log back in to Discord.</li>";
}

// Final recommendation
echo "<li>Check the server logs for more information on what might be happening during the OAuth process.</li>";
echo "</ul>";

echo '</div>';

echo '</div>
</body>
</html>';
?>
