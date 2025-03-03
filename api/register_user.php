<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Include database connection
require_once '../config/db_connect.php';

$userId = $_POST['user_id'] ?? null;
$userEmail = $_POST['user_email'] ?? null;

if (!$userId || !$userEmail) {
    echo json_encode(['status' => 'error', 'message' => 'User ID and Email required']);
    exit;
}

try {
    // Check if user already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingUser) {
        // Create new user
        $stmt = $conn->prepare("INSERT INTO users (id, email) VALUES (:id, :email)");
        $stmt->execute([
            ':id' => $userId,
            ':email' => $userEmail
        ]);
    } else {
        // Update email if it has changed
        if ($existingUser['email'] !== $userEmail) {
            $stmt = $conn->prepare("UPDATE users SET email = :email WHERE id = :id");
            $stmt->execute([
                ':id' => $userId,
                ':email' => $userEmail
            ]);
        }
    }
    
    echo json_encode(['status' => 'success']);
} catch(PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>
