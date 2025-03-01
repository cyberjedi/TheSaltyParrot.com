<?php
// Database connection parameters
$host = 'localhost';
$db_name = 'your_database_name'; // Replace with your actual database name
$username = 'your_database_username'; // Replace with your actual username
$password = 'your_database_password'; // Replace with your actual password

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(array(
        "status" => "error",
        "message" => "Connection Error: " . $e->getMessage()
    ));
    die();
}
?>
