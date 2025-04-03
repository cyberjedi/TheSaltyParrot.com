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
        
        $config = null;
        foreach ($possible_config_paths as $path) {
            error_log("Checking config path: " . $path);
            if (file_exists($path)) {
                error_log("Found config file at: " . $path);
                $config = require_once($path);
                break;
            }
        }
        
        if ($config === null) {
            throw new Exception('Database configuration file not found. Checked paths: ' . implode(', ', $possible_config_paths));
        }
        
        // Log the database configuration being used (without sensitive data)
        error_log("Using database: host=" . ($config['host'] ?? 'not set') . 
                 ", dbname=" . ($config['dbname'] ?? 'not set'));
        
        // Extract DB credentials - using the format from secure_variables.php
        $db_host = $config['host'] ?? 'localhost';
        $db_name = $config['dbname'] ?? 'theshfmb_SPDB';
        $db_user = $config['username'] ?? '';
        $db_pass = $config['password'] ?? '';
        
        // Create PDO connection
        $conn = new PDO(
            "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
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