<?php
// File: discord/test_webhook.php
// This file tests a webhook by sending a test message

// Enable error reporting for troubleshooting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'discord-config.php';
require_once '../config/db_connect.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Check if webhook ID is provided
if (!isset($data['webhook_id']) || empty($data['webhook_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Webhook ID is required']);
    exit;
}

// Check if user is logged in
if (!is_discord_authenticated()) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

// Get Discord user ID
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
    $webhook_id = $data['webhook_id'];
    
    // Get webhook details
    $stmt = $conn->prepare("SELECT * FROM discord_webhooks WHERE id = :id AND user_id = :user_id");
    $stmt->bindParam(':id', $webhook_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $webhook = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$webhook) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Webhook not found or does not belong to you']);
        exit;
    }
    
    // Prepare test message
    $message = [
        'content' => null,
        'embeds' => [
            [
                'title' => '🧪 Test Message from The Salty Parrot',
                'description' => 'This is a test message to verify your webhook is working correctly. You can now send generated content from The Salty Parrot to this Discord channel!',
                'color' => 0xbf9d61, // Hex color in decimal (--secondary color)
                'footer' => [
                    'text' => 'The Salty Parrot - A Pirate Borg Toolbox'
                ],
                'timestamp' => date('c')
            ]
        ]
    ];
    
    // Send message to webhook
    $url = "https://discord.com/api/webhooks/{$webhook['webhook_id']}/{$webhook['webhook_token']}";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Log webhook usage
    $content_summary = 'Test message';
    $generator_type = 'test';
    
    $stmt = $conn->prepare("INSERT INTO discord_webhook_logs 
        (webhook_id, user_id, generator_type, content_summary, status_code, is_success, request_timestamp, response_timestamp) 
        VALUES 
        (:webhook_id, :user_id, :generator_type, :content_summary, :status_code, :is_success, NOW(), NOW())");
    
    $stmt->bindParam(':webhook_id', $webhook_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':generator_type', $generator_type);
    $stmt->bindParam(':content_summary', $content_summary);
    $stmt->bindParam(':status_code', $http_code);
    $is_success = ($http_code >= 200 && $http_code < 300) ? 1 : 0;
    $stmt->bindParam(':is_success', $is_success);
    
    $stmt->execute();
    
    // Check response status
    if ($http_code >= 200 && $http_code < 300) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Test message sent successfully']);
    } else {
        // Log error message
        $error_data = json_decode($response, true);
        $error_message = isset($error_data['message']) ? $error_data['message'] : 'Unknown error';
        
        // Update webhook log with error
        $stmt = $conn->prepare("UPDATE discord_webhook_logs SET error_message = :error_message WHERE webhook_id = :webhook_id AND user_id = :user_id ORDER BY id DESC LIMIT 1");
        $error_message_param = $error_message;
        $stmt->bindParam(':error_message', $error_message_param);
        $stmt->bindParam(':webhook_id', $webhook_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Discord API error: ' . $error_message]);
    }
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}
?>
