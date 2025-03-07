<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Use a single, hardcoded path to the configuration
$config_file = '/home/theshfmb/private/secure_variables.php';

// Check if file exists
if (!file_exists($config_file)) {
    die('Configuration file not found: ' . $config_file);
}

// Load the configuration file directly
$config = require $config_file;

// Simple check if it's an array
if (!is_array($config)) {
    die('Configuration file did not return an array');
}

try {
    // Create database connection
    $conn = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']}",
        $config['username'],
        $config['password']
    );
    
    // Set error mode
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch(PDOException $e) {
    // Simple error handling
    die('Database connection failed: ' . $e->getMessage());
}

// No fancy error handling, no table checks, just the basics
?>
