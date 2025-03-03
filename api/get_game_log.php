<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Include database connection
require_once '../config/db_connect.php';

$gameId = $_GET['game_id'] ?? null;
$afterTimestamp = $_GET['after'] ?? 0;

if (!$gameId) {
    echo json_encode(['status' => 'error', 'message' => 'Game ID required']);
    exit;
}

try {
    // Check session validity
    $stmt = $conn->prepare("SELECT * FROM game_sessions WHERE id = :game_id AND status = 'active'");
    $stmt->execute([':game_id' => $gameId]);
    $gameSession = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$gameSession) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid or inactive game session']);
        exit;
    }
    
    // Get log entries after the specified timestamp
    $stmt = $conn->prepare("SELECT g.*, u.email as user_email 
                           FROM game_log_entries g
                           LEFT JOIN session_members s ON g.user_id = s.user_id
                           LEFT JOIN users u ON g.user_id = u.id
                           WHERE g.session_id = :game_id 
                           AND g.created_at > FROM_UNIXTIME(:after_timestamp)
                           ORDER BY g.created_at ASC");
    $stmt->execute([
        ':game_id' => $gameId,
        ':after_timestamp' => $afterTimestamp
    ]);
    
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process each entry
    foreach ($entries as &$entry) {
        // Convert content from JSON to object
        $entry['content'] = json_decode($entry['content'], true);
        
        // Format timestamp
        $entry['timestamp'] = strtotime($entry['created_at']);
        
        // If no user email found, use a default
        if (!isset($entry['user_email']) || empty($entry['user_email'])) {
            $entry['user_email'] = 'Anonymous Pirate';
        }
    }
    
    echo json_encode(['status' => 'success', 'entries' => $entries]);
} catch(PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>
