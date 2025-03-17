<?php
/**
 * Webhooks Configuration Page (New UI)
 * 
 * Allows users to configure Discord webhooks for the application
 */

// Enable error reporting to diagnose issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include required files with path handling
if (file_exists(__DIR__ . '/discord-config.php')) {
    require_once __DIR__ . '/discord-config.php';
} else {
    require_once 'discord/discord-config.php';
}

if (file_exists(__DIR__ . '/../config/db_connect.php')) {
    require_once __DIR__ . '/../config/db_connect.php';
} else {
    require_once 'config/db_connect.php';
}

if (file_exists(__DIR__ . '/discord_service.php')) {
    require_once __DIR__ . '/discord_service.php';
} else {
    require_once 'discord/discord_service.php';
}

if (file_exists(__DIR__ . '/webhook_service.php')) {
    require_once __DIR__ . '/webhook_service.php';
} else {
    require_once 'discord/webhook_service.php';
}

// Redirect to login if not authenticated
if (!is_discord_authenticated()) {
    $_SESSION['discord_error'] = 'You must be logged in with Discord to manage webhooks.';
    header('Location: ../index.php');
    exit;
}

// Get current user
$user = get_discord_user();

// Initialize webhook service
try {
    $webhookService = createWebhookService();
    
    // Verify the service was created successfully
    if (!$webhookService) {
        throw new Exception('Failed to create webhook service');
    }
} catch (Exception $e) {
    // Log the error
    error_log('Error initializing webhook service: ' . $e->getMessage());
    
    // Set error message
    $message = 'There was a problem connecting to the database. Please try again later.';
    $messageType = 'error';
    
    // Create empty service object to prevent errors
    $webhookService = new stdClass();
    $webhookService->getUserWebhooks = function() { return []; };
}

// Initialize variables
$message = $message ?? '';
$messageType = $messageType ?? '';
$webhooks = [];

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && method_exists($webhookService, 'getUserWebhooks')) {
    // Add webhook form submission
    if (isset($_POST['action']) && $_POST['action'] === 'add_webhook') {
        if (empty($_POST['webhook_url'])) {
            $message = 'Please enter a Discord webhook URL.';
            $messageType = 'error';
        } else {
            if (method_exists($webhookService, 'addWebhook')) {
                $webhookUrl = trim($_POST['webhook_url']);
                $webhookName = !empty($_POST['webhook_name']) ? $_POST['webhook_name'] : 'The Salty Parrot';
                $channelName = !empty($_POST['channel_name']) ? $_POST['channel_name'] : 'general';
                $webhookDescription = !empty($_POST['webhook_description']) ? $_POST['webhook_description'] : '';
                
                $result = $webhookService->addWebhook($webhookUrl, $webhookName, $channelName, $webhookDescription);
                
                $message = $result['message'];
                $messageType = $result['status'];
            } else {
                $message = 'The webhook service is currently unavailable. Please try again later.';
                $messageType = 'error';
            }
        }
    }
    // Import shared webhook
    elseif (isset($_POST['action']) && $_POST['action'] === 'import_webhook') {
        if (empty($_POST['sharing_code'])) {
            $message = 'Please enter a webhook sharing code.';
            $messageType = 'error';
        } else {
            if (method_exists($webhookService, 'importSharedWebhook')) {
                $sharingCode = trim($_POST['sharing_code']);
                
                $result = $webhookService->importSharedWebhook($sharingCode);
                
                $message = $result['message'];
                $messageType = $result['status'];
            } else {
                $message = 'The webhook service is currently unavailable. Please try again later.';
                $messageType = 'error';
            }
        }
    }
    // Set default webhook
    elseif (isset($_POST['action']) && $_POST['action'] === 'set_default') {
        if (empty($_POST['webhook_id'])) {
            $message = 'Missing webhook ID.';
            $messageType = 'error';
        } else {
            if (method_exists($webhookService, 'setDefaultWebhook')) {
                $webhookId = $_POST['webhook_id'];
                
                $result = $webhookService->setDefaultWebhook($webhookId);
                
                $message = $result['message'];
                $messageType = $result['status'];
            } else {
                $message = 'The webhook service is currently unavailable. Please try again later.';
                $messageType = 'error';
            }
        }
    }
    // Update webhook details
    elseif (isset($_POST['action']) && $_POST['action'] === 'edit_webhook') {
        if (empty($_POST['webhook_id'])) {
            $message = 'Missing webhook ID.';
            $messageType = 'error';
        } else {
            if (method_exists($webhookService, 'updateWebhook')) {
                $webhookId = $_POST['webhook_id'];
                $webhookName = $_POST['webhook_name'];
                $channelName = $_POST['channel_name'];
                $webhookDescription = $_POST['webhook_description'] ?? '';
                
                $result = $webhookService->updateWebhook($webhookId, $webhookName, $channelName, $webhookDescription);
                
                $message = $result['message'];
                $messageType = $result['status'];
            } else {
                $message = 'The webhook service is currently unavailable. Please try again later.';
                $messageType = 'error';
            }
        }
    }
    // Delete webhook
    elseif (isset($_POST['action']) && $_POST['action'] === 'delete_webhook') {
        if (empty($_POST['webhook_id'])) {
            $message = 'Missing webhook ID.';
            $messageType = 'error';
        } else {
            if (method_exists($webhookService, 'deleteWebhook')) {
                $webhookId = $_POST['webhook_id'];
                
                $result = $webhookService->deleteWebhook($webhookId);
                
                $message = $result['message'];
                $messageType = $result['status'];
            } else {
                $message = 'The webhook service is currently unavailable. Please try again later.';
                $messageType = 'error';
            }
        }
    }
}

// Get user's Discord servers (guilds)
$guilds = []; 
if (isset($_SESSION['discord_access_token'])) {
    $url = DISCORD_API_URL . '/users/@me/guilds';
    $headers = [
        'Authorization: Bearer ' . $_SESSION['discord_access_token'],
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        $guildsData = json_decode($response, true);
        
        if (is_array($guildsData)) {
            $guilds = $guildsData;
        } else {
            $message = 'Failed to parse Discord servers response.';
            $messageType = 'error';
        }
    } else {
        $message = 'Failed to fetch your Discord servers.';
        $messageType = 'error';
    }
}

// Get user's webhooks
if (method_exists($webhookService, 'getUserWebhooks')) {
    try {
        $webhooks = $webhookService->getUserWebhooks();
    } catch (Exception $e) {
        error_log('Error getting webhooks: ' . $e->getMessage());
        $webhooks = [];
        
        if (empty($message)) {
            $message = 'There was an error fetching your webhooks. Please try again later.';
            $messageType = 'error';
        }
    }
} else {
    $webhooks = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discord Webhooks - The Salty Parrot</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/topbar.css">
    <link rel="stylesheet" href="../css/discord.css">
    <link rel="icon" href="../favicon.ico" type="image/x-icon">
    <style>
        /* Base Styles */
        body {
            background-color: var(--dark);
            color: var(--light);
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .webhook-container {
            max-width: 850px;
            margin: 0 auto;
            padding: 25px;
        }
        
        .webhook-title {
            color: var(--secondary);
            font-size: 2rem;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(191, 157, 97, 0.3);
            padding-bottom: 12px;
        }
        
        .webhook-description {
            margin-bottom: 30px;
            line-height: 1.5;
        }
        
        /* Cards and Forms */
        .webhook-card {
            background-color: var(--primary);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            border-left: 4px solid var(--secondary);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }
        
        .webhook-form {
            margin-top: 20px;
            max-width: 650px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: var(--secondary);
            font-weight: 500;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid rgba(191, 157, 97, 0.3);
            background-color: rgba(0, 0, 0, 0.3);
            color: var(--light);
            font-size: 1rem;
            transition: all 0.2s ease;
        }
        
        input:focus, select:focus, textarea:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 2px rgba(191, 157, 97, 0.25);
            outline: none;
        }
        
        textarea {
            min-height: 80px;
            resize: vertical;
        }
        
        .input-help {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.6);
            margin-top: 6px;
            padding-left: 2px;
        }
        
        /* Buttons */
        button, .btn {
            background-color: var(--secondary);
            color: var(--dark);
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s ease;
            font-size: 1rem;
        }
        
        button:hover, .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        
        .btn-primary {
            background-color: var(--secondary);
            color: var(--dark);
        }
        
        .btn-secondary {
            background-color: rgba(0, 0, 0, 0.3);
            color: var(--light);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .btn-danger {
            background-color: #d9534f;
            color: white;
        }
        
        .btn-icon {
            background: none;
            border: none;
            color: var(--secondary);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }
        
        .btn-icon.delete {
            color: #d9534f;
        }
        
        .btn-icon.test {
            color: #5865F2; /* Discord Blue */
        }
        
        .btn-icon:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }
        
        .btn-icon.delete:hover {
            background-color: rgba(244, 67, 54, 0.2);
            color: #f44336;
        }
        
        .btn-icon.test:hover {
            background-color: rgba(88, 101, 242, 0.2);
        }
        
        /* Status Messages */
        .message {
            border-radius: 8px;
            padding: 15px 18px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            position: relative;
            border-left: 5px solid transparent;
        }
        
        .message.success {
            background-color: rgba(40, 167, 69, 0.1);
            border-left-color: #28a745;
            color: #28a745;
        }
        
        .message.error {
            background-color: rgba(220, 53, 69, 0.1);
            border-left-color: #dc3545;
            color: #dc3545;
        }
        
        .message.info {
            background-color: rgba(23, 162, 184, 0.1);
            border-left-color: #17a2b8;
            color: #17a2b8;
        }
        
        /* Webhook Tables */
        .webhooks-table-container {
            overflow-x: auto;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .webhooks-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .webhooks-table th, 
        .webhooks-table td {
            text-align: left;
            padding: 14px 16px;
        }
        
        .webhooks-table th {
            background-color: rgba(0, 0, 0, 0.3);
            color: var(--secondary);
            font-weight: 600;
            position: sticky;
            top: 0;
            border-bottom: 2px solid rgba(191, 157, 97, 0.3);
        }
        
        .webhooks-table tbody tr {
            transition: background-color 0.2s ease;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .webhooks-table tbody tr:last-child {
            border-bottom: none;
        }
        
        .webhooks-table tr:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .webhooks-table tr.default-webhook {
            background-color: rgba(191, 157, 97, 0.1);
        }
        
        .webhooks-table tr.default-webhook:hover {
            background-color: rgba(191, 157, 97, 0.15);
        }
        
        .default-badge {
            display: inline-block;
            background-color: var(--secondary);
            color: var(--dark);
            padding: 3px 8px;
            border-radius: 50px;
            font-size: 0.7rem;
            margin-left: 8px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        
        .server-name {
            display: block;
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 5px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        
        .status-badge.owner {
            background-color: #2e7d32;
            color: white;
        }
        
        .status-badge.shared {
            background-color: #1565c0;
            color: white;
        }
        
        .sharing-code {
            display: flex;
            align-items: center;
        }
        
        .sharing-code code {
            background-color: rgba(0, 0, 0, 0.3);
            padding: 6px 10px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            margin-right: 8px;
            border: 1px solid rgba(191, 157, 97, 0.2);
        }
        
        .webhook-actions {
            white-space: nowrap;
            display: flex;
            gap: 5px;
            flex-wrap: nowrap;
            justify-content: flex-end;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(3px);
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background-color: var(--primary);
            margin: 8% auto;
            padding: 30px;
            border-radius: 12px;
            width: 85%;
            max-width: 550px;
            position: relative;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.4s ease;
            border: 1px solid rgba(191, 157, 97, 0.3);
        }
        
        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .close-modal {
            position: absolute;
            top: 15px;
            right: 20px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 24px;
            cursor: pointer;
            transition: all 0.2s;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .close-modal:hover {
            color: var(--secondary);
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .form-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 25px;
        }
        
        /* Tabs */
        .webhook-tabs {
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            margin: 25px 0;
        }
        
        .tab-buttons {
            display: flex;
            background-color: rgba(0, 0, 0, 0.2);
            padding: 0 5px;
        }
        
        .tab-button {
            padding: 15px 25px;
            text-align: center;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: white;
            position: relative;
            transition: all 0.2s ease;
            border-bottom: 3px solid transparent;
            opacity: 0.7;
        }
        
        .tab-button:hover {
            opacity: 1;
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .tab-button.active {
            color: var(--secondary);
            border-bottom-color: var(--secondary);
            opacity: 1;
            font-weight: 600;
        }
        
        .tab-content {
            display: none;
            padding: 25px;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        /* Back Link */
        .back-link {
            display: inline-flex;
            align-items: center;
            margin-top: 25px;
            color: var(--secondary);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 50px;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        
        .back-link:hover {
            background-color: rgba(191, 157, 97, 0.1);
            transform: translateX(-3px);
        }
        
        .back-link i {
            margin-right: 8px;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .webhook-container {
                padding: 15px;
            }
            
            .tab-buttons {
                flex-wrap: wrap;
            }
            
            .tab-button {
                padding: 12px 15px;
                flex: 1;
                text-align: center;
            }
            
            .webhooks-table th, 
            .webhooks-table td {
                padding: 10px 12px;
            }
            
            .webhook-actions {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .modal-content {
                width: 95%;
                padding: 20px;
                margin: 15% auto;
            }
            
            .active-webhook-selector {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .webhooks-table th:nth-child(3),
            .webhooks-table td:nth-child(3) {
                display: none;
            }
        }
        
        /* Help section styles */
        .webhook-guide {
            margin-top: 20px;
            padding: 25px;
            background-color: var(--dark);
            border: 1px solid rgba(191, 157, 97, 0.3);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }
        
        .webhook-guide h2 {
            color: var(--secondary);
            border-bottom: 1px solid rgba(191, 157, 97, 0.3);
            padding-bottom: 12px;
            margin-bottom: 25px;
            font-size: 1.4rem;
        }
        
        .guide-section {
            margin-bottom: 28px;
            background-color: rgba(0, 0, 0, 0.15);
            padding: 18px;
            border-radius: 8px;
            border-left: 3px solid var(--secondary);
            text-align: left;
        }
        
        .guide-section h3 {
            color: var(--secondary);
            margin-bottom: 12px;
            font-size: 1.15rem;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(191, 157, 97, 0.2);
            padding-bottom: 8px;
        }
        
        .guide-section h3 i {
            margin-right: 10px;
            font-size: 1.2em;
        }
        
        .guide-section ol,
        .guide-section ul {
            margin-top: 10px;
            padding-left: 25px;
            text-align: left;
        }
        
        .guide-section li {
            margin-bottom: 10px;
            line-height: 1.5;
            text-align: left;
        }
        
        .guide-section p {
            line-height: 1.6;
            text-align: left;
        }
        
        .guide-section strong {
            color: var(--secondary);
        }
        
        /* Empty state */
        .no-webhooks {
            text-align: center;
            padding: 40px 20px;
            background-color: rgba(0, 0, 0, 0.15);
            border-radius: 12px;
            margin: 20px 0;
        }
        
        .no-webhooks p {
            margin-bottom: 20px;
            color: rgba(255, 255, 255, 0.8);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }
        
        .active-webhook-selector {
            display: flex;
            align-items: center;
            margin: 20px 0;
            padding: 16px;
            background-color: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(191, 157, 97, 0.3);
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .default-webhook-form {
            display: flex;
            align-items: center;
            width: 100%;
        }
        
        .default-webhook-form label {
            margin-right: 12px;
            color: var(--secondary);
            font-weight: 600;
            white-space: nowrap;
        }
        
        .default-webhook-form select {
            flex-grow: 1;
            background-color: rgba(0, 0, 0, 0.3);
            color: white;
            border: 1px solid rgba(191, 157, 97, 0.5);
            padding: 10px 15px;
            border-radius: 8px;
            min-width: 250px;
            font-size: 1em;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%23BF9D61' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
            transition: all 0.2s ease;
        }
        
        .default-webhook-form select:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 2px rgba(191, 157, 97, 0.25);
            outline: none;
        }
        
        @media (max-width: 768px) {
            .active-webhook-selector {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .default-webhook-form {
                flex-direction: column;
                align-items: flex-start;
                width: 100%;
            }
            
            .default-webhook-form label {
                margin-bottom: 10px;
                margin-right: 0;
            }
            
            .default-webhook-form select {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Include the topbar with user's Discord profile -->
    <?php include '../components/topbar.php'; ?>
    
    <!-- Main Content Area -->
    <main class="main-content">
        <div class="webhook-container">
            <h1 class="webhook-title">Discord Webhook Configuration</h1>
            
            <div class="webhook-description">
                <p>Configure your Discord webhooks to send game content directly to your Discord server. Add webhooks to different channels and set your default destination for sharing.</p>
            </div>
            
            <?php if (!empty($webhooks)): ?>
            <div class="active-webhook-selector">
                <form method="post" action="" id="default-webhook-form" class="default-webhook-form">
                    <input type="hidden" name="action" value="set_default">
                    <label for="active_webhook">Active Webhook:</label>
                    <select name="webhook_id" id="active_webhook" onchange="document.getElementById('default-webhook-form').submit();">
                        <?php foreach ($webhooks as $webhook): ?>
                            <option value="<?php echo $webhook['id']; ?>" <?php echo $webhook['is_default'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($webhook['webhook_name']); ?> (#<?php echo htmlspecialchars($webhook['channel_name']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <div class="webhook-list">
                <h2>Your Discord Webhooks</h2>
                
                <?php if (empty($webhooks)): ?>
                    <div class="no-webhooks">
                        <p>You haven't set up any webhooks yet. Add one below to start sending content to Discord.</p>
                    </div>
                <?php else: ?>
                    <div class="webhooks-table-container">
                        <table class="webhooks-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Channel</th>
                                    <th>Sharing Code</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($webhooks as $webhook): ?>
                                    <tr class="<?php echo $webhook['is_default'] ? 'default-webhook' : ''; ?>">
                                        <td>
                                            <?php echo htmlspecialchars($webhook['webhook_name']); ?>
                                            <?php if ($webhook['is_default']): ?>
                                                <span class="default-badge">Default</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            #<?php echo htmlspecialchars($webhook['channel_name']); ?>
                                            <span class="server-name">
                                                <?php
                                                    // Try to find the server name
                                                    $serverName = 'Unknown Server';
                                                    foreach ($guilds as $guild) {
                                                        if ($guild['id'] === $webhook['server_id']) {
                                                            $serverName = htmlspecialchars($guild['name']);
                                                            break;
                                                        }
                                                    }
                                                    echo $serverName;
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (isset($webhook['sharing_code']) && !empty($webhook['sharing_code'])): ?>
                                                <div class="sharing-code">
                                                    <code><?php echo htmlspecialchars($webhook['sharing_code']); ?></code>
                                                    <button onclick="copyToClipboard('<?php echo htmlspecialchars($webhook['sharing_code']); ?>')" class="btn-icon" title="Copy sharing code">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <em>Not available</em>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($webhook['is_shared']): ?>
                                                <span class="status-badge shared">Shared</span>
                                            <?php else: ?>
                                                <span class="status-badge owner">Owner</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="webhook-actions">
                                            <?php if (!$webhook['is_default']): ?>
                                                <form method="post" action="" class="inline-form">
                                                    <input type="hidden" name="action" value="set_default">
                                                    <input type="hidden" name="webhook_id" value="<?php echo $webhook['id']; ?>">
                                                    <button type="submit" class="btn-icon" title="Set as Default">
                                                        <i class="fas fa-star"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <button onclick="testWebhook(<?php echo $webhook['id']; ?>)" class="btn-icon test" title="Test Webhook">
                                                <i class="fas fa-vial"></i>
                                            </button>
                                            
                                            <button onclick="editWebhook(<?php echo $webhook['id']; ?>, '<?php echo htmlspecialchars(addslashes($webhook['webhook_name'])); ?>', '<?php echo htmlspecialchars(addslashes($webhook['webhook_description'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($webhook['channel_name'])); ?>')" class="btn-icon" title="Edit Webhook">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <form method="post" action="" class="inline-form delete-form">
                                                <input type="hidden" name="action" value="delete_webhook">
                                                <input type="hidden" name="webhook_id" value="<?php echo $webhook['id']; ?>">
                                                <button type="submit" class="btn-icon delete" title="Delete Webhook">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="webhook-tabs">
                <div class="tab-buttons">
                    <button id="tab-add" class="tab-button active">Add Webhook</button>
                    <button id="tab-import" class="tab-button">Import Shared Webhook</button>
                    <button id="tab-help" class="tab-button">Help</button>
                </div>
                
                <div id="tab-add-content" class="tab-content active">
                    <div class="webhook-form">
                        <h3>Add Discord Webhook</h3>
                        <p class="form-help">
                            Add a webhook created in your Discord server. You'll need to have the "Manage Webhooks" permission in your server.
                        </p>
                        <form method="post" action="">
                            <input type="hidden" name="action" value="add_webhook">
                            
                            <div class="form-group">
                                <label for="webhook_url">Discord Webhook URL:</label>
                                <input type="text" id="webhook_url" name="webhook_url" required 
                                       placeholder="https://discord.com/api/webhooks/...">
                                <div class="input-help">
                                    Paste the full webhook URL from Discord. You can create webhooks in your server's "Integrations" settings.
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="webhook_name">Webhook Name (optional):</label>
                                <input type="text" id="webhook_name" name="webhook_name" 
                                       placeholder="The Salty Parrot" value="The Salty Parrot">
                                <div class="input-help">
                                    This is just for your reference to identify the webhook in the list
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="webhook_description">Description (optional):</label>
                                <input type="text" id="webhook_description" name="webhook_description" 
                                       placeholder="Campaign webhook for sharing content">
                            </div>
                            
                            <div class="form-group">
                                <label for="channel_name">Channel Name:</label>
                                <input type="text" id="channel_name" name="channel_name" required
                                       placeholder="general">
                                <div class="input-help">
                                    Enter the Discord channel name (without the # symbol)
                                </div>
                            </div>
                            
                            <div class="form-buttons">
                                <button type="submit" class="btn btn-primary">Add Webhook</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div id="tab-import-content" class="tab-content">
                    <div class="webhook-form">
                        <h3>Import Shared Webhook</h3>
                        <p class="form-help">
                            Import a webhook that someone has shared with you using a sharing code.
                        </p>
                        <form method="post" action="">
                            <input type="hidden" name="action" value="import_webhook">
                            
                            <div class="form-group">
                                <label for="sharing_code">Sharing Code:</label>
                                <input type="text" id="sharing_code" name="sharing_code" required 
                                       placeholder="Enter webhook sharing code">
                                <div class="input-help">
                                    Ask your game master or another player to share their webhook code with you
                                </div>
                            </div>
                            
                            <div class="form-buttons">
                                <button type="submit" class="btn btn-primary">Import Webhook</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div id="tab-help-content" class="tab-content">
                    <div class="webhook-guide">
                        <h2>Setting Up Discord Webhooks: A User Guide</h2>
                        
                        <div class="guide-section">
                            <h3><i class="fas fa-key"></i> Prerequisites</h3>
                            <ul>
                                <li>You must have the <strong>"Manage Webhooks"</strong> permission on your Discord server</li>
                                <li>You need to be logged in with Discord on both The Salty Parrot and your Discord server</li>
                            </ul>
                        </div>
                        
                        <div class="guide-section">
                            <h3><i class="fas fa-list-ol"></i> Step-by-Step Guide</h3>
                            <ol>
                                <li><strong>Create a Webhook in Discord</strong>
                                    <ul>
                                        <li>Go to your Discord server</li>
                                        <li>Click on the server name and select "Server Settings"</li>
                                        <li>Select "Integrations" from the left menu</li>
                                        <li>Click on "Webhooks" and then "New Webhook"</li>
                                        <li>Give your webhook a name (e.g., "The Salty Parrot")</li>
                                        <li>Select the channel you want messages to go to</li>
                                        <li>Click "Copy Webhook URL" to copy the full webhook URL</li>
                                    </ul>
                                </li>
                                <li><strong>Add the Webhook to The Salty Parrot</strong>
                                    <ul>
                                        <li>Paste the webhook URL into the "Discord Webhook URL" field</li>
                                        <li>Give it a name to help you identify it</li>
                                        <li>Add the channel name (without the # symbol)</li>
                                        <li>Click "Add Webhook" to save it</li>
                                    </ul>
                                </li>
                                <li><strong>Test the Connection</strong> - Use the "Test" button to verify the webhook works</li>
                            </ol>
                        </div>
                        
                        <div class="guide-section">
                            <h3><i class="fas fa-share-alt"></i> Sharing Webhooks</h3>
                            <p>You can share your webhooks with other players in your game:</p>
                            <ol>
                                <li>Find the sharing code for your webhook in the table above</li>
                                <li>Copy the code and send it to other players in your group</li>
                                <li>They can import your webhook using the "Import Shared Webhook" tab</li>
                                <li>This allows your entire group to send content to the same Discord channel</li>
                            </ol>
                        </div>
                        
                        <div class="guide-section">
                            <h3><i class="fas fa-info-circle"></i> How It Works</h3>
                            <p>Discord webhooks are a simple way to post messages to Discord from external applications. When you add a webhook to The Salty Parrot, you're creating a connection between our app and your Discord server.</p>
                            <p>After adding a webhook, you'll be able to:</p>
                            <ul>
                                <li>Send generated game content directly to your Discord server</li>
                                <li>Share game content with your group without copy-pasting</li>
                                <li>Control which server and channel receives your content</li>
                            </ul>
                            <p>Webhooks only allow our app to <em>send</em> messages to your server. The app cannot read messages or access any other Discord data.</p>
                        </div>
                        
                        <div class="guide-section">
                            <h3><i class="fas fa-exclamation-triangle"></i> Troubleshooting</h3>
                            <ul>
                                <li><strong>Invalid webhook URL?</strong> Make sure you copied the entire URL from Discord</li>
                                <li><strong>Test message fails?</strong> The webhook may have been deleted from Discord's side</li>
                                <li><strong>Sharing code doesn't work?</strong> The webhook may have been deleted or changed</li>
                                <li><strong>Other issues?</strong> Try deleting the webhook and creating a new one</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <a href="../index.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
    </main>
    
    <!-- Edit Webhook Modal -->
    <div id="edit-webhook-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h3>Edit Webhook</h3>
            
            <form method="post" action="" id="edit-webhook-form">
                <input type="hidden" name="action" value="edit_webhook">
                <input type="hidden" name="webhook_id" id="edit-webhook-id">
                
                <div class="form-group">
                    <label for="edit-webhook-name">Webhook Name:</label>
                    <input type="text" id="edit-webhook-name" name="webhook_name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit-webhook-description">Description:</label>
                    <input type="text" id="edit-webhook-description" name="webhook_description">
                </div>
                
                <div class="form-group">
                    <label for="edit-channel-name">Channel Name:</label>
                    <input type="text" id="edit-channel-name" name="channel_name" required>
                    <div class="input-help">
                        Enter the Discord channel name without the # symbol
                    </div>
                </div>
                
                <div class="form-buttons">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <button type="button" class="btn btn-secondary close-modal-btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <footer>
        <p>The Salty Parrot is an independent production by Stuart Greenwell. It is not affiliated with Limithron LLC. It is published under the PIRATE BORG Third Party License. PIRATE BORG is ©2022 Limithron LLC.</p>
        <p>&copy; 2025 The Salty Parrot</p>
    </footer>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Setup tab switching
            setupTabs();
            
            // Setup delete confirmation
            setupDeleteConfirmation();
            
            // Setup modal functionality
            setupModal();
        });
        
        // Function to handle edit webhook
        function editWebhook(webhookId, webhookName, webhookDescription, channelName) {
            // Populate the edit form
            document.getElementById('edit-webhook-id').value = webhookId;
            document.getElementById('edit-webhook-name').value = webhookName;
            document.getElementById('edit-webhook-description').value = webhookDescription || '';
            document.getElementById('edit-channel-name').value = channelName;
            
            // Show the modal
            document.getElementById('edit-webhook-modal').style.display = 'block';
        }
        
        // Function to setup modal
        function setupModal() {
            const modal = document.getElementById('edit-webhook-modal');
            const closeBtn = document.getElementsByClassName('close-modal')[0];
            const cancelBtn = document.getElementsByClassName('close-modal-btn')[0];
            
            // Close the modal when clicking the X
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    modal.style.display = 'none';
                });
            }
            
            // Close the modal when clicking the Cancel button
            if (cancelBtn) {
                cancelBtn.addEventListener('click', function() {
                    modal.style.display = 'none';
                });
            }
            
            // Close the modal when clicking outside of it
            window.addEventListener('click', function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        }
        
        // Function to test webhook
        function testWebhook(webhookId) {
            if (confirm('Send a test message to this webhook?')) {
                fetch('test_webhook.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        webhook_id: webhookId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Test message sent successfully!');
                    } else {
                        alert('Error sending test message: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error sending test message. Please try again.');
                });
            }
        }
        
        // Function to setup tabs
        function setupTabs() {
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    // Get the tab ID from the button
                    const tabId = button.id.replace('tab-', '');
                    
                    // Remove active class from all buttons and contents
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // Add active class to clicked button and corresponding content
                    button.classList.add('active');
                    document.getElementById(`tab-${tabId}-content`).classList.add('active');
                });
            });
        }
        
        // Function to setup delete confirmation
        function setupDeleteConfirmation() {
            const deleteForms = document.querySelectorAll('.delete-form');
            
            deleteForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const confirmed = confirm('Are you sure you want to delete this webhook? This cannot be undone.');
                    if (!confirmed) {
                        e.preventDefault();
                    }
                });
            });
        }
        
        // Function to copy text to clipboard
        function copyToClipboard(text) {
            // Create a temporary textarea element
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'absolute';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            
            // Copy the text
            textarea.select();
            document.execCommand('copy');
            
            // Remove the textarea
            document.body.removeChild(textarea);
            
            // Show a message
            alert(`Webhook code ${text} copied to clipboard!`);
        }
    </script>
</body>
</html>