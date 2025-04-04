<?php
/**
 * API to get the active character for a user
 */

// Start the session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set headers for JSON response
header('Content-Type: application/json');

// Get the user ID from the request
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

if (!$user_id) {
    echo json_encode([
        'success' => false,
        'error' => 'User ID is required'
    ]);
    exit;
}

// Include database connection
require_once '../config/db_connect.php';

try {
    // Query to get the active character for the user
    $stmt = $conn->prepare("
        SELECT cs.*, pb.*
        FROM character_sheets cs
        LEFT JOIN pirate_borg_sheets pb ON cs.id = pb.sheet_id
        WHERE cs.user_id = ? AND cs.is_active = 1
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $character = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'character' => $character ? $character : null
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} 