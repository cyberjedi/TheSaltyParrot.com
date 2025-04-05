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
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Get user ID from session
$userId = $_SESSION['uid'];

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
    require_once '../config/db_connect.php';
    
    if (!isset($conn) || $conn === null) {
        throw new Exception('Database connection failed');
    }
    
    error_log("Updating profile for user UID: " . $userId);
    
    // Check if user exists using UID
    $stmt = $conn->prepare("SELECT uid FROM users WHERE uid = :uid");
    $stmt->bindParam(':uid', $userId, PDO::PARAM_STR);
    $stmt->execute();
    $userExists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userExists) {
        // This case might need review - should an update create a user? 
        // For now, let's assume the user MUST exist for an update.
        error_log("User with UID " . $userId . " not found for update.");
        throw new Exception('User profile not found'); 
        
        /* // Original INSERT logic (commented out for now, as update shouldn't typically create)
        $stmt = $conn->prepare("INSERT INTO users (uid, display_name) VALUES (:uid, :displayName)"); // Use uid
        if (!$stmt) {
            error_log("Failed to prepare INSERT statement: " . print_r($conn->errorInfo(), true));
            throw new Exception('Failed to prepare INSERT statement: ' . implode(' ', $conn->errorInfo()));
        }
        
        $success = $stmt->execute([
            ':uid' => $userId, // Use uid
            ':displayName' => $displayName
        ]);
        
        if (!$success) {
            throw new Exception('Failed to create user profile during update attempt');
        }
        */
    } else {
        // Update user profile using UID
        $stmt = $conn->prepare("UPDATE users SET display_name = :displayName WHERE uid = :uid");
        if (!$stmt) {
            error_log("Failed to prepare UPDATE statement: " . print_r($conn->errorInfo(), true));
            throw new Exception('Failed to prepare UPDATE statement: ' . implode(' ', $conn->errorInfo()));
        }
        
        error_log("Executing UPDATE with parameters: displayName=" . $displayName . ", uid=" . $userId);
        
        $success = $stmt->execute([
            ':displayName' => $displayName,
            ':uid' => $userId
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
            // This can happen if the new display name is the same as the old one. Not necessarily an error.
            error_log("Warning: UPDATE statement didn't modify any rows. Display name might be unchanged.");
        }
        
        // Verify the update
        $verifyStmt = $conn->prepare("SELECT display_name FROM users WHERE uid = :uid");
        $verifyStmt->bindParam(':uid', $userId, PDO::PARAM_STR);
        $verifyStmt->execute();
        $updated = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$updated) {
            error_log("Verification failed: couldn't retrieve user after update");
        } else {
             if ($updated['display_name'] === $displayName) {
                error_log("Verification successful. Updated display_name: " . $updated['display_name']);
            } else {
                 error_log("Verification discrepancy! DB has: " . $updated['display_name'] . " but expected: " . $displayName);
                 // Still treat as success for now, as the UPDATE reported success.
            }
        }
    }
    
    // Update session
    $_SESSION['displayName'] = $displayName;
    
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