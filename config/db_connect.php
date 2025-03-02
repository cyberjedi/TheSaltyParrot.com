<?php
// Get configuration from private file outside web root
try {
    $config = require_once($_SERVER['DOCUMENT_ROOT'] . '/../../private/db_config.php');

    try {
        $conn = new PDO("mysql:host={$config['host']};dbname={$config['dbname']}", 
                    $config['username'], $config['password']);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode([
            "status" => "error",
            "message" => "Database Connection Error: " . $e->getMessage()
        ]);
        die();
    }
} catch(Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        "status" => "error",
        "message" => "Config Error: " . $e->getMessage()
    ]);
    die();
}
?>
