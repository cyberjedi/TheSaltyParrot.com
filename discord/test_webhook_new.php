<?php
/**
 * Test Webhook Endpoint for New UI
 * 
 * Handles sending test messages to a webhook
 */

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once 'discord_service_new.php';
require_once 'webhook_service_new.php';

// Check if user is authenticated
if (!is_discord_authenticated_new()) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated with Discord']);
    exit;
}

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

// Initialize webhook service
$webhookService = createWebhookServiceNew();

// Send test message
$result = $webhookService->sendTestMessage($data['webhook_id']);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($result);
exit;
?>