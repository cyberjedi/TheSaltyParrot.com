<?php
/**
 * Get User Photos API (Filesystem Scan Version)
 * 
 * Retrieves all photos uploaded by the current user by scanning the upload directory
 * for filenames starting with the user's UID.
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
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

$user_id = $_SESSION['uid'];
error_log("get_user_photos.php: Scanning for photos for user ID: " . $user_id);

$response = ['success' => false, 'message' => 'An error occurred while fetching photos.', 'photos' => []];
$photos_list = [];

try {
    $upload_dir_relative_to_script = '../../uploads/character_sheets/';
    $web_base_path = 'uploads/character_sheets/'; // Path for browser access

    // Ensure the directory exists
    if (!is_dir($upload_dir_relative_to_script)) {
         // If the directory doesn't exist, there are no photos. Return success with empty list.
         $response['success'] = true;
         $response['photos'] = [];
         $response['message'] = 'Upload directory not found.'; // Or 'No photos found.'
         echo json_encode($response);
         exit;
    }

    // Scan the directory
    $files = scandir($upload_dir_relative_to_script);
    
    if ($files === false) {
        throw new Exception('Could not scan upload directory.');
    }

    // Filter files: must start with user_id + "_"
    $user_prefix = $user_id . '_';
    foreach ($files as $file) {
        // Skip directories and ensure it starts with the user's prefix
        if ($file !== '.' && $file !== '..' && strpos($file, $user_prefix) === 0) {
            $full_file_path = $upload_dir_relative_to_script . $file;
            // Ensure it's actually a file (not a subdir named like a file)
            if (is_file($full_file_path)) {
                 $web_path = $web_base_path . $file;
                 $photos_list[] = [
                    'id' => $web_path,  // Use path as ID for now
                    'url' => $web_path
                 ];
            }
        }
    }

    // Optional: Sort photos, e.g., by filename (which includes timestamp)
    // sort($photos_list); // Or use usort for custom sorting

    $response['success'] = true;
    $response['photos'] = $photos_list;
    $response['message'] = 'Photos loaded successfully.';

} catch (Exception $e) {
    error_log("General Error (get_user_photos - filesystem): " . $e->getMessage());
    $response['message'] = 'An internal error occurred while scanning for photos.';
    http_response_code(500);
}

echo json_encode($response);
exit;