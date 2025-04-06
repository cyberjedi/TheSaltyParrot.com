<?php
/**
 * Discord Webhook API Endpoint
 * 
 * Handles AJAX requests from the account page for webhook management.
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is authenticated via Discord session
if (!isset($_SESSION['discord_user']) || !isset($_SESSION['discord_user']['id'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'User not authenticated with Discord.']);
    exit;
}

// Include the webhook service
require_once 'webhook_service.php';

// Instantiate the service (it handles DB connection internally)
try {
    $webhookService = new WebhookService();
} catch (Exception $e) {
    http_response_code(500);
    error_log("Webhook API Error - Failed to instantiate WebhookService: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Internal server error initializing webhook service.']);
    exit;
}

// Check if service has a valid user ID (set in constructor based on session)
if (!$webhookService->isUserInitialized()) {
     http_response_code(403); // Forbidden (could also be 500 if DB issue prevented lookup)
     error_log("Webhook API Error - WebhookService user ID not initialized for Discord ID: " . ($_SESSION['discord_user']['id'] ?? 'N/A'));
     echo json_encode(['status' => 'error', 'message' => 'Failed to link Discord session to user record.']);
     exit;
}

// Get the requested action
$action = $_POST['action'] ?? $_GET['action'] ?? null; // Allow GET for simple reads like get_webhooks

if (!$action) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'No action specified.']);
    exit;
}

// Process the action
$response = null;

try {
    switch ($action) {
        case 'get_webhooks':
            $webhooks = $webhookService->getUserWebhooks();
            // Add the full webhook URL for easy copying on the frontend
            foreach ($webhooks as &$hook) {
                $hook['full_url'] = "https://discord.com/api/webhooks/{$hook['webhook_id']}/{$hook['webhook_token']}";
            }
            unset($hook); // Unset reference
            $response = ['status' => 'success', 'webhooks' => $webhooks];
            break;

        case 'add_webhook':
            $webhookUrl = filter_var($_POST['webhookUrl'] ?? '', FILTER_SANITIZE_URL);
            $serverName = htmlspecialchars($_POST['serverName'] ?? 'My Server', ENT_QUOTES, 'UTF-8');
            $discordChannelName = htmlspecialchars($_POST['discordChannelName'] ?? 'Default Channel', ENT_QUOTES, 'UTF-8');

            if (empty($webhookUrl) || empty($serverName) || empty($discordChannelName)) {
                 http_response_code(400);
                 $response = ['status' => 'error', 'message' => 'Missing required fields (Webhook URL, Discord Server, Discord Channel).'];
                 break;
            }
            
            $response = $webhookService->addWebhook($webhookUrl, $serverName, $discordChannelName);
            break;

        case 'delete_webhook':
            $webhookId = filter_var($_POST['webhookId'] ?? '', FILTER_VALIDATE_INT);
            if (!$webhookId) {
                 http_response_code(400);
                 $response = ['status' => 'error', 'message' => 'Invalid Webhook ID.'];
                 break;
            }
            // Authorization check happens inside deleteWebhook
            $response = $webhookService->deleteWebhook($webhookId);
            break;
            
        case 'set_default':
            $webhookId = filter_var($_POST['webhookId'] ?? '', FILTER_VALIDATE_INT);
             if (!$webhookId) {
                 http_response_code(400);
                 $response = ['status' => 'error', 'message' => 'Invalid Webhook ID.'];
                 break;
            }
            // Authorization check happens inside setDefaultWebhook
            $response = $webhookService->setDefaultWebhook($webhookId);
            break;

        case 'test_webhook':
             $webhookId = filter_var($_POST['webhookId'] ?? '', FILTER_VALIDATE_INT);
             if (!$webhookId) {
                 http_response_code(400);
                 $response = ['status' => 'error', 'message' => 'Invalid Webhook ID.'];
                 break;
             }
             // Check if user wants to test the default webhook
             if ($webhookId === -1) { // Use -1 as a special code for 'default'
                $defaultWebhook = $webhookService->getDefaultWebhook();
                if ($defaultWebhook && isset($defaultWebhook['id'])) {
                    $webhookId = $defaultWebhook['id'];
                } else {
                    http_response_code(404);
                    $response = ['status' => 'error', 'message' => 'No default webhook found to test.'];
                    break;
                }
             }
            // Authorization check happens inside sendTestMessage
            $response = $webhookService->sendTestMessage($webhookId);
            break;

        case 'edit_webhook':
             $webhookId = filter_var($_POST['webhookId'] ?? '', FILTER_VALIDATE_INT);
             $serverName = htmlspecialchars($_POST['serverName'] ?? '', ENT_QUOTES, 'UTF-8');
             $discordChannelName = htmlspecialchars($_POST['discordChannelName'] ?? '', ENT_QUOTES, 'UTF-8');

             if (!$webhookId || empty($serverName) || empty($discordChannelName)) {
                 http_response_code(400);
                 $response = ['status' => 'error', 'message' => 'Missing required fields (Webhook ID, Discord Server, Discord Channel).'];
                 break;
             }
            // Authorization check happens inside updateWebhook
            $response = $webhookService->updateWebhook($webhookId, $serverName, $discordChannelName);
            break;

        default:
            http_response_code(400); // Bad Request
            $response = ['status' => 'error', 'message' => 'Invalid action specified.'];
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("Webhook API Error - Action '{$action}': " . $e->getMessage());
    $response = ['status' => 'error', 'message' => 'An internal server error occurred. Please try again later.'];
}

// Ensure $response is always set before encoding
if ($response === null) {
     http_response_code(500);
     error_log("Webhook API Error - Action '{$action}' resulted in null response.");
     $response = ['status' => 'error', 'message' => 'An unexpected internal error occurred.'];
}

echo json_encode($response);
exit;

// Helper method added to WebhookService class (need to add this to the class file)
/*
public function isUserInitialized() {
    return $this->userId !== null;
}
*/

?> 