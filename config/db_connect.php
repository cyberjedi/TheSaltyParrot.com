<?php
/**
 * Database Connection for New UI
 * 
 * Handles database connection for the new UI components
 * Reuses the same connection parameters as the original system
 */

// Start the session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Only create connection if one doesn't already exist
if (!isset($conn)) {
    try {
        // Find and include the secure variables file - use same paths as original
        $possible_config_paths = [
            '/home/theshfmb/private/secure_variables.php', // Production
            dirname(__DIR__) . '/private/secure_variables.php', // Relative path
            $_SERVER['DOCUMENT_ROOT'] . '/private/secure_variables.php', // Document root
            __DIR__ . '/../private/secure_variables.php', // Local development
            $_SERVER['DOCUMENT_ROOT'] . '/../private/secure_variables.php', // Alt production
            $_SERVER['DOCUMENT_ROOT'] . '/../../private/secure_variables.php' // Another alt production
        ];
        
        // Check if config file was actually loaded (require_once returns 1 on success for define-only files)
        $configLoaded = false;
        foreach ($possible_config_paths as $path) {
            if (file_exists($path)) {
                require_once($path);
                $configLoaded = true;
                error_log("Loaded config file: " . $path);
                break;
            }
        }

        if (!$configLoaded) {
            throw new Exception('Database configuration file not found. Checked paths: ' . implode(', ', $possible_config_paths));
        }
        
        // Extract DB credentials using defined constants
        $db_host = defined('DB_HOST') ? DB_HOST : 'localhost';
        $db_port = defined('DB_PORT') ? DB_PORT : '3306'; // Default MySQL port
        $db_name = defined('DB_NAME') ? DB_NAME : ''; // No sensible default, fail if not defined
        $db_user = defined('DB_USER') ? DB_USER : '';
        $db_pass = defined('DB_PASS') ? DB_PASS : '';

        if (empty($db_name)) {
            throw new Exception('DB_NAME is not defined in secure_variables.php');
        }

        // Log the database configuration being used
        error_log("Attempting DB connection: host=$db_host, port=$db_port, dbname=$db_name, user=$db_user");

        // Build DSN (Data Source Name)
        $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
        if ($db_port !== '3306') {
            $dsn .= ";port=$db_port";
        }

        // Create PDO connection
        $conn = new PDO(
            $dsn,
            $db_user, 
            $db_pass, 
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        
        // Create a global hook to alias $conn to $conn for compatibility in some contexts
        $GLOBALS['conn'] = $conn;
        
    } catch (PDOException $e) {
        error_log('New UI DB Connection error: ' . $e->getMessage());
        $conn = null;
        throw new Exception('Database connection failed: ' . $e->getMessage());
    } catch (Exception $e) {
        error_log('New UI DB Config error: ' . $e->getMessage());
        $conn = null;
        throw new Exception('Database configuration error: ' . $e->getMessage());
    }
}
?>