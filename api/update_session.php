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
        // First log what we're about to sync
        error_log("Syncing user data - UID: " . $data['uid'] . ", Display Name: " . ($data['displayName'] ?? 'null') . ", Photo URL: " . ($data['photoURL'] ?? 'null'));
        
        $stmt = $conn->prepare("
            INSERT INTO users (uid, email, display_name, photo_url) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                email = VALUES(email),
                display_name = COALESCE(VALUES(display_name), display_name),
                photo_url = COALESCE(VALUES(photo_url), photo_url),
                last_login = CURRENT_TIMESTAMP
        ");
        
        $success = $stmt->execute([
            $data['uid'],
            $data['email'],
            $data['displayName'] ?? null,
            $data['photoURL'] ?? null
        ]);
        
        error_log("User sync result: " . ($success ? "Success" : "Failed"));
        
        // Get the latest values from the database after update
        $fetchStmt = $conn->prepare("SELECT display_name, photo_url FROM users WHERE uid = ?");
        $fetchStmt->execute([$data['uid']]);
        $dbUser = $fetchStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($dbUser) {
            error_log("Database values after sync - display_name: " . ($dbUser['display_name'] ?? 'null') . ", photo_url: " . ($dbUser['photo_url'] ?? 'null'));
            
            // Update session with the actual database values
            $_SESSION['displayName'] = $dbUser['display_name'] ?? $_SESSION['displayName'];
            $_SESSION['photoURL'] = $dbUser['photo_url'] ?? $_SESSION['photoURL'];
            
            error_log("Session updated with DB values - displayName: " . $_SESSION['displayName'] . ", photoURL: " . ($_SESSION['photoURL'] ?? 'null'));
        }
    } else {
        error_log("Database connection not available for user sync");
    }
} catch (PDOException $e) {
    error_log("Error syncing user data: " . $e->getMessage());
    // Don't return error to client as this is not critical
}

// Return success response
echo json_encode(['success' => true]); 