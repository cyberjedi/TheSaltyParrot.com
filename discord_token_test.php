<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start a fresh session
session_start();

// Create a deliberately long test token
$test_token = str_repeat("ABCDEFGHIJKLMNOPQRSTUVWXYZ", 10); // 260 characters

// Store it in session
$_SESSION['test_token'] = $test_token;

// Check how it was stored
echo "Original token length: " . strlen($test_token) . "<br>";
echo "Stored token length: " . strlen($_SESSION['test_token']) . "<br>";

// Now test database storage
require_once 'config/db_connect.php';

// Store in database
try {
    // First, check if the table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'token_test'");
    $stmt->execute();
    
    if($stmt->rowCount() == 0) {
        // Create test table
        $conn->exec("CREATE TABLE token_test (
            id INT AUTO_INCREMENT PRIMARY KEY,
            test_token VARCHAR(2000) NOT NULL
        )");
        echo "Created test table<br>";
    }
    
    // Clear any old data
    $conn->exec("TRUNCATE TABLE token_test");
    
    // Insert the token
    $stmt = $conn->prepare("INSERT INTO token_test (test_token) VALUES (:token)");
    $stmt->bindParam(':token', $test_token);
    $stmt->execute();
    
    // Retrieve it
    $stmt = $conn->prepare("SELECT test_token FROM token_test LIMIT 1");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Retrieved token length from DB: " . strlen($result['test_token']) . "<br>";
    
} catch(PDOException $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>Session Information:</h3>";
echo "<pre>";
print_r(session_get_cookie_params());
echo "</pre>";
?>
