<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Possible config file locations with priority
$possible_config_paths = [
    $_SERVER['DOCUMENT_ROOT'] . '/../../private/secure_variables.php',
    $_SERVER['DOCUMENT_ROOT'] . '/../private/secure_variables.php',
    $_SERVER['DOCUMENT_ROOT'] . '/private/secure_variables.php',
    dirname(__FILE__) . '/../../private/secure_variables.php'
];

// Find and load the config file
$config = null;
foreach ($possible_config_paths as $path) {
    if (file_exists($path)) {
        $config = require_once($path);
        break;
    }
}

// If no config file found, throw an error
if ($config === null) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Database configuration file not found',
        'paths_checked' => $possible_config_paths
    ]);
    die();
}

try {
    $conn = new PDO("mysql:host={$config['host']};dbname={$config['dbname']}", 
                    $config['username'], $config['password']);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verify required tables exist
    $required_tables = ['ship_generator_names', 'ship_generator_vessel_classes', 'ship_generator_armaments', 
                    'ship_generator_crew_quantities', 'ship_generator_crew_qualities', 
                    'ship_generator_mundane_cargo', 'ship_generator_special_cargo', 'ship_generator_plot_twists',
                    'loot_generator_items'];
    
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
        'config_paths_tried' => $possible_config_paths,
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
