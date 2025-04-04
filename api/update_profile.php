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

try {
    require_once '../config/db_connect.php';
    
    if (!isset($conn) || $conn === null) {
        throw new Exception('Database connection failed');
    }
    
    error_log("Updating profile for user: " . $_SESSION['uid']);
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT uid FROM users WHERE uid = ?");
    $stmt->execute([$_SESSION['uid']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Create user if not exists
        $stmt = $conn->prepare("INSERT INTO users (uid, display_name) VALUES (?, ?)");
        if (!$stmt) {
            error_log("Failed to prepare INSERT statement: " . print_r($conn->errorInfo(), true));
            throw new Exception('Failed to prepare INSERT statement: ' . implode(' ', $conn->errorInfo()));
        }
        
        $success = $stmt->execute([
            $_SESSION['uid'],
            trim($data['displayName'])
        ]);
        
        if (!$success) {
            throw new Exception('Failed to create user profile');
        }
    } else {
        // Update user profile
        $stmt = $conn->prepare("UPDATE users SET display_name = ? WHERE uid = ?");
        if (!$stmt) {
            error_log("Failed to prepare UPDATE statement: " . print_r($conn->errorInfo(), true));
            throw new Exception('Failed to prepare UPDATE statement: ' . implode(' ', $conn->errorInfo()));
        }
        
        error_log("Executing UPDATE with parameters: " . trim($data['displayName']) . ", " . $_SESSION['uid']);
        
        $success = $stmt->execute([
            trim($data['displayName']),
            $_SESSION['uid']
        ]);
        
        error_log("Update result: " . ($success ? "Success" : "Failed"));
        if (!$success) {
            $error = $stmt->errorInfo();
            error_log("Update failed. Error info: " . print_r($error, true));
            throw new Exception('Update failed: ' . ($error[2] ?? 'Unknown error'));
        }
        
        // Get the number of affected rows
        $rowCount = $stmt->rowCount();
        error_log("Rows affected by UPDATE: " . $rowCount);
        
        if ($rowCount === 0) {
            error_log("Warning: UPDATE statement didn't modify any rows, even though user exists");
        }
        
        // Verify the update
        $verifyStmt = $conn->prepare("SELECT display_name FROM users WHERE uid = ?");
        $verifyStmt->execute([$_SESSION['uid']]);
        $updated = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$updated) {
            error_log("Verification failed: couldn't retrieve user after update");
        } else {
            error_log("Verification successful. Updated display_name: " . $updated['display_name']);
        }
    }
    
    // Update session
    $_SESSION['displayName'] = trim($data['displayName']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'data' => [
            'displayName' => trim($data['displayName'])
        ]
    ]);
} catch (Exception $e) {
    error_log("Error updating profile: " . $e->getMessage());
    error_log("Error trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to update profile: ' . $e->getMessage()
    ]);
} 