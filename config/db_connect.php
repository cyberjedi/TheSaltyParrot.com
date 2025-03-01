<?php
// Get configuration from private file outside web root
$config = require_once($_SERVER['DOCUMENT_ROOT'] . '/../../private/db_config.php');

try {
    $conn = new PDO("mysql:host={$config['host']};dbname={$config['dbname']}", 
                $config['username'], $config['password']);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Connection Error: " . $e->getMessage()
    ]);
    die();
}
?>
