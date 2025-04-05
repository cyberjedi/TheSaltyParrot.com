<?php
/**
 * User Synchronization Script
 * Syncs Firebase user data with MySQL users table
 */

require_once '../config/db_connect.php';
require_once '../config/firebase-config.php';

function syncUser($uid, $email = null, $displayName = null, $photoURL = null) {
    global $conn;
    
    try {
        // Check if user exists
        $stmt = $conn->prepare("SELECT uid FROM users WHERE uid = ?");
        $stmt->execute([$uid]);
        $userExists = $stmt->fetch();

        if ($userExists) {
            // Update existing user
            $updateFields = [];
            $params = [];
            
            if ($email !== null) {
                $updateFields[] = "email = ?";
                $params[] = $email;
            }
            
            if ($displayName !== null) {
                $updateFields[] = "display_name = ?";
                $params[] = $displayName;
            }
            
            if ($photoURL !== null) {
                $updateFields[] = "photo_url = ?";
                $params[] = $photoURL;
            }
            
            if (!empty($updateFields)) {
                $updateFields[] = "last_login = CURRENT_TIMESTAMP";
                $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE uid = ?";
                $params[] = $uid;
                
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
            }
        } else {
            // Insert new user
            $stmt = $conn->prepare("
                INSERT INTO users (uid, email, display_name, photo_url)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$uid, $email, $displayName, $photoURL]);
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Error syncing user: " . $e->getMessage());
        return false;
    }
}

// Handle direct API calls
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['uid'])) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID is required']);
        exit;
    }
    
    $result = syncUser(
        $data['uid'],
        $data['email'] ?? null,
        $data['displayName'] ?? null,
        $data['photoURL'] ?? null
    );
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to sync user']);
    }
    exit;
} 