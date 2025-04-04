<?php
/**
 * Delete Photo API
 * 
 * Deletes a photo and updates any character sheets that use it
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

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['path'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Photo path is required']);
    exit;
}

$user_id = $_SESSION['uid'];
$photo_path = $input['path'];

try {
    // Include database connection
    require_once '../../config/db_connect.php';
    
    if (!isset($conn) || $conn === null) {
        throw new Exception('Database connection failed');
    }
    
    // Get the filename from the path
    $filename = basename($photo_path);
    
    // Ensure the photo belongs to the user
    if (strpos($filename, $user_id . '_') !== 0) {
        throw new Exception('You do not have permission to delete this photo');
    }
    
    // Update any sheets that use this photo to use the default
    $stmt = $conn->prepare("UPDATE character_sheets SET image_path = '../assets/TSP_default_character.jpg' WHERE image_path = ? AND user_id = ?");
    $stmt->execute([$photo_path, $user_id]);
    
    // Delete the file from the filesystem
    $full_path = '../../' . str_replace('../', '', $photo_path);
    if (file_exists($full_path)) {
        if (!unlink($full_path)) {
            throw new Exception('Failed to delete photo file. It may be in use by another process.');
        }
    } else {
        // The file doesn't exist, but we'll still return success
        // since we've updated the database
        error_log("File not found for deletion: $full_path");
    }
    
    // Return success
    echo json_encode([
        'success' => true,
        'message' => 'Photo deleted successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to delete photo: ' . $e->getMessage()
    ]);
    exit;
} 