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
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Get user ID from session
$userId = $_SESSION['user_id'];

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Check if displayName is provided
if (!isset($data['displayName']) || empty(trim($data['displayName']))) {
    echo json_encode(['success' => false, 'message' => 'Display name is required']);
    exit;
}

$displayName = trim($data['displayName']);

// Update profile in database
try {
    require_once '../config/database.php';
    
    if (!isset($pdo) || $pdo === null) {
        throw new Exception('Database connection failed');
    }
    
    error_log("Updating profile for user: " . $userId);
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = :userId");
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Create user if not exists
        $stmt = $pdo->prepare("INSERT INTO users (id, display_name) VALUES (:userId, :displayName)");
        if (!$stmt) {
            error_log("Failed to prepare INSERT statement: " . print_r($pdo->errorInfo(), true));
            throw new Exception('Failed to prepare INSERT statement: ' . implode(' ', $pdo->errorInfo()));
        }
        
        $success = $stmt->execute([
            ':userId' => $userId,
            ':displayName' => $displayName
        ]);
        
        if (!$success) {
            throw new Exception('Failed to create user profile');
        }
    } else {
        // Update user profile
        $stmt = $pdo->prepare("UPDATE users SET display_name = :displayName WHERE id = :userId");
        if (!$stmt) {
            error_log("Failed to prepare UPDATE statement: " . print_r($pdo->errorInfo(), true));
            throw new Exception('Failed to prepare UPDATE statement: ' . implode(' ', $pdo->errorInfo()));
        }
        
        error_log("Executing UPDATE with parameters: " . $displayName . ", " . $userId);
        
        $success = $stmt->execute([
            ':displayName' => $displayName,
            ':userId' => $userId
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
        $verifyStmt = $pdo->prepare("SELECT display_name FROM users WHERE id = :userId");
        $verifyStmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $verifyStmt->execute();
        $updated = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$updated) {
            error_log("Verification failed: couldn't retrieve user after update");
        } else {
            error_log("Verification successful. Updated display_name: " . $updated['display_name']);
        }
    }
    
    // Update session
    $_SESSION['display_name'] = $displayName;
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'data' => [
            'displayName' => $displayName
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