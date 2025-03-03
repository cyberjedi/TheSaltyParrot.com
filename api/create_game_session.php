<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Include database connection
require_once '../config/db_connect.php';

// Get user ID from Firebase JWT token (you'll need to implement this)
// For now, we'll use a placeholder value from POST data
$userId = $_POST['user_id'] ?? null;
$userEmail = $_POST['user_email'] ?? 'unknown@user.com';
$sessionName = $_POST['session_name'] ?? 'New Pirate Adventure';

if (!$userId) {
    echo json_encode(['status' => 'error', 'message' => 'User ID required']);
    exit;
}

try {
    // Generate a unique join code (6 alphanumeric characters)
    $joinCode = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6);
    
    // Create new game session
    $gameId = uniqid();
    $stmt = $conn->prepare("INSERT INTO game_sessions (id, name, gm_user_id, join_code) 
                           VALUES (:id, :name, :gm_user_id, :join_code)");
    $stmt->execute([
        ':id' => $gameId,
        ':name' => $sessionName,
        ':gm_user_id' => $userId,
        ':join_code' => $joinCode
    ]);
    
    // Add GM as a member
    $stmt = $conn->prepare("INSERT INTO session_members (session_id, user_id, role) 
                           VALUES (:session_id, :user_id, 'gm')");
    $stmt->execute([
        ':session_id' => $gameId,
        ':user_id' => $userId
    ]);
    
    // Create system log entry about session creation
    $stmt = $conn->prepare("INSERT INTO game_log_entries (session_id, user_id, entry_type, content) 
                           VALUES (:session_id, :user_id, 'system', :content)");
    $stmt->execute([
        ':session_id' => $gameId,
        ':user_id' => $userId,
        ':content' => json_encode([
            'message' => "Game session created by $userEmail",
            'timestamp' => time()
        ])
    ]);
    
    echo json_encode([
        'status' => 'success', 
        'game_id' => $gameId, 
        'join_code' => $joinCode
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>
