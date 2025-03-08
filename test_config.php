<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Configuration & Token Test</h1>";

// PART 1: Configuration File Test
echo "<h2>Configuration File Test</h2>";

echo "Checking configuration file directly:<br>";
$path = '/home/theshfmb/private/secure_variables.php';
echo "Looking for file at: " . $path . "<br>";
echo "File exists: " . (file_exists($path) ? "YES" : "NO") . "<br>";

if (file_exists($path)) {
    echo "Attempting to load it:<br>";
    try {
        // Use a temp variable to see what's returned
        $result = require($path);
        echo "Type returned: " . gettype($result) . "<br>";
        
        if (is_array($result)) {
            echo "Config is an array with keys: " . implode(", ", array_keys($result)) . "<br>";
        } else {
            echo "Config is NOT an array, it's a: " . gettype($result) . "<br>";
            echo "Value: ";
            var_dump($result);
        }
    } catch (Exception $e) {
        echo "Error loading file: " . $e->getMessage();
    }
}

echo "<hr>Checking file contents:<br>";
$contents = file_get_contents($path);
echo "File size: " . strlen($contents) . " bytes<br>";
echo "Contains userStyle tag: " . (strpos($contents, '<userStyle>Normal</userStyle>') !== false ? "YES" : "NO") . "<br>";

// Check for common syntax issues
echo "Last character: '" . substr($contents, -1) . "' (ASCII: " . ord(substr($contents, -1)) . ")<br>";
echo "Ends with ?>: " . (substr(trim($contents), -2) == '?>' ? "YES" : "NO") . "<br>";

// Look for other potential issues
echo "<hr>File contents preview (first 200 chars):<br>";
echo htmlspecialchars(substr($contents, 0, 200)) . "...<br>";

echo "<hr>File contents preview (last 200 chars):<br>";
echo htmlspecialchars(substr($contents, -200)) . "<br>";

// Check if file has the correct structure
echo "<hr>Checking file structure:<br>";
$lines = file($path);
$first_line = trim($lines[0]);
$last_line = trim($lines[count($lines)-1]);

echo "First line: " . htmlspecialchars($first_line) . "<br>";
echo "Last line: " . htmlspecialchars($last_line) . "<br>";
echo "First line is '<?php': " . ($first_line == '<?php' ? "YES" : "NO") . "<br>";
echo "Last line is '];': " . ($last_line == '];' ? "YES" : "NO") . "<br>";

// PART 2: Discord Token Test
echo "<hr><h2>Discord Token Tests</h2>";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Test 1: Session storage capacity
echo "<h3>Test 1: Session Storage Test</h3>";
$testToken = str_repeat("ABCDEFGHIJKLMNOPQRSTUVWXYZ", 10); // 260 characters
$_SESSION['test_token'] = $testToken;

echo "Original token length: " . strlen($testToken) . "<br>";
echo "Stored token length: " . strlen($_SESSION['test_token']) . "<br>";

if (strlen($_SESSION['test_token']) === strlen($testToken)) {
    echo "<p style='color:green'>Session storage test PASSED ✅</p>";
} else {
    echo "<p style='color:red'>Session storage test FAILED ❌</p>";
}

// Test 2: Database storage test
echo "<h3>Test 2: Database Storage Test</h3>";

try {
    require_once 'config/db_connect.php';
    
    // Check database connection
    echo "Database connection: ";
    if (isset($conn) && $conn instanceof PDO) {
        echo "<span style='color:green'>Connected ✅</span><br>";
    } else {
        echo "<span style='color:red'>Failed ❌</span><br>";
        throw new Exception("Database connection failed");
    }
    
    // Check database structure
    echo "Checking discord_users table structure:<br>";
    $stmt = $conn->prepare("DESCRIBE discord_users");
    $stmt->execute();
    $table_structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($table_structure as $column) {
        if ($column['Field'] === 'access_token' || $column['Field'] === 'refresh_token') {
            echo "Column '{$column['Field']}' type: {$column['Type']}<br>";
        }
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
        echo "Test insertion: <span style='color:green'>Success ✅</span><br>";
    } else {
        echo "Test insertion: <span style='color:red'>Failed ❌</span><br>";
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
            echo "<p style='color:green'>Database storage test PASSED ✅</p>";
        } else {
            echo "<p style='color:red'>Database storage test FAILED ❌</p>";
            echo "First 20 chars of original: " . substr($testToken, 0, 20) . "<br>";
            echo "First 20 chars of retrieved: " . substr($retrievedToken, 0, 20) . "<br>";
        }
    } else {
        echo "<p style='color:red'>Couldn't retrieve test token ❌</p>";
    }
    
    // Test existing token in database
    echo "<h3>Test 3: Current Database Token</h3>";
    $stmt = $conn->prepare("SELECT * FROM discord_users WHERE discord_id != 'TEST_USER' LIMIT 1");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "Found user: " . $user['username'] . "<br>";
        echo "Access token length: " . strlen($user['access_token']) . "<br>";
        echo "First 10 chars: " . substr($user['access_token'], 0, 10) . "...<br>";
        echo "Refresh token length: " . strlen($user['refresh_token']) . "<br>";
    } else {
        echo "<p>No users found in database</p>";
    }
    
    // Clean up test data
    $stmt = $conn->prepare("DELETE FROM discord_users WHERE discord_id = 'TEST_USER'");
    $stmt->execute();
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . " ❌</p>";
}

// Test 4: Session token
echo "<h3>Test 4: Current Session Token</h3>";
if (isset($_SESSION['discord_access_token'])) {
    echo "Current session token length: " . strlen($_SESSION['discord_access_token']) . "<br>";
    echo "First 10 chars: " . substr($_SESSION['discord_access_token'], 0, 10) . "...<br>";
} else {
    echo "<p>No Discord token in session</p>";
}

// Test Discord login
echo "<hr><h3>Discord Login</h3>";
echo "<p>You can test Discord login by clicking below:</p>";

// Check if discord-config.php exists
if (file_exists('discord/discord-config.php')) {
    echo "<a href='discord/discord-login.php' style='padding: 10px 15px; background-color: #5865F2; color: white; text-decoration: none; border-radius: 5px;'>Login with Discord</a>";
} else {
    echo "<p style='color:red'>discord-config.php not found. Login test unavailable.</p>";
}

// Added helper function to print table structure
function printTableStructure($tableName, $conn) {
    echo "<h4>Table Structure: $tableName</h4>";
    $stmt = $conn->prepare("DESCRIBE $tableName");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}
?>
