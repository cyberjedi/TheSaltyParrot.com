<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get configuration from private file outside web root
try {
    $config_path = $_SERVER['DOCUMENT_ROOT'] . '/../../private/db_config.php';
    
    if (!file_exists($config_path)) {
        throw new Exception("Database config file not found at: $config_path");
    }
    
    $config = require_once($config_path);
    
    if (!isset($config['host']) || !isset($config['dbname']) || !isset($config['username']) || !isset($config['password'])) {
        throw new Exception("Database config file is missing required parameters.");
    }
    
    try {
        $conn = new PDO("mysql:host={$config['host']};dbname={$config['dbname']}", 
                    $config['username'], $config['password']);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
        // Format error as JSON
        header('Content-Type: application/json');
        echo json_encode([
            "status" => "error",
            "message" => "Database Connection Error: " . $e->getMessage(),
            "details" => [
                "host" => $config['host'],
                "dbname" => $config['dbname'],
                "username_length" => strlen($config['username']),
                "password_set" => !empty($config['password']),
                "server_root" => $_SERVER['DOCUMENT_ROOT']
            ]
        ]);
        die();
    }
} catch(Exception $e) {
    // Format error as JSON
    header('Content-Type: application/json');
    echo json_encode([
        "status" => "error",
        "message" => "Config Error: " . $e->getMessage(),
        "server_root" => $_SERVER['DOCUMENT_ROOT'],
        "config_path" => $config_path ?? 'Not set',
        "php_version" => PHP_VERSION
    ]);
    die();
}
?>
