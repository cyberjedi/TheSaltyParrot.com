<?php
/**
 * Upload Photo API
 * 
 * Handles uploading character images
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

$user_id = $_SESSION['uid'];
$sheet_id = isset($_POST['sheet_id']) ? (int)$_POST['sheet_id'] : null;

try {
    // Check if file was uploaded
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error occurred');
    }
    
    // Check file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $file_info = finfo_open(FILEINFO_MIME_TYPE);
    $file_type = finfo_file($file_info, $_FILES['image']['tmp_name']);
    finfo_close($file_info);
    
    if (!in_array($file_type, $allowed_types)) {
        throw new Exception('Invalid file type. Please upload a JPEG, PNG, or GIF image.');
    }
    
    // Generate unique filename
    $upload_dir = '../../uploads/character_sheets/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $new_filename = $user_id . '_' . time() . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;
    $relative_path = '../uploads/character_sheets/' . $new_filename;
    
    // Move the uploaded file
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
        throw new Exception('Failed to upload image. Please try again.');
    }
    
    // If a sheet ID was provided, update that sheet's image
    if ($sheet_id) {
        require_once '../../config/db_connect.php';
        
        if (!isset($conn) || $conn === null) {
            throw new Exception('Database connection failed');
        }
        
        // Verify sheet belongs to user
        $stmt = $conn->prepare("SELECT id FROM character_sheets WHERE id = ? AND user_id = ?");
        $stmt->execute([$sheet_id, $user_id]);
        if (!$stmt->fetch()) {
            // Clean up the uploaded file
            @unlink($upload_path);
            throw new Exception('Sheet not found or access denied');
        }
        
        // Update the sheet with the new image path
        $stmt = $conn->prepare("UPDATE character_sheets SET image_path = ? WHERE id = ?");
        $stmt->execute([$relative_path, $sheet_id]);
    }
    
    // Return success with the path to the uploaded image
    echo json_encode([
        'success' => true,
        'path' => $relative_path,
        'message' => 'Image uploaded successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
    exit;
} 