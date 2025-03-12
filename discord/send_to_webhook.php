<?php
// File: discord/send_to_webhook.php
// This file sends the generated content to a Discord webhook

require_once 'discord-config.php';
require_once '../config/db_connect.php';
require_once 'discord_service.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Check required fields
if (!isset($data['webhook_id']) || empty($data['webhook_id']) || !isset($data['content']) || empty($data['content'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

// Get optional character image
$character_image = isset($data['character_image']) ? $data['character_image'] : null;

// Check if user is logged in
if (!is_discord_authenticated()) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated with Discord']);
    exit;
}

// Get generator type
$generator_type = isset($data['generator_type']) ? $data['generator_type'] : 'unknown';

// Get Discord user ID and fetch user from database
$discord_id = $_SESSION['discord_user']['id'];

try {
    // Get user ID from database
    $stmt = $conn->prepare("SELECT id FROM discord_users WHERE discord_id = :discord_id");
    $stmt->bindParam(':discord_id', $discord_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        exit;
    }
    
    $user_id = $user['id'];
    
    // Verify webhook belongs to user
    $stmt = $conn->prepare("SELECT id FROM discord_webhooks WHERE id = :id AND user_id = :user_id");
    $stmt->bindParam(':id', $data['webhook_id']);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Webhook not found or does not belong to you']);
        exit;
    }
    
    // Send content to webhook
    $result = send_to_discord_webhook($conn, $data['webhook_id'], $data['content'], $generator_type, $character_image);
    
    // Log webhook usage for debugging
    error_log('Webhook used: ID=' . $data['webhook_id'] . ', Type=' . $generator_type . ', Content: ' . substr($data['content'], 0, 100) . '...');
    
    // Return result
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
    
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
    exit;
}
?>