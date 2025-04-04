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
    echo json_encode([
        'success' => false,
        'error' => 'Not authenticated'
    ]);
    exit;
}

// Check if a file was uploaded
if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'success' => false,
        'error' => 'No file uploaded or upload error'
    ]);
    exit;
}

// Check file size
if ($_FILES['profile_photo']['size'] > 2 * 1024 * 1024) { // 2 MB max
    echo json_encode([
        'success' => false,
        'error' => 'File size exceeds maximum allowed (2MB)'
    ]);
    exit;
}

// Check file type
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$file_info = finfo_open(FILEINFO_MIME_TYPE);
$file_type = finfo_file($file_info, $_FILES['profile_photo']['tmp_name']);
finfo_close($file_info);

if (!in_array($file_type, $allowed_types)) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid file type. Please upload a JPEG, PNG, or GIF image.'
    ]);
    exit;
}

// Include database connection
require_once '../config/db_connect.php';

try {
    // Get current photo_url
    $stmt = $conn->prepare("SELECT photo_url FROM users WHERE uid = ?");
    $stmt->execute([$_SESSION['uid']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_photo_url = $user ? $user['photo_url'] : null;
    
    // Create upload directory if it doesn't exist
    $upload_dir = '../uploads/profile_photos/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate unique filename
    $file_extension = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
    $new_filename = $_SESSION['uid'] . '_' . time() . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;
    $relative_path = '../uploads/profile_photos/' . $new_filename;
    
    // Move the uploaded file
    if (!move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
        throw new Exception('Failed to move uploaded file');
    }
    
    // Delete old image if it exists and is not the default
    if ($current_photo_url && strpos($current_photo_url, 'default') === false && file_exists($current_photo_url)) {
        @unlink($current_photo_url);
    }
    
    // Update database
    $stmt = $conn->prepare("UPDATE users SET photo_url = ? WHERE uid = ?");
    $stmt->execute([$relative_path, $_SESSION['uid']]);
    
    // Update session
    $_SESSION['photoURL'] = $relative_path;
    
    echo json_encode([
        'success' => true,
        'photo_url' => $relative_path
    ]);
} catch (Exception $e) {
    error_log("Error updating profile photo: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to update profile photo: ' . $e->getMessage()
    ]);
} 