<?php
// File: discord/webhooks.php
// This file displays the webhook management interface

// Include required files
require_once 'discord-config.php';
require_once '../config/db_connect.php';

// Handle AJAX requests for webhooks
if (isset($_GET['action']) && $_GET['action'] === 'get_webhooks' && isset($_GET['format']) && $_GET['format'] === 'json') {
    // Check if user is logged in with Discord
    if (!is_discord_authenticated()) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Not authenticated with Discord']);
        exit;
    }
    
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
        
        // Get user's webhooks
        $stmt = $conn->prepare("SELECT id, webhook_name, channel_name, guild_name, is_default FROM discord_webhooks WHERE user_id = :user_id AND is_active = 1 ORDER BY is_default DESC, last_updated DESC");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'webhooks' => $webhooks]);
        exit;
        
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
        exit;
    }
}

// Check if user is logged in with Discord for regular page access
if (!is_discord_authenticated()) {
    $_SESSION['discord_error'] = 'You need to log in with Discord first.';
    header('Location: ../index.php');
    exit;
}

// Force a token refresh to ensure fresh permissions
if (!force_discord_token_refresh()) {
    // If token refresh fails, redirect to re-authenticate
    $_SESSION['discord_error'] = 'Your Discord session has expired. Please log in again.';
    header('Location: discord-login.php');
    exit;
}

// Initialize variables
$guilds = [];
$webhooks = [];
$selectedGuild = '';
$selectedChannel = '';
$webhookName = 'The Salty Parrot';
$message = '';
$messageType = '';

// Process webhook actions if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_webhook') {
        // Validate inputs
        if (empty($_POST['webhook_url'])) {
            $message = 'Please enter a Discord webhook URL.';
            $messageType = 'error';
        } else {
            // Parse the webhook URL to extract ID and token
            $webhookUrl = trim($_POST['webhook_url']);
            $webhook_name = !empty($_POST['webhook_name']) ? $_POST['webhook_name'] : 'The Salty Parrot';
            $webhook_description = !empty($_POST['webhook_description']) ? $_POST['webhook_description'] : '';
            
            // Match Discord webhook URL pattern (https://discord.com/api/webhooks/ID/TOKEN)
            if (preg_match('#https?://(?:canary\.|ptb\.)?discord(?:app)?\.com/api/webhooks/(\d+)/([a-zA-Z0-9_-]+)#', $webhookUrl, $matches)) {
                $webhook_id = $matches[1];
                $webhook_token = $matches[2];
                
                // Verify the webhook by making a GET request to Discord
                $url = DISCORD_API_URL . "/webhooks/{$webhook_id}/{$webhook_token}";
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                
                $response_json = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                $response = json_decode($response_json, true);
                
                if ($http_code === 200 && isset($response['id']) && isset($response['token'])) {
                    // Extract channel and guild information from the response
                    // Use user-provided channel name if available, otherwise try to get from response
                    $channelName = !empty($_POST['channel_name']) ? $_POST['channel_name'] : 
                                  (isset($response['channel']['name']) ? $response['channel']['name'] : 'Unknown Channel');
                    $channelId = isset($response['channel_id']) ? $response['channel_id'] : '';
                    $guildId = isset($response['guild_id']) ? $response['guild_id'] : '';
                    $guildName = isset($response['guild']['name']) ? $response['guild']['name'] : 'Unknown Server';
                    
                    try {
                        // Generate a unique sharing code for this webhook
                        $sharing_code = substr(md5($webhook_id . $webhook_token . time()), 0, 10);
                        
                        // Insert webhook into database
                        $stmt = $conn->prepare("INSERT INTO discord_webhooks 
                            (user_id, server_id, channel_id, channel_name, webhook_id, webhook_token, webhook_name, webhook_description, 
                             sharing_code, is_shared, created_at, last_updated) 
                            VALUES 
                            (:user_id, :server_id, :channel_id, :channel_name, :webhook_id, :webhook_token, :webhook_name, :webhook_description,
                             :sharing_code, 0, NOW(), NOW())");
                        
                        $user_id = $_SESSION['discord_user']['id'];
                            
                        $stmt->bindParam(':user_id', $user_id);
                        $stmt->bindParam(':server_id', $guildId);
                        $stmt->bindParam(':channel_id', $channelId);
                        $stmt->bindParam(':channel_name', $channelName);
                        $stmt->bindParam(':webhook_id', $webhook_id);
                        $stmt->bindParam(':webhook_token', $webhook_token);
                        $stmt->bindParam(':webhook_name', $webhook_name);
                        $stmt->bindParam(':webhook_description', $webhook_description);
                        $stmt->bindParam(':sharing_code', $sharing_code);
                        
                        // Get the database user ID based on Discord ID
                        try {
                            $userStmt = $conn->prepare("SELECT id FROM discord_users WHERE discord_id = :discord_id");
                            $discord_id = $_SESSION['discord_user']['id'];
                            $userStmt->bindParam(':discord_id', $discord_id);
                            $userStmt->execute();
                            $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
                            
                            if (!$userData) {
                                throw new Exception('User not found in database');
                            }
                            
                            $user_id = $userData['id'];
                            $stmt->bindParam(':user_id', $user_id);
                            
                            $stmt->execute();
                            
                            $message = "Webhook successfully added for #{$channelName}!";
                            $messageType = 'success';
                            
                            // Clear form
                            $webhookName = 'The Salty Parrot';
                        } catch (Exception $userEx) {
                            error_log('User lookup error: ' . $userEx->getMessage());
                            $message = 'Error finding your user account. Please try logging out and back in.';
                            $messageType = 'error';
                        }
                    } catch (PDOException $e) {
                        error_log('Database error: ' . $e->getMessage());
                        $message = 'Error saving webhook to database.';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Invalid webhook URL or the webhook no longer exists.';
                    $messageType = 'error';
                }
            } else {
                $message = 'Invalid Discord webhook URL format. Please check and try again.';
                $messageType = 'error';
            }
        }
    }
    // Handle importing a shared webhook with a code
    elseif (isset($_POST['action']) && $_POST['action'] === 'import_webhook' && isset($_POST['sharing_code'])) {
        $sharing_code = trim($_POST['sharing_code']);
        
        try {
            // Look up the webhook by sharing code
            $stmt = $conn->prepare("SELECT * FROM discord_webhooks WHERE sharing_code = :sharing_code");
            $stmt->bindParam(':sharing_code', $sharing_code);
            $stmt->execute();
            $shared_webhook = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($shared_webhook) {
                // Check if user already has this webhook
                $user_id = $_SESSION['discord_user']['id'];
                $webhook_id = $shared_webhook['webhook_id'];
                $webhook_token = $shared_webhook['webhook_token'];
                
                $stmt = $conn->prepare("SELECT id FROM discord_webhooks WHERE user_id = :user_id AND webhook_id = :webhook_id");
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':webhook_id', $webhook_id);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $message = 'You already have this webhook added to your account.';
                    $messageType = 'info';
                } else {
                    // Verify the webhook still exists by making a GET request to Discord
                    $url = DISCORD_API_URL . "/webhooks/{$webhook_id}/{$webhook_token}";
                    
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    
                    $response_json = curl_exec($ch);
                    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    if ($http_code === 200) {
                        // Clone the webhook for this user
                        $stmt = $conn->prepare("INSERT INTO discord_webhooks 
                            (user_id, server_id, channel_id, channel_name, webhook_id, webhook_token, webhook_name, webhook_description,
                             sharing_code, is_shared, created_at, last_updated) 
                            VALUES 
                            (:user_id, :server_id, :channel_id, :channel_name, :webhook_id, :webhook_token, :webhook_name, :webhook_description,
                             :sharing_code, 1, NOW(), NOW())");
                             
                        // Get the database user ID based on Discord ID
                        try {
                            $userStmt = $conn->prepare("SELECT id FROM discord_users WHERE discord_id = :discord_id");
                            $discord_id = $_SESSION['discord_user']['id'];
                            $userStmt->bindParam(':discord_id', $discord_id);
                            $userStmt->execute();
                            $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
                            
                            if (!$userData) {
                                throw new Exception('User not found in database');
                            }
                            
                            $user_id = $userData['id'];
                            $stmt->bindParam(':user_id', $user_id);
                            $stmt->bindParam(':server_id', $shared_webhook['server_id']);
                            $stmt->bindParam(':channel_id', $shared_webhook['channel_id']);
                            $stmt->bindParam(':channel_name', $shared_webhook['channel_name']);
                            $stmt->bindParam(':webhook_id', $webhook_id);
                            $stmt->bindParam(':webhook_token', $webhook_token);
                            $stmt->bindParam(':webhook_name', $shared_webhook['webhook_name'] . ' (Shared)');
                            $stmt->bindParam(':webhook_description', $shared_webhook['webhook_description']);
                            $stmt->bindParam(':sharing_code', $sharing_code);
                            
                            $stmt->execute();
                            
                            $message = "Shared webhook successfully imported!";
                            $messageType = 'success';
                        } catch (Exception $userEx) {
                            error_log('User lookup error: ' . $userEx->getMessage());
                            $message = 'Error finding your user account. Please try logging out and back in.';
                            $messageType = 'error';
                        }
                    } else {
                        $message = 'This webhook no longer exists or is invalid.';
                        $messageType = 'error';
                    }
                }
            } else {
                $message = 'Invalid sharing code. Please check and try again.';
                $messageType = 'error';
            }
        } catch (PDOException $e) {
            error_log('Database error: ' . $e->getMessage());
            $message = 'Error importing webhook.';
            $messageType = 'error';
        }
    }
    // Handle setting a webhook as default
    elseif (isset($_POST['action']) && $_POST['action'] === 'set_default' && isset($_POST['webhook_id'])) {
        try {
            $webhook_id = $_POST['webhook_id'];
            
            // Get the database user ID based on Discord ID
            $userStmt = $conn->prepare("SELECT id FROM discord_users WHERE discord_id = :discord_id");
            $discord_id = $_SESSION['discord_user']['id'];
            $userStmt->bindParam(':discord_id', $discord_id);
            $userStmt->execute();
            $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$userData) {
                throw new Exception('User not found in database');
            }
            
            $user_id = $userData['id'];
            
            // First, unset all defaults for this user
            $stmt = $conn->prepare("UPDATE discord_webhooks SET is_default = 0 WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            // Then set the selected webhook as default
            $stmt = $conn->prepare("UPDATE discord_webhooks SET is_default = 1 WHERE id = :id AND user_id = :user_id");
            $stmt->bindParam(':id', $webhook_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $message = "Default webhook has been updated.";
            $messageType = 'success';
        } catch (Exception $e) {
            error_log('Default webhook error: ' . $e->getMessage());
            $message = 'Error setting default webhook.';
            $messageType = 'error';
        }
    }
    // Handle editing a webhook
    elseif (isset($_POST['action']) && $_POST['action'] === 'edit_webhook' && isset($_POST['webhook_id'])) {
        try {
            // Get the database user ID based on Discord ID
            $userStmt = $conn->prepare("SELECT id FROM discord_users WHERE discord_id = :discord_id");
            $discord_id = $_SESSION['discord_user']['id'];
            $userStmt->bindParam(':discord_id', $discord_id);
            $userStmt->execute();
            $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$userData) {
                throw new Exception('User not found in database');
            }
            
            $user_id = $userData['id'];
            $webhook_id = $_POST['webhook_id'];
            
            // Update the webhook details
            $stmt = $conn->prepare("UPDATE discord_webhooks SET 
                webhook_name = :webhook_name,
                webhook_description = :webhook_description,
                channel_name = :channel_name,
                last_updated = NOW()
                WHERE id = :id AND user_id = :user_id");
                
            $webhook_name = $_POST['webhook_name'];
            $webhook_description = $_POST['webhook_description'];
            $channel_name = $_POST['channel_name'];
            
            $stmt->bindParam(':webhook_name', $webhook_name);
            $stmt->bindParam(':webhook_description', $webhook_description);
            $stmt->bindParam(':channel_name', $channel_name);
            $stmt->bindParam(':id', $webhook_id);
            $stmt->bindParam(':user_id', $user_id);
            
            $stmt->execute();
            
            $message = "Webhook has been updated.";
            $messageType = 'success';
        } catch (Exception $e) {
            error_log('Edit webhook error: ' . $e->getMessage());
            $message = 'Error updating webhook.';
            $messageType = 'error';
        }
    }
    // Handle deleting a webhook
    elseif (isset($_POST['action']) && $_POST['action'] === 'delete_webhook' && isset($_POST['webhook_id'])) {
        try {
            // Get the database user ID based on Discord ID
            $userStmt = $conn->prepare("SELECT id FROM discord_users WHERE discord_id = :discord_id");
            $discord_id = $_SESSION['discord_user']['id'];
            $userStmt->bindParam(':discord_id', $discord_id);
            $userStmt->execute();
            $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$userData) {
                throw new Exception('User not found in database');
            }
            
            $user_id = $userData['id'];
            
            // Get webhook details first
            $webhook_id = $_POST['webhook_id'];
            $stmt = $conn->prepare("SELECT webhook_id, webhook_token, channel_name, is_shared FROM discord_webhooks WHERE id = :id AND user_id = :user_id");
            $stmt->bindParam(':id', $webhook_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $webhook = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($webhook) {
                // If this is not a shared webhook, delete it from Discord too
                if (!$webhook['is_shared']) {
                    // Delete from Discord first
                    $url = DISCORD_API_URL . "/webhooks/{$webhook['webhook_id']}/{$webhook['webhook_token']}";
                    
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    
                    curl_exec($ch);
                    curl_close($ch);
                }
                
                // Delete from database
                $stmt = $conn->prepare("DELETE FROM discord_webhooks WHERE id = :id AND user_id = :user_id");
                $stmt->bindParam(':id', $webhook_id);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                
                $message = "Webhook for #{$webhook['channel_name']} has been deleted.";
                $messageType = 'success';
            } else {
                $message = 'Webhook not found or you do not have permission to delete it.';
                $messageType = 'error';
            }
        } catch (Exception $e) {
            error_log('Delete webhook error: ' . $e->getMessage());
            $message = 'Error deleting webhook.';
            $messageType = 'error';
        }
    }
}

// Fetch user's guilds directly from Discord API
$url = DISCORD_API_URL . '/users/@me/guilds';
$headers = [
    'Authorization: Bearer ' . $_SESSION['discord_access_token'],
    'Content-Type: application/json'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$guilds_response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code >= 200 && $http_code < 300) {
    $guildResponse = json_decode($guilds_response, true);
    
    if (is_array($guildResponse)) {
        $guilds = $guildResponse;
    } else {
        $message = 'Failed to parse Discord servers response.';
        $messageType = 'error';
    }
} else {
    $message = 'Failed to fetch your Discord servers (HTTP ' . $http_code . ').';
    $messageType = 'error';
    error_log('Discord guilds error: ' . $guilds_response);
}

// Fetch user's existing webhooks from database
try {
    // Get the database user ID based on Discord ID
    $userStmt = $conn->prepare("SELECT id FROM discord_users WHERE discord_id = :discord_id");
    $discord_id = $_SESSION['discord_user']['id'];
    $userStmt->bindParam(':discord_id', $discord_id);
    $userStmt->execute();
    $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        throw new Exception('User not found in database');
    }
    
    $user_id = $userData['id'];
    
    // Fetch webhooks using database user ID
    $stmt = $conn->prepare("SELECT * FROM discord_webhooks WHERE user_id = :user_id ORDER BY is_default DESC, last_updated DESC");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if user has a default webhook
    $has_default = false;
    foreach ($webhooks as $webhook) {
        if ($webhook['is_default']) {
            $has_default = true;
            break;
        }
    }
    
    // If no default webhook is set, set the first one as default
    if (!$has_default && count($webhooks) > 0) {
        $default_id = $webhooks[0]['id'];
        $stmt = $conn->prepare("UPDATE discord_webhooks SET is_default = 1 WHERE id = :id AND user_id = :user_id");
        $stmt->bindParam(':id', $default_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        // Refresh webhooks list
        $stmt = $conn->prepare("SELECT * FROM discord_webhooks WHERE user_id = :user_id ORDER BY is_default DESC, last_updated DESC");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log('Fetch webhooks error: ' . $e->getMessage());
    $message = 'Error fetching your webhooks. Try logging out and back in.';
    $messageType = 'error';
}

// Prepare page title
$page_title = 'Manage Discord Webhooks';

// Define a base path for assets that accounts for being in a subdirectory
$base_path = '../';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - The Salty Parrot</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>css/styles.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>css/sidebar.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>css/discord.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>css/webhooks.css">
    <link rel="icon" href="<?php echo $base_path; ?>favicon.ico" type="image/x-icon">
</head>
<body>
    <div class="app-container">
        <!-- Include the sidebar with proper base path -->
        <?php 
        // Define base_path for the sidebar component
        $GLOBALS['base_path'] = $base_path;
        include '../components/sidebar.php'; 
        ?>
        
        <!-- Main Content Area -->
        <main class="main-content">
            <div class="dashboard-header">
                <div class="logo">
                    <i class="fab fa-discord"></i>
                    <h1><?php echo $page_title; ?></h1>
                </div>
                <a href="../index.php" class="btn btn-secondary">Back to Dashboard</a>
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
                <div class="discord-message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Debug section removed for production -->
            
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
                                            
                                            <button onclick="testWebhook(<?php echo $webhook['id']; ?>)" class="btn-icon" title="Test Webhook">
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
                            Add a webhook created in Discord. You'll need to create the webhook in your Discord server first.
                        </p>
                        <form method="post" action="">
                            <input type="hidden" name="action" value="add_webhook">
                            
                            <div class="form-group">
                                <label for="webhook_url">Discord Webhook URL:</label>
                                <input type="text" id="webhook_url" name="webhook_url" required 
                                       placeholder="https://discord.com/api/webhooks/...">
                                <div class="input-help">
                                    Paste the full webhook URL from Discord
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="webhook_name">Webhook Name (optional):</label>
                                <input type="text" id="webhook_name" name="webhook_name" 
                                       placeholder="The Salty Parrot" value="<?php echo htmlspecialchars($webhookName); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="webhook_description">Description (optional):</label>
                                <input type="text" id="webhook_description" name="webhook_description" 
                                       placeholder="My campaign webhook">
                            </div>
                            
                            <div class="form-group">
                                <label for="channel_name">Channel Name:</label>
                                <input type="text" id="channel_name" name="channel_name" required
                                       placeholder="general">
                                <div class="input-help">
                                    Enter the Discord channel name without the # symbol
                                </div>
                            </div>
                            
                            <div class="webhook-buttons">
                                <button type="submit" class="btn btn-primary">Add Webhook</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div id="tab-import-content" class="tab-content">
                    <div class="webhook-form">
                        <h3>Import Shared Webhook</h3>
                        <p class="form-help">
                            Import a webhook shared by another user using their sharing code.
                        </p>
                        <form method="post" action="">
                            <input type="hidden" name="action" value="import_webhook">
                            
                            <div class="form-group">
                                <label for="sharing_code">Sharing Code:</label>
                                <input type="text" id="sharing_code" name="sharing_code" required 
                                       placeholder="Enter webhook sharing code">
                            </div>
                            
                            <div class="webhook-buttons">
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
                                        <li>On this page, paste the webhook URL into the "Discord Webhook URL" field</li>
                                        <li>Give it a name to help you identify it (optional)</li>
                                        <li>Add a description if desired</li>
                                        <li>Click "Add Webhook" to save it</li>
                                    </ul>
                                </li>
                                <li><strong>Test the Connection</strong> - Use the "Test" button to verify the webhook works</li>
                            </ol>
                        </div>
                        
                        <div class="guide-section">
                            <h3><i class="fas fa-share-alt"></i> Sharing Webhooks</h3>
                            <p>You can share your webhooks with other users of The Salty Parrot:</p>
                            <ol>
                                <li>Find the sharing code for your webhook in the table above</li>
                                <li>Copy the code and send it to other players in your group</li>
                                <li>They can import your webhook using the "Import Shared Webhook" tab</li>
                                <li>This allows your entire group to send content to the same Discord channel</li>
                            </ol>
                        </div>
                        
                        <div class="guide-section">
                            <h3><i class="fas fa-info-circle"></i> How It Works</h3>
                            <p>After adding a webhook, you'll be able to:</p>
                            <ul>
                                <li>Generate ships, loot, and other content on The Salty Parrot</li>
                                <li>Send the generated content directly to your Discord server with a single click</li>
                                <li>Share your creations with your gaming group without copy-pasting</li>
                            </ul>
                            <p>Webhooks only allow The Salty Parrot to <em>send</em> messages to your server. The app cannot read messages or access any other Discord data.</p>
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
        </main>
    </div>
    
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
            console.log('Webhook page loaded');
            
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
        
    function fetchChannels() {
        const guildSelect = document.getElementById('guild_id');
        const channelSelect = document.getElementById('channel_id');
        
        // Reset channel select
        channelSelect.innerHTML = '<option value="">-- Loading channels... --</option>';
        channelSelect.disabled = true;
        
        const guildId = guildSelect.value;
        
        if (!guildId) {
            channelSelect.innerHTML = '<option value="">-- Select a server first --</option>';
            return;
        }
        
        // Add timestamp to prevent caching
        const timestamp = new Date().getTime();
        
        // Fetch channels via AJAX
        fetch(`get_channels.php?guild_id=${guildId}&t=${timestamp}`)
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Data received:', data);
                
                // Handle specific error cases
                if (data.status === 'error') {
                    console.error('Error fetching Discord channels:', data.message);
                    
                    // Let user enter their own channel ID and name if we can't fetch them
                    if (data.http_code === 401 || data.http_code === 403) {
                        // Show a custom input form instead of the dropdown
                        const formGroup = channelSelect.parentElement;
                        
                        // Create a container for custom inputs
                        const customInputs = document.createElement('div');
                        customInputs.className = 'custom-channel-inputs';
                        customInputs.style.marginTop = '10px';
                        
                        // Add explanation text
                        const explanation = document.createElement('p');
                        explanation.className = 'discord-message info';
                        explanation.style.fontSize = '14px';
                        explanation.style.padding = '10px';
                        explanation.style.marginBottom = '15px';
                        explanation.innerHTML = `
                            <strong>We can't automatically fetch your Discord channels.</strong><br>
                            Please enter your channel details manually:<br>
                            1. Go to your Discord server<br>
                            2. Right-click on the text channel you want to use<br>
                            3. Select "Copy ID" (you may need to enable Developer Mode in Discord settings)<br>
                            4. Paste the ID below and provide a channel name
                        `;
                        
                        // Create input for channel ID
                        const idInput = document.createElement('input');
                        idInput.type = 'text';
                        idInput.id = 'custom_channel_id';
                        idInput.name = 'channel_id';
                        idInput.placeholder = 'Paste Discord Channel ID here';
                        idInput.style.width = '100%';
                        idInput.style.marginBottom = '10px';
                        idInput.required = true;
                        
                        // Create input for channel name (for display only)
                        const nameInput = document.createElement('input');
                        nameInput.type = 'text';
                        nameInput.id = 'custom_channel_name';
                        nameInput.name = 'channel_name';
                        nameInput.placeholder = 'Enter channel name (e.g., general)';
                        nameInput.style.width = '100%';
                        
                        // Replace the select element with our custom inputs
                        formGroup.innerHTML = '<label for="custom_channel_id">Enter Discord Channel:</label>';
                        formGroup.appendChild(customInputs);
                        customInputs.appendChild(explanation);
                        customInputs.appendChild(idInput);
                        customInputs.appendChild(nameInput);
                        
                        return;
                    }
                }
                
                channelSelect.innerHTML = '<option value="">-- Select a channel --</option>';
                
                if ((data.status === 'success' || data.note === 'Using fallback channel list due to permission limitations') && 
                    data.channels && data.channels.length > 0) {
                    
                    // Sort channels by name
                    data.channels.sort((a, b) => a.name.localeCompare(b.name));
                    
                    // Add text channels to select
                    data.channels.forEach(channel => {
                        const option = document.createElement('option');
                        option.value = channel.id;
                        option.textContent = `#${channel.name}`;
                        channelSelect.appendChild(option);
                    });
                    
                    channelSelect.disabled = false;
                } else {
                    channelSelect.innerHTML = '<option value="">-- No text channels available --</option>';
                }
            })
            .catch(error => {
                console.error('Error fetching channels:', error);
                channelSelect.innerHTML = '<option value="">-- Error loading channels --</option>';
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
                    alert('Error sending test message. Check console for details.');
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
