<?php
/**
 * API to update user profile photo
 * Ensures compatibility between users.photo_url and character_sheets.image_path
 */

// Start the session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set headers for JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Get user ID from session
$userId = $_SESSION['user_id'];

// Decode JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Check if photoUrl was provided
if (!isset($data['photoUrl'])) {
    echo json_encode(['success' => false, 'message' => 'No photo URL provided']);
    exit;
}

$photoUrl = $data['photoUrl'];

// Include database connection
require_once '../config/database.php';

try {
    // Update the user's photo URL in the database
    $stmt = $pdo->prepare("UPDATE users SET photoURL = :photoUrl WHERE id = :userId");
    $stmt->bindParam(':photoUrl', $photoUrl, PDO::PARAM_STR);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $result = $stmt->execute();

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update profile photo']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} 