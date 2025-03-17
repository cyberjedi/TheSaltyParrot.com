<?php
// Simple diagnostic page to check PHP and database status
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to check if a file exists and is readable
function check_file($path) {
    if (file_exists($path)) {
        echo "<span style='color:green'>✓</span> File exists: " . htmlspecialchars($path) . "<br>";
        if (is_readable($path)) {
            echo "<span style='color:green'>✓</span> File is readable<br>";
        } else {
            echo "<span style='color:red'>✗</span> File is NOT readable<br>";
        }
    } else {
        echo "<span style='color:red'>✗</span> File does NOT exist: " . htmlspecialchars($path) . "<br>";
    }
    echo "<hr>";
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>The Salty Parrot - System Check</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        h1, h2 { color: #333; }
        pre { background: #f4f4f4; padding: 10px; overflow-x: auto; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <h1>The Salty Parrot - System Check</h1>
    
    <h2>PHP Information</h2>
    <p>PHP Version: <?php echo phpversion(); ?></p>
    
    <h2>Critical File Check</h2>
    <?php
    check_file(__DIR__ . '/discord/discord-config.php');
    check_file(__DIR__ . '/config/db_connect.php');
    check_file(__DIR__ . '/discord/discord_service.php');
    check_file(__DIR__ . '/discord/webhook_service.php');
    ?>
    
    <h2>Database Connection Test</h2>
    <?php
    try {
        require_once __DIR__ . '/config/db_connect.php';
        
        global $conn;
        if (isset($conn)) {
            echo "<p class='success'>Database connection exists</p>";
            
            // Try a simple query
            $stmt = $conn->query("SELECT 1");
            if ($stmt) {
                echo "<p class='success'>Database query successful</p>";
            } else {
                echo "<p class='error'>Database query failed</p>";
            }
        } else {
            echo "<p class='error'>No database connection available</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    ?>
    
    <h2>Session Information</h2>
    <?php if (!empty($_SESSION)): ?>
    <pre>
    <?php 
    // Make a safe copy of the session
    $safe_session = $_SESSION;
    
    // Remove sensitive data
    if (isset($safe_session['discord_access_token'])) {
        $safe_session['discord_access_token'] = '[REDACTED]';
    }
    if (isset($safe_session['discord_refresh_token'])) {
        $safe_session['discord_refresh_token'] = '[REDACTED]';
    }
    
    print_r($safe_session); 
    ?>
    </pre>
    <?php else: ?>
    <p>No session data available</p>
    <?php endif; ?>
    
    <h2>Discord Authentication Check</h2>
    <?php
    try {
        if (file_exists(__DIR__ . '/discord/discord_service.php')) {
            require_once __DIR__ . '/discord/discord_service.php';
            
            if (function_exists('is_discord_authenticated')) {
                $authenticated = is_discord_authenticated();
                echo $authenticated 
                    ? "<p class='success'>User is authenticated with Discord</p>" 
                    : "<p class='error'>User is NOT authenticated with Discord</p>";
                
                if ($authenticated && function_exists('get_discord_user')) {
                    $user = get_discord_user();
                    if ($user) {
                        echo "<p>Logged in as: " . htmlspecialchars($user['username']) . "</p>";
                    }
                }
            } else {
                echo "<p class='error'>Discord authentication function not found</p>";
            }
        } else {
            echo "<p class='error'>Discord service file not found</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>Discord authentication error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    ?>
    
    <h2>Error Log Check</h2>
    <?php
    $error_log_path = ini_get('error_log');
    if ($error_log_path) {
        echo "<p>Error log path: " . htmlspecialchars($error_log_path) . "</p>";
        if (file_exists($error_log_path) && is_readable($error_log_path)) {
            echo "<p class='success'>Error log exists and is readable</p>";
            
            // Try to get the last few lines of the error log
            $log_content = shell_exec('tail -n 20 ' . escapeshellarg($error_log_path) . ' 2>&1');
            if ($log_content) {
                echo "<pre>" . htmlspecialchars($log_content) . "</pre>";
            } else {
                echo "<p>Could not read error log content</p>";
            }
        } else {
            echo "<p class='error'>Error log is not accessible</p>";
        }
    } else {
        echo "<p>Error log path not set in PHP configuration</p>";
    }
    ?>
</body>
</html>