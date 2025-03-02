<?php
// test_db.php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test</h1>";

try {
    $config_path = $_SERVER['DOCUMENT_ROOT'] . '/../../private/db_config.php';
    echo "<p>Config path: $config_path</p>";
    echo "<p>Config file exists: " . (file_exists($config_path) ? 'Yes' : 'No') . "</p>";
    
    if (file_exists($config_path)) {
        $config = require_once($config_path);
        echo "<p>Config loaded successfully</p>";
        echo "<p>Host: {$config['host']}</p>";
        echo "<p>Database: {$config['dbname']}</p>";
        
        $conn = new PDO("mysql:host={$config['host']};dbname={$config['dbname']}", 
                        $config['username'], $config['password']);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "<p>Database connection successful!</p>";
        
        // Check if required tables exist
        $tables = ['ship_names', 'vessel_classes', 'armaments', 
                   'crew_quantities', 'crew_qualities', 'mundane_cargo', 
                   'special_cargo', 'plot_twists'];
        
        echo "<h2>Table Status:</h2>";
        echo "<ul>";
        foreach ($tables as $table) {
            $stmt = $conn->prepare("SHOW TABLES LIKE :table");
            $stmt->execute(['table' => $table]);
            $exists = ($stmt->rowCount() > 0);
            echo "<li>$table: " . ($exists ? 'Exists' : 'Missing') . "</li>";
            
            if ($exists) {
                $count_stmt = $conn->prepare("SELECT COUNT(*) FROM $table");
                $count_stmt->execute();
                $count = $count_stmt->fetchColumn();
                echo " (contains $count rows)";
            }
        }
        echo "</ul>";
    }
} catch(Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
