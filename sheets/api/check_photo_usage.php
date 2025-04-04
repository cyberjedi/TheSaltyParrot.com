<?php
/**
 * Check Photo Usage API
 * 
 * Checks if a photo is being used by any character sheets
 */

// Start the session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set JSON content type
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Check if photo path is provided
if (!isset($_GET['path']) || empty($_GET['path'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Photo path is required']);
    exit;
}

$user_id = $_SESSION['uid'];
$photo_path = $_GET['path'];

try {
    // Include database connection
    require_once '../../config/db_connect.php';
    
    if (!isset($conn) || $conn === null) {
        throw new Exception('Database connection failed');
    }
    
    // Get all sheets that use this photo
    $stmt = $conn->prepare("SELECT id, name FROM character_sheets WHERE image_path = ? AND user_id = ?");
    $stmt->execute([$photo_path, $user_id]);
    $sheets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return the sheets that use this photo
    echo json_encode([
        'success' => true,
        'sheets' => $sheets
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to check photo usage: ' . $e->getMessage()
    ]);
    exit;
} 