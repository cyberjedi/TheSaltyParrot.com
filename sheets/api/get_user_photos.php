<?php
/**
 * Get User Photos API
 * 
 * Retrieves all photos uploaded by the current user
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

try {
    // Include database connection
    require_once '../../config/db_connect.php';
    
    if (!isset($conn) || $conn === null) {
        throw new Exception('Database connection failed');
    }

    // Get the upload directory path
    $upload_dir = '../../uploads/character_sheets/';
    $photos = [];
    
    // First, get all photos from the database that belong to this user's sheets
    $stmt = $conn->prepare("SELECT DISTINCT image_path FROM character_sheets WHERE user_id = ? AND image_path IS NOT NULL AND image_path != '../assets/TSP_default_character.jpg'");
    $stmt->execute([$user_id]);
    $db_photos = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Add database photos to the list
    foreach ($db_photos as $path) {
        // Ensure the path from DB is also root-relative
        $web_path = ltrim($path, './'); // Remove leading ./ or ../
        if (strpos($web_path, 'uploads/') !== 0) { // Add uploads/ if missing (basic check)
            // This might need refinement depending on how paths are stored
            // $web_path = 'uploads/character_sheets/' . basename($web_path); 
        }

        if (file_exists('../../' . $web_path)) { // Check existence relative to script location
            $photos[] = [
                'url' => $web_path, // Use the root-relative path for the browser
                'source' => 'database'
            ];
        }
    }
    
    // Now scan the uploads directory for any photos with the user's ID in the filename
    if (is_dir($upload_dir)) {
        $files = scandir($upload_dir);
        foreach ($files as $file) {
            // Skip . and .. directories
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            // Check if the filename contains the user's ID
            if (strpos($file, $user_id . '_') === 0) {
                $web_path = 'uploads/character_sheets/' . $file; // Removed ../
                
                // Check if this path is already in our photos array
                $exists = false;
                foreach ($photos as $photo) {
                    if ($photo['url'] === $web_path) { // Check against 'url'
                        $exists = true;
                        break;
                    }
                }
                
                if (!$exists) {
                    $photos[] = [
                        'url' => $web_path, // Use the root-relative path
                        'source' => 'filesystem'
                    ];
                }
            }
        }
    }
    
    // Return photos as JSON
    echo json_encode([
        'success' => true,
        'photos' => $photos // Ensure the key is 'photos' as expected by JS
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to load photos: ' . $e->getMessage()
    ]);
    exit;
} 