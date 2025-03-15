<?php
// File: api/send_webhook.php
// Centralized API endpoint for Discord webhook operations

require_once '../discord/discord-config.php';
require_once '../config/db_connect.php';
require_once '../discord/webhook_service.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in with Discord
if (!is_discord_authenticated()) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Not authenticated with Discord'
    ]);
    exit;
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Invalid JSON data'
    ]);
    exit;
}

// Initialize webhook service
$webhookService = new WebhookService($conn);

// Process based on content type
try {
    $webhook_id = $data['webhook_id'] ?? null;
    $content_type = $data['content_type'] ?? 'formatted';
    $source = $data['source'] ?? 'custom';
    
    // Validate webhook ID
    if (!$webhook_id) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Missing webhook ID'
        ]);
        exit;
    }
    
    // Process based on content type
    switch ($content_type) {
        case 'simple':
            // Send simple text message
            if (!isset($data['message']) || empty($data['message'])) {
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Missing message content'
                ]);
                exit;
            }
            
            $result = $webhookService->sendMessage(
                $webhook_id,
                $data['message'],
                $source
            );
            break;
            
        case 'embed':
            // Send embed message
            if (!isset($data['embed']) || !is_array($data['embed'])) {
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Missing or invalid embed data'
                ]);
                exit;
            }
            
            $result = $webhookService->sendEmbed(
                $webhook_id,
                $data['embed'],
                $source
            );
            break;
            
        case 'custom':
            // Send custom payload
            if (!isset($data['payload']) || !is_array($data['payload'])) {
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Missing or invalid payload data'
                ]);
                exit;
            }
            
            $result = $webhookService->sendCustomPayload(
                $webhook_id,
                $data['payload'],
                $source,
                $data['summary'] ?? 'Custom payload'
            );
            break;
            
        case 'formatted':
        default:
            // Send formatted content (HTML)
            if (!isset($data['content']) || empty($data['content'])) {
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Missing content'
                ]);
                exit;
            }
            
            $result = $webhookService->sendFormattedContent(
                $webhook_id,
                $data['content'],
                $source,
                $data['character_image'] ?? null
            );
            break;
    }
    
    // Return result
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('API error in send_webhook.php: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'An unexpected error occurred'
    ]);
}
?>