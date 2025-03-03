<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Include database connection
require_once '../config/db_connect.php';

$gameId = $_GET['game_id'] ?? null;

if (!$gameId) {
    echo json_encode(['status' => 'error', 'message' => 'Game ID required']);
    exit;
}

try {
    // Get session info
    $stmt = $conn->prepare("SELECT * FROM game_sessions WHERE id = :game_id AND status = 'active'");
    $stmt->execute([':game_id' => $gameId]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$session) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid or inactive game session']);
        exit;
    }
    
    echo json_encode(['status' => 'success', 'session' => $session]);
} catch(PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>
