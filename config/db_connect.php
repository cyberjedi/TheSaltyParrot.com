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
        $config = null; // Initialize config array
        foreach ($possible_config_paths as $path) {
            if (file_exists($path)) {
                // Load the array returned by secure_variables.php
                $config = require($path); 
                $configLoaded = true;
                error_log("Loaded config file: " . $path);
                break;
            }
        }

        if (!$configLoaded || !is_array($config)) {
            throw new Exception('Database configuration file not found or did not return an array. Checked paths: ' . implode(', ', $possible_config_paths));
        }
        
        // Extract DB credentials from the config array
        $db_host = $config['DB_HOST'] ?? 'localhost';
        $db_port = $config['DB_PORT'] ?? '3306';
        $db_name = $config['DB_NAME'] ?? null;
        $db_user = $config['DB_USER'] ?? null;
        $db_pass = $config['DB_PASS'] ?? null;

        // Validate required database credentials
        if (empty($db_name) || empty($db_user)) { // Password can potentially be empty for local root
            // Log detailed error
            $missing_keys = [];
            if (empty($db_name)) $missing_keys[] = 'DB_NAME';
            if (empty($db_user)) $missing_keys[] = 'DB_USER';
            // Add DB_PASS here if you require it: if (is_null($db_pass)) $missing_keys[] = 'DB_PASS';
            
            $error_message = 'Required database configuration keys missing in secure_variables.php: ' . implode(', ', $missing_keys);
            error_log($error_message);
            throw new Exception($error_message);
        }

        // Log the database configuration being used
        error_log("Attempting DB connection: host=$db_host, port=$db_port, dbname=$db_name, user=$db_user");

        // Build DSN (Data Source Name) - Always include the port
        $dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4";

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