<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Include database connection
require_once '../config/db_connect.php';

$gameId = $_POST['game_id'] ?? null;
$userId = $_POST['user_id'] ?? null;
$userEmail = $_POST['user_email'] ?? 'unknown@user.com';
$entryType = $_POST['entry_type'] ?? 'custom';
$content = $_POST['content'] ?? null;
$isPublic = isset($_POST['is_public']) ? (bool)$_POST['is_public'] : true;

if (!$gameId || !$userId || !$content) {
    echo json_encode(['status' => 'error', 'message' => 'Game ID, User ID, and Content required']);
    exit;
}

try {
    // Check if user is a member of the session
    $stmt = $conn->prepare("SELECT * FROM session_members WHERE session_id = :session_id AND user_id = :user_id");
    $stmt->execute([
        ':session_id' => $gameId,
        ':user_id' => $userId
    ]);
    
    $membership = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$membership) {
        echo json_encode(['status' => 'error', 'message' => 'You are not a member of this game session']);
        exit;
    }
    
    // Create log entry
    $stmt = $conn->prepare("INSERT INTO game_log_entries (session_id, user_id, entry_type, content, is_public) 
                           VALUES (:session_id, :user_id, :entry_type, :content, :is_public)");
    $stmt->execute([
        ':session_id' => $gameId,
        ':user_id' => $userId,
        ':entry_type' => $entryType,
        ':content' => $content,
        ':is_public' => $isPublic ? 1 : 0
    ]);
    
    // Get the newly created entry
    $entryId = $conn->lastInsertId();
    $stmt = $conn->prepare("SELECT * FROM game_log_entries WHERE id = :id");
    $stmt->execute([':id' => $entryId]);
    $entry = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Add user email for client-side display
    $entry['user_email'] = $userEmail;
    
    echo json_encode(['status' => 'success', 'entry' => $entry]);
} catch(PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>
