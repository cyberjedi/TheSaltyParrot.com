<?php
/**
 * Update Profile API
 * 
 * Handles user profile updates and syncs with the database
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

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate input
if (!isset($data['displayName']) || trim($data['displayName']) === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Display name is required']);
    exit;
}

// Clean and validate photo URL
$photoURL = null;
if (isset($data['photoURL']) && trim($data['photoURL']) !== '') {
    $photoURL = filter_var(trim($data['photoURL']), FILTER_VALIDATE_URL);
    if ($photoURL === false) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid photo URL']);
        exit;
    }
}

try {
    // Include database connection
    require_once __DIR__ . '/../config/db_connect.php';
    
    // Update user in database
    $stmt = $conn->prepare("
        UPDATE users 
        SET display_name = ?, 
            photo_url = ?,
            last_login = CURRENT_TIMESTAMP
        WHERE uid = ?
    ");
    
    $result = $stmt->execute([
        trim($data['displayName']),
        $photoURL,
        $_SESSION['uid']
    ]);
    
    if ($result) {
        // Update session
        $_SESSION['displayName'] = trim($data['displayName']);
        $_SESSION['photoURL'] = $photoURL;
        
        // Return success
        echo json_encode([
            'success' => true,
            'user' => [
                'displayName' => $_SESSION['displayName'],
                'photoURL' => $_SESSION['photoURL']
            ]
        ]);
    } else {
        throw new Exception('Failed to update user profile');
    }
    
} catch (Exception $e) {
    error_log("Error updating profile: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update profile']);
    exit;
} 