<?php
/**
 * Update Sheet Photo API
 * 
 * Updates a character sheet with an existing photo
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
if (!$input || !isset($input['sheet_id']) || !isset($input['image_path'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$user_id = $_SESSION['uid'];
$sheet_id = (int)$input['sheet_id'];
$image_path = $input['image_path'];

try {
    // Include database connection
    require_once '../../config/db_connect.php';
    
    if (!isset($conn) || $conn === null) {
        throw new Exception('Database connection failed');
    }
    
    // Verify sheet belongs to user
    $stmt = $conn->prepare("SELECT id FROM character_sheets WHERE id = ? AND user_id = ?");
    $stmt->execute([$sheet_id, $user_id]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Sheet not found or access denied']);
        exit;
    }
    
    // Update the sheet with the new image path
    $stmt = $conn->prepare("UPDATE character_sheets SET image_path = ? WHERE id = ?");
    $stmt->execute([$image_path, $sheet_id]);
    
    // Return success
    echo json_encode([
        'success' => true,
        'message' => 'Sheet photo updated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to update sheet photo: ' . $e->getMessage()
    ]);
    exit;
} 