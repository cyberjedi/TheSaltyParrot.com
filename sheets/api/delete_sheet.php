<?php
/**
 * Delete Character Sheet API
 * 
 * Deletes a character sheet by ID
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

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Check if sheet ID is provided
if (!isset($data['id']) || empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Sheet ID is required']);
    exit;
}

$sheet_id = (int)$data['id'];
$user_id = $_SESSION['uid'];

try {
    // Include database connection
    require_once '../../config/db_connect.php';
    
    if (!isset($conn) || $conn === null) {
        throw new Exception('Database connection failed');
    }

    // First, check if the sheet exists and belongs to this user
    $checkStmt = $conn->prepare("SELECT id, system, image_path FROM character_sheets WHERE id = ? AND user_id = ?");
    $checkStmt->execute([$sheet_id, $user_id]);
    $sheet = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sheet) {
        http_response_code(404);
        echo json_encode(['error' => 'Sheet not found or access denied']);
        exit;
    }
    
    // Start a transaction
    $conn->beginTransaction();
    
    try {
        // Delete system-specific data first (foreign key constraints)
        if ($sheet['system'] === 'pirate_borg') {
            $deleteSystemStmt = $conn->prepare("DELETE FROM pirate_borg_sheets WHERE sheet_id = ?");
            $deleteSystemStmt->execute([$sheet_id]);
        }
        // Add elseif blocks for other systems here in the future
        
        // Delete the main sheet
        $deleteMainStmt = $conn->prepare("DELETE FROM character_sheets WHERE id = ? AND user_id = ?");
        $success = $deleteMainStmt->execute([$sheet_id, $user_id]);
        
        if (!$success) {
            throw new Exception('Failed to delete character sheet');
        }
        
        // If there's a custom image path and it's not the default image, delete the file
        $image_path = $sheet['image_path'] ?? '';
        if (!empty($image_path) && strpos($image_path, 'TSP_default_character.jpg') === false) {
            if (file_exists($image_path) && is_file($image_path)) {
                @unlink($image_path); // Attempt to delete the file, but don't throw an error if it fails
            }
        }
        
        // Commit the transaction
        $conn->commit();
        
        // Return success
        echo json_encode([
            'success' => true,
            'message' => 'Character sheet deleted successfully'
        ]);
        
    } catch (Exception $e) {
        // Rollback the transaction
        $conn->rollBack();
        throw $e;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to delete character sheet: ' . $e->getMessage()
    ]);
    exit;
} 