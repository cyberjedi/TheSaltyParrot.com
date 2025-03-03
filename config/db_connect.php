<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define a single consistent path for the config file
$config_path = $_SERVER['DOCUMENT_ROOT'] . '/../../private/secure_variables.php';

// Check if the config file exists
if (!file_exists($config_path)) {
    // If not, try one level up
    $config_path = $_SERVER['DOCUMENT_ROOT'] . '/../private/secure_variables.php';
    
    // If that doesn't exist either, check in the current directory structure
    if (!file_exists($config_path)) {
        $config_path = dirname(__FILE__) . '/../../private/secure_variables.php';
        
        // If that still doesn't exist, we have a problem
        if (!file_exists($config_path)) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Database configuration file not found',
                'path_checked' => $config_path
            ]);
            die();
        }
    }
}

// Load the configuration
$config = require_once($config_path);

try {
    // Establish database connection
    $conn = new PDO("mysql:host={$config['host']};dbname={$config['dbname']}", 
                    $config['username'], $config['password']);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verify required tables exist
    $required_tables = ['ship_names', 'vessel_classes', 'armaments', 
                        'crew_quantities', 'crew_qualities', 
                        'mundane_cargo', 'special_cargo', 'plot_twists'];
    
    $missing_tables = [];
    foreach ($required_tables as $table) {
        $stmt = $conn->prepare("SHOW TABLES LIKE :table");
        $stmt->execute(['table' => $table]);
        if ($stmt->rowCount() == 0) {
            $missing_tables[] = $table;
        }
    }
    
    if (!empty($missing_tables)) {
        throw new Exception("Missing tables: " . implode(', ', $missing_tables));
    }
    
} catch(PDOException $e) {
    // Detailed error handling
    $error_details = [
        'message' => $e->getMessage(),
        'config_path' => $config_path,
        'config' => array_merge($config, ['password' => '***REDACTED***'])
    ];
    
    // JSON encode to ensure no sensitive info is exposed
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed',
        'details' => $error_details
    ]);
    die();
}
?>
