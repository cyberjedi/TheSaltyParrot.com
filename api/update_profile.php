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
        error_log("Database connection failed - \$conn is null or not set");
        throw new Exception('Database connection failed');
    }

    // Debug the connection details
    $driverName = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);
    error_log("PDO Driver: " . $driverName);
    error_log("PDO Connection Status: " . ($conn ? "Connected" : "Failed"));
    error_log("Current UID from session: " . $_SESSION['uid']);
    error_log("Display Name to update: " . trim($data['displayName']));
    error_log("Photo URL to update: " . ($photoURL ?? 'NULL'));

    // First verify the user exists
    $checkStmt = $conn->prepare("SELECT uid FROM users WHERE uid = ?");
    $checkStmt->execute([$_SESSION['uid']]);
    $userExists = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("User exists check: " . ($userExists ? "Yes" : "No"));
    
    if (!$userExists) {
        error_log("User does not exist in database, creating new record");
        // Insert user if they don't exist
        $insertStmt = $conn->prepare("INSERT INTO users (uid, display_name, photo_url, email) VALUES (?, ?, ?, ?)");
        $success = $insertStmt->execute([
            $_SESSION['uid'],
            trim($data['displayName']),
            $photoURL,
            $_SESSION['email'] ?? null
        ]);
        
        error_log("Insert result: " . ($success ? "Success" : "Failed"));
        if (!$success) {
            error_log("Insert error info: " . print_r($insertStmt->errorInfo(), true));
            throw new Exception("Failed to create user record: " . implode(", ", $insertStmt->errorInfo()));
        }
    } else {
        // Update user profile
        $stmt = $conn->prepare("UPDATE users SET display_name = ?, photo_url = ? WHERE uid = ?");
        if (!$stmt) {
            error_log("Failed to prepare UPDATE statement: " . print_r($conn->errorInfo(), true));
            throw new Exception('Failed to prepare UPDATE statement: ' . implode(' ', $conn->errorInfo()));
        }
        
        error_log("Executing UPDATE with parameters: " . trim($data['displayName']) . ", " . ($photoURL ?? 'NULL') . ", " . $_SESSION['uid']);
        
        $success = $stmt->execute([
            trim($data['displayName']),
            $photoURL,
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
        $verifyStmt = $conn->prepare("SELECT display_name, photo_url FROM users WHERE uid = ?");
        $verifyStmt->execute([$_SESSION['uid']]);
        $updated = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$updated) {
            error_log("Verification failed: couldn't retrieve user after update");
        } else {
            error_log("Retrieved after update - display_name: " . ($updated['display_name'] ?? 'NULL') . 
                      ", photo_url: " . ($updated['photo_url'] ?? 'NULL'));
            
            if ($updated['display_name'] !== trim($data['displayName'])) {
                error_log("Warning: display_name mismatch after update. Expected: " . trim($data['displayName']) . 
                          ", Got: " . ($updated['display_name'] ?? 'NULL'));
            }
        }
    }

    // Update session
    $_SESSION['displayName'] = trim($data['displayName']);
    $_SESSION['photoURL'] = $photoURL;
    error_log("Session updated - displayName: " . $_SESSION['displayName'] . ", photoURL: " . ($_SESSION['photoURL'] ?? 'NULL'));

    // Return success
    echo json_encode([
        'success' => true,
        'user' => [
            'displayName' => $_SESSION['displayName'],
            'photoURL' => $_SESSION['photoURL']
        ]
    ]);
    error_log("Success response sent to client");

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