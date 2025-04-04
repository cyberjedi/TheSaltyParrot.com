<?php
// Only show errors in development environment
if (getenv('ENVIRONMENT') == 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Define possible config file locations based on environment
$config_paths = [
    '/home/theshfmb/private/secure_variables.php', // Production
    dirname(__DIR__) . '/private/secure_variables.php', // Relative path
    $_SERVER['DOCUMENT_ROOT'] . '/private/secure_variables.php', // Document root
    __DIR__ . '/../private/secure_variables.php' // Local development
];

// Find the first config file that exists
$config_file = null;
foreach ($config_paths as $path) {
    if (file_exists($path)) {
        $config_file = $path;
        break;
    }
}

// Check if any configuration file was found
if (!$config_file) {
    http_response_code(500);
    exit('Configuration file not found. Please contact the administrator.');
}

// Load the configuration file
$config = require $config_file;

// Check if it's an array
if (!is_array($config)) {
    http_response_code(500);
    exit('Invalid configuration format. Please contact the administrator.');
}

try {
    // Log connection attempt (without sensitive information)
    error_log("Attempting database connection to host: {$config['host']}, database: {$config['dbname']}");
    
    // Create database connection
    $conn = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']}",
        $config['username'],
        $config['password']
    );
    
    // Set error mode
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Define constants for easy reference
    define('DB_HOST', $config['host']);
    define('DB_NAME', $config['dbname']);
    define('DB_USER', $config['username']);
    
    // Check if we're connected to the correct database
    // If not, try to use the production database name
    $result = $conn->query("SELECT DATABASE()")->fetch(PDO::FETCH_NUM);
    $current_db = $result[0];
    
    error_log("Connected to database: " . $current_db);
    
    // If not connected to the expected database, try to switch
    if ($current_db != 'theshfmb_SPDB') {
        $conn->exec("USE theshfmb_SPDB");
        error_log("Switched to database: theshfmb_SPDB");
    }
    
    // Verify we can access the characters table
    $tables = $conn->query("SHOW TABLES LIKE 'characters'")->fetchAll();
    if (count($tables) === 0) {
        error_log("ERROR: characters table not found in database " . $current_db);
    } else {
        // Check how many characters exist
        $charCount = $conn->query("SELECT COUNT(*) FROM characters")->fetchColumn();
        error_log("Found " . $charCount . " characters in database");
    }
    
} catch(PDOException $e) {
    // Log error for administrators
    error_log('Database connection error: ' . $e->getMessage());
    
    // Return a user-friendly error message
    http_response_code(500);
    exit('Database connection error. Please try again later or contact support.');
}

// No fancy error handling, no table checks, just the basics
?>
