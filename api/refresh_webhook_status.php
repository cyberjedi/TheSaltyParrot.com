<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../discord/webhook_service_new.php';
require_once __DIR__ . '/../config/db_connect_new.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

try {
    $webhookService = createWebhookServiceNew();
    $activeWebhook = $webhookService->getActiveWebhook();
    
    if ($activeWebhook) {
        echo json_encode([
            'status' => 'success',
            'webhook' => [
                'name' => $activeWebhook['webhook_name'],
                'channel' => $activeWebhook['channel_name']
            ]
        ]);
    } else {
        echo json_encode([
            'status' => 'no_webhook',
            'message' => 'No active webhook'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error fetching webhook status'
    ]);
}