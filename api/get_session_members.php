<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Include database connection
require_once '../config/db_connect.php';

$sessionId = $_GET['session_id'] ?? null;

if (!$sessionId) {
    echo json_encode(['status' => 'error', 'message' => 'Session ID required']);
    exit;
}

try {
    // Check session validity
    $stmt = $conn->prepare("SELECT * FROM game_sessions WHERE id = :session_id AND status = 'active'");
    $stmt->execute([':session_id' => $sessionId]);
    $gameSession = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$gameSession) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid or inactive game session']);
        exit;
    }
    
    // Get session members
    $stmt = $conn->prepare("
        SELECT m.*, u.email as user_email 
        FROM session_members m
        LEFT JOIN users u ON m.user_id = u.id
        WHERE m.session_id = :session_id
        ORDER BY m.role DESC, m.created_at ASC
    ");
    $stmt->execute([':session_id' => $sessionId]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'members' => $members]);
} catch(PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>
