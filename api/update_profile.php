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

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON content type
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['uid'])) {
    error_log("Update profile failed: User not authenticated");
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Log received data
error_log("Received profile update request for user " . $_SESSION['uid'] . ": " . print_r($data, true));

// Validate input
if (!isset($data['displayName']) || trim($data['displayName']) === '') {
    error_log("Update profile failed: Display name is required");
    http_response_code(400);
    echo json_encode(['error' => 'Display name is required']);
    exit;
}

// Clean and validate photo URL
$photoURL = null;
if (isset($data['photoURL']) && trim($data['photoURL']) !== '') {
    $photoURL = filter_var(trim($data['photoURL']), FILTER_VALIDATE_URL);
    if ($photoURL === false) {
        error_log("Update profile failed: Invalid photo URL - " . $data['photoURL']);
        http_response_code(400);
        echo json_encode(['error' => 'Invalid photo URL']);
        exit;
    }
}

try {
    // Include database connection
    require_once __DIR__ . '/../config/db_connect.php';
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Update user in database
    $stmt = $conn->prepare("
        INSERT INTO users (uid, display_name, photo_url, email)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            display_name = VALUES(display_name),
            photo_url = VALUES(photo_url),
            last_login = CURRENT_TIMESTAMP
    ");
    
    $params = [
        $_SESSION['uid'],
        trim($data['displayName']),
        $photoURL,
        $_SESSION['email'] ?? null
    ];
    
    error_log("Executing update with params: " . print_r($params, true));
    
    $result = $stmt->execute($params);
    
    if ($result) {
        // Update session
        $_SESSION['displayName'] = trim($data['displayName']);
        $_SESSION['photoURL'] = $photoURL;
        
        error_log("Profile updated successfully for user " . $_SESSION['uid']);
        
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
    error_log("Error updating profile for user {$_SESSION['uid']}: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update profile: ' . $e->getMessage()]);
    exit;
} 