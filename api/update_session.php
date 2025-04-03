<?php
/**
 * Update Firebase Session
 * 
 * Updates the PHP session with Firebase user data
 */

// Start the session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include Firebase configuration
require_once __DIR__ . '/../config/firebase-config.php';
require_once __DIR__ . '/../config/db_connect.php';

// Set JSON content type
header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate required fields
if (!isset($data['uid']) || !isset($data['email']) || !isset($data['token'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Store user data in session
$_SESSION['uid'] = $data['uid'];
$_SESSION['email'] = $data['email'];
$_SESSION['displayName'] = $data['displayName'] ?? null;
$_SESSION['photoURL'] = $data['photoURL'] ?? null;
$_SESSION['firebase_token'] = $data['token'];

// Sync user data with MySQL database
try {
    global $conn;
    if ($conn) {
        $stmt = $conn->prepare("
            INSERT INTO users (uid, email, display_name, photo_url) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                email = VALUES(email),
                display_name = VALUES(display_name),
                photo_url = VALUES(photo_url),
                last_login = CURRENT_TIMESTAMP
        ");
        
        $stmt->execute([
            $data['uid'],
            $data['email'],
            $data['displayName'] ?? null,
            $data['photoURL'] ?? null
        ]);
    }
} catch (PDOException $e) {
    error_log("Error syncing user data: " . $e->getMessage());
    // Don't return error to client as this is not critical
}

// Return success response
echo json_encode(['success' => true]); 