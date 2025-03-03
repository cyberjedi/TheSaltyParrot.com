<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Include database connection
require_once '../config/db_connect.php';

$userId = $_POST['user_id'] ?? null;
$userEmail = $_POST['user_email'] ?? 'unknown@user.com';
$joinCode = $_POST['join_code'] ?? null;

if (!$userId || !$joinCode) {
    echo json_encode(['status' => 'error', 'message' => 'User ID and Join Code required']);
    exit;
}

try {
    // Find game session with the join code
    $stmt = $conn->prepare("SELECT * FROM game_sessions WHERE join_code = :join_code AND status = 'active'");
    $stmt->execute([':join_code' => $joinCode]);
    $gameSession = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$gameSession) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid join code or inactive game session']);
        exit;
    }
    
    // Check if user is already a member
    $stmt = $conn->prepare("SELECT * FROM session_members WHERE session_id = :session_id AND user_id = :user_id");
    $stmt->execute([
        ':session_id' => $gameSession['id'],
        ':user_id' => $userId
    ]);
    $existingMembership = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingMembership) {
        // Add user as member
        $stmt = $conn->prepare("INSERT INTO session_members (session_id, user_id, role) 
                               VALUES (:session_id, :user_id, 'player')");
        $stmt->execute([
            ':session_id' => $gameSession['id'],
            ':user_id' => $userId
        ]);
        
        // Add log entry for new player
        $stmt = $conn->prepare("INSERT INTO game_log_entries (session_id, user_id, entry_type, content) 
                               VALUES (:session_id, :user_id, 'system', :content)");
        $stmt->execute([
            ':session_id' => $gameSession['id'],
            ':user_id' => $userId,
            ':content' => json_encode([
                'message' => "$userEmail joined the crew!",
                'timestamp' => time()
            ])
        ]);
    }
    
    echo json_encode([
        'status' => 'success', 
        'game_id' => $gameSession['id'], 
        'session_name' => $gameSession['name']
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>
