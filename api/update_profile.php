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

// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
if (!$json) {
    http_response_code(400);
    echo json_encode(['error' => 'No data received']);
    exit;
}

$data = json_decode($json, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON data: ' . json_last_error_msg()]);
    exit;
}

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
    
    if (!isset($conn) || $conn === null) {
        throw new Exception('Database connection failed');
    }

    // Update user profile
    $stmt = $conn->prepare("UPDATE users SET display_name = ?, photo_url = ? WHERE uid = ?");
    if (!$stmt) {
        throw new Exception('Failed to prepare UPDATE statement: ' . implode(' ', $conn->errorInfo()));
    }
    
    $success = $stmt->execute([
        trim($data['displayName']),
        $photoURL,
        $_SESSION['uid']
    ]);
    
    if (!$success) {
        $error = $stmt->errorInfo();
        error_log("Update failed. Error: " . print_r($error, true));
        error_log("Attempted values - display_name: " . trim($data['displayName']) . ", photo_url: " . $photoURL . ", uid: " . $_SESSION['uid']);
        throw new Exception('Update failed: ' . ($error[2] ?? 'Unknown error'));
    }
    
    // Verify the update
    $verifyStmt = $conn->prepare("SELECT display_name, photo_url FROM users WHERE uid = ?");
    $verifyStmt->execute([$_SESSION['uid']]);
    $updated = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$updated || $updated['display_name'] !== trim($data['displayName'])) {
        error_log("Update verification failed. Expected: " . trim($data['displayName']) . ", Got: " . ($updated['display_name'] ?? 'null'));
        throw new Exception('Update verification failed - database not updated');
    }

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

} catch (Exception $e) {
    error_log('Profile update error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Profile update failed',
        'message' => $e->getMessage(),
        'uid' => $_SESSION['uid'] ?? 'not set'
    ]);
    exit;
} 