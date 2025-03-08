<?php
// db_diagnostic.php - A diagnostic tool to check database connections and tables

// Enable error reporting for diagnostics
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Database Diagnostic Tool</h1>";
echo "<p>This tool checks database connections and tables for The Salty Parrot.</p>";

// Function to display results in a readable format
function displayResult($title, $content, $is_error = false) {
    $style = $is_error ? "color: red; background-color: #ffeeee; padding: 10px; border: 1px solid red;" : "margin: 10px 0; padding: 10px; border: 1px solid #ccc; background-color: #f5f5f5;";
    echo "<div style='$style'>";
    echo "<h3>$title</h3>";
    echo "<pre>" . print_r($content, true) . "</pre>";
    echo "</div>";
}

// 1. Check if session is active
session_start();
$session_data = isset($_SESSION) ? $_SESSION : "No session data";
displayResult("SESSION Data", $session_data);

// 2. Try to include the database connection
try {
    // Try to load config directly from known locations
    $config_paths = [
        '/home/theshfmb/private/secure_variables.php', // Production
        dirname(__FILE__) . '/private/secure_variables.php', // Relative path
        __DIR__ . '/private/secure_variables.php' // Local development
    ];
    
    $config = null;
    foreach ($config_paths as $path) {
        if (file_exists($path)) {
            displayResult("Config File Found", $path);
            $config = require($path);
            break;
        }
    }
    
    if (!$config) {
        displayResult("Config Not Found", "Tried paths: " . implode(", ", $config_paths), true);
        
        // Try using the standard db_connect.php file
        if (file_exists('config/db_connect.php')) {
            displayResult("Using Standard DB Connect File", "config/db_connect.php");
            require_once 'config/db_connect.php';
            
            // Check if $conn is defined from the included file
            if (isset($conn)) {
                displayResult("Connection Object", "Connection object defined in db_connect.php");
            } else {
                displayResult("Connection Error", "Connection object not defined in db_connect.php", true);
            }
        } else {
            displayResult("DB Connect File Missing", "config/db_connect.php not found", true);
        }
    } else {
        // Config found, create connection manually
        displayResult("Config Contents (without password)", array_diff_key($config, array_flip(['password'])));
        
        try {
            // Create database connection
            $conn = new PDO(
                "mysql:host={$config['host']};dbname={$config['dbname']}",
                $config['username'],
                $config['password']
            );
            
            // Set error mode
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            displayResult("Manual Connection", "Successfully connected to database {$config['dbname']}");
        } catch(PDOException $e) {
            displayResult("Manual Connection Error", $e->getMessage(), true);
        }
    }
    
    // 3. Test database connection if we have a connection object
    if (isset($conn)) {
        try {
            // Check which database we're connected to
            $result = $conn->query("SELECT DATABASE()")->fetch(PDO::FETCH_NUM);
            displayResult("Current Database", $result[0]);
            
            // List all tables in the current database
            $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            displayResult("Tables in Database", $tables);
            
            // Check if characters table exists
            if (in_array('characters', $tables)) {
                // Show characters table structure
                $structure = $conn->query("DESCRIBE characters")->fetchAll(PDO::FETCH_ASSOC);
                displayResult("Characters Table Structure", $structure);
                
                // Count characters
                $count = $conn->query("SELECT COUNT(*) FROM characters")->fetchColumn();
                displayResult("Character Count", $count);
                
                // Show all characters
                if ($count > 0) {
                    $characters = $conn->query("SELECT * FROM characters")->fetchAll(PDO::FETCH_ASSOC);
                    displayResult("All Characters", $characters);
                } else {
                    displayResult("No Characters Found", "The characters table exists but contains no records", true);
                }
            } else {
                displayResult("Characters Table Missing", "The characters table does not exist in this database", true);
            }
            
            // Check if discord_users table exists
            if (in_array('discord_users', $tables)) {
                // Show discord_users table structure
                $structure = $conn->query("DESCRIBE discord_users")->fetchAll(PDO::FETCH_ASSOC);
                displayResult("Discord Users Table Structure", $structure);
                
                // Count discord users
                $count = $conn->query("SELECT COUNT(*) FROM discord_users")->fetchColumn();
                displayResult("Discord Users Count", $count);
                
                // Show all discord users
                if ($count > 0) {
                    $users = $conn->query("SELECT * FROM discord_users")->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Mask sensitive data like tokens
                    foreach ($users as &$user) {
                        if (isset($user['access_token'])) {
                            $user['access_token'] = substr($user['access_token'], 0, 10) . '...';
                        }
                        if (isset($user['refresh_token'])) {
                            $user['refresh_token'] = substr($user['refresh_token'], 0, 10) . '...';
                        }
                    }
                    
                    displayResult("All Discord Users", $users);
                } else {
                    displayResult("No Discord Users Found", "The discord_users table exists but contains no records", true);
                }
            } else {
                displayResult("Discord Users Table Missing", "The discord_users table does not exist in this database", true);
            }
            
            // Try directly switching to the database we know should work
            try {
                $conn->exec("USE theshfmb_SPDB");
                $current_db = $conn->query("SELECT DATABASE()")->fetch(PDO::FETCH_NUM)[0];
                displayResult("Switched Database", "Successfully switched to database: $current_db");
                
                // Check characters table again
                $chars_exist = $conn->query("SHOW TABLES LIKE 'characters'")->fetchColumn();
                if ($chars_exist) {
                    $count = $conn->query("SELECT COUNT(*) FROM characters")->fetchColumn();
                    displayResult("Characters in theshfmb_SPDB", $count);
                    
                    if ($count > 0) {
                        $characters = $conn->query("SELECT * FROM characters")->fetchAll(PDO::FETCH_ASSOC);
                        displayResult("Characters in theshfmb_SPDB", $characters);
                    }
                } else {
                    displayResult("No Characters Table in theshfmb_SPDB", "The characters table does not exist in theshfmb_SPDB", true);
                }
            } catch (PDOException $e) {
                displayResult("Database Switch Error", $e->getMessage(), true);
            }
            
        } catch(PDOException $e) {
            displayResult("Database Query Error", $e->getMessage(), true);
        }
    }
    
} catch (Exception $e) {
    displayResult("General Error", $e->getMessage(), true);
}

// 4. Check Discord authentication
$discord_authenticated = isset($_SESSION['discord_user']) && isset($_SESSION['discord_access_token']);
displayResult("Discord Authentication Status", $discord_authenticated ? "Authenticated" : "Not Authenticated");

if ($discord_authenticated) {
    $discord_user = $_SESSION['discord_user'];
    
    // Sanitize sensitive data
    if (isset($_SESSION['discord_access_token'])) {
        $token_preview = substr($_SESSION['discord_access_token'], 0, 10) . '...';
    } else {
        $token_preview = "Not available";
    }
    
    displayResult("Discord User Info", $discord_user);
    displayResult("Discord Token Preview", $token_preview);
}

echo "<hr>";
echo "<p>Diagnostic complete. If you're seeing errors, please take a screenshot and send to the developer.</p>";
?>