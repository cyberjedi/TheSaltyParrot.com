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
if (!isset($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Get user ID from session
$userId = $_SESSION['uid'];

// Decode JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Check if photoUrl was provided
if (!isset($data['photoUrl']) || empty($data['photoUrl'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No photo URL provided']);
    exit;
}

$photoUrl = $data['photoUrl'];

// Include database connection
require_once '../config/db_connect.php';

try {
    if (!isset($conn) || $conn === null) {
        throw new PDOException('Database connection failed in db_connect.php');
    }
    
    // Update the user's photo URL in the database
    $stmt = $conn->prepare("UPDATE users SET photo_url = :photoUrl WHERE uid = :uid");
    $stmt->bindParam(':photoUrl', $photoUrl, PDO::PARAM_STR);
    $stmt->bindParam(':uid', $userId, PDO::PARAM_STR);
    $result = $stmt->execute();

    if ($result) {
        // Update session photo URL as well
        $_SESSION['photoURL'] = $photoUrl;
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update profile photo in database']);
    }
} catch (PDOException $e) {
    error_log("Database error in update_profile_photo: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: Could not update photo']);
} catch (Exception $e) {
    error_log("General error in update_profile_photo: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred']);
} 