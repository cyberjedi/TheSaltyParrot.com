<?php
// File: discord/webhooks.php
// This file displays the webhook management interface

require_once '../auth/discord-config.php';
require_once '../config/db_connect.php';

// Check if user is logged in with Discord
if (!is_discord_authenticated()) {
    $_SESSION['discord_error'] = 'You need to log in with Discord first.';
    header('Location: ../index.php');
    exit;
}

// Refresh token if needed
if (!refresh_discord_token_if_needed()) {
    $_SESSION['discord_error'] = 'Your Discord session has expired. Please log in again.';
    header('Location: ../index.php');
    exit;
}

// Get user's Discord ID
$discord_id = $_SESSION['discord_user']['id'];

// Fetch user ID from database
try {
    $stmt = $conn->prepare("SELECT id FROM discord_users WHERE discord_id = :discord_id");
    $stmt->bindParam(':discord_id', $discord_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['discord_error'] = 'User not found in database. Please log in again.';
        header('Location: ../auth/discord-logout.php');
        exit;
    }
    
    $user_id = $user['id'];
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    $_SESSION['discord_error'] = 'Database error. Please try again later.';
    header('Location: ../index.php');
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

// Process webhook creation/deletion if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'create_webhook') {
        // Validate inputs
        if (empty($_POST['guild_id']) || empty($_POST['channel_id'])) {
            $message = 'Please select both a server and a channel.';
            $messageType = 'error';
        } else {
            // Get server and channel info
            $selectedGuild = $_POST['guild_id'];
            $selectedChannel = $_POST['channel_id'];
            
            // Get custom webhook name if provided
            if (!empty($_POST['webhook_name'])) {
                $webhookName = $_POST['webhook_name'];
            }
            
            // Create webhook on Discord
            $data = [
                'name' => $webhookName
            ];
            
            // Optional: Add avatar if you have a default one
            // $data['avatar'] = base64_encode(file_get_contents('../assets/discord_webhook_avatar.png'));
            
            $response = discord_api_request(
                "/guilds/{$selectedGuild}/channels/{$selectedChannel}/webhooks",
                'POST',
                $data,
                $_SESSION['discord_access_token']
            );
            
            if (isset($response['id']) && isset($response['token'])) {
                // Store webhook in database
                try {
                    // First, get channel name from API
                    $channelInfo = discord_api_request(
                        "/channels/{$selectedChannel}",
                        'GET',
                        [],
                        $_SESSION['discord_access_token']
                    );
                    
                    $channelName = isset($channelInfo['name']) ? $channelInfo['name'] : 'Unknown Channel';
                    
                    // Get server name
                    $guildName = '';
                    foreach ($guilds as $guild) {
                        if ($guild['id'] === $selectedGuild) {
                            $guildName = $guild['name'];
                            break;
                        }
                    }
                    
                    // Insert webhook
                    $stmt = $conn->prepare("INSERT INTO discord_webhooks 
                        (user_id, server_id, channel_id, channel_name, webhook_id, webhook_token, webhook_name, created_at, last_updated) 
                        VALUES 
                        (:user_id, :server_id, :channel_id, :channel_name, :webhook_id, :webhook_token, :webhook_name, NOW(), NOW())");
                        
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->bindParam(':server_id', $selectedGuild);
                    $stmt->bindParam(':channel_id', $selectedChannel);
                    $stmt->bindParam(':channel_name', $channelName);
                    $stmt->bindParam(':webhook_id', $response['id']);
                    $stmt->bindParam(':webhook_token', $response['token']);
                    $stmt->bindParam(':webhook_name', $webhookName);
                    
                    $stmt->execute();
                    
                    $message = "Webhook successfully created for #{$channelName} in {$guildName}!";
                    $messageType = 'success';
                    
                    // Clear selection
                    $selectedGuild = '';
                    $selectedChannel = '';
                    $webhookName = 'The Salty Parrot';
                } catch (PDOException $e) {
                    error_log('Database error: ' . $e->getMessage());
                    $message = 'Error saving webhook to database.';
                    $messageType = 'error';
                }
            } else {
                $message = 'Error creating webhook: ' . ($response['message'] ?? 'Unknown error');
                $messageType = 'error';
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_webhook' && isset($_POST['webhook_id'])) {
        // Delete webhook
        try {
            // Get webhook details first
            $stmt = $conn->prepare("SELECT webhook_id, webhook_token, channel_name FROM discord_webhooks WHERE id = :id AND user_id = :user_id");
            $stmt->bindParam(':id', $_POST['webhook_id']);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $webhook = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($webhook) {
                // Delete from Discord first
                $deleteResponse = discord_api_request(
                    "/webhooks/{$webhook['webhook_id']}/{$webhook['webhook_token']}",
                    'DELETE',
                    [],
                    $_SESSION['discord_access_token']
                );
                
                // Delete from database even if Discord deletion fails (webhook might already be deleted on Discord side)
                $stmt = $conn->prepare("DELETE FROM discord_webhooks WHERE id = :id AND user_id = :user_id");
                $stmt->bindParam(':id', $_POST['webhook_id']);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                
                $message = "Webhook for #{$webhook['channel_name']} has been deleted.";
                $messageType = 'success';
            } else {
                $message = 'Webhook not found or you do not have permission to delete it.';
                $messageType = 'error';
            }
        } catch (PDOException $e) {
            error_log('Database error: ' . $e->getMessage());
            $message = 'Error deleting webhook.';
            $messageType = 'error';
        }
    }
}

// Fetch user's guilds from Discord API
$guildResponse = discord_api_request('/users/@me/guilds', 'GET', [], $_SESSION['discord_access_token']);

if (is_array($guildResponse)) {
    $guilds = $guildResponse;
} else {
    $message = 'Failed to fetch your Discord servers.';
    $messageType = 'error';
}

// Fetch user's existing webhooks from database
try {
    $stmt = $conn->prepare("SELECT * FROM discord_webhooks WHERE user_id = :user_id ORDER BY last_updated DESC");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    $message = 'Error fetching your webhooks.';
    $messageType = 'error';
}

// Prepare page title
$page_title = 'Manage Discord Webhooks';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - The Salty Parrot</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/discord.css">
    <link rel="icon" href="../favicon.ico" type="image/x-icon">
</head>
<body>
    <div class="app-container">
        <!-- Include the sidebar -->
        <?php include '../components/sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <main class="main-content">
            <div class="dashboard-header">
                <div class="logo">
                    <i class="fab fa-discord"></i>
                    <h1><?php echo $page_title; ?></h1>
                </div>
                <a href="../index.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="discord-message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <div class="webhook-list">
                <h2>Your Discord Webhooks</h2>
                
                <?php if (empty($webhooks)): ?>
                    <div class="no-webhooks">
                        <p>You haven't set up any webhooks yet. Create one below to start sending content to Discord.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($webhooks as $webhook): ?>
                        <div class="webhook-item">
                            <div class="webhook-info">
                                <div class="webhook-server">Server: <?php 
                                    // Try to find the server name
                                    $serverName = 'Unknown Server';
                                    foreach ($guilds as $guild) {
                                        if ($guild['id'] === $webhook['server_id']) {
                                            $serverName = htmlspecialchars($guild['name']);
                                            break;
                                        }
                                    }
                                    echo $serverName;
                                ?></div>
                                <div class="webhook-channel">Channel: #<?php echo htmlspecialchars($webhook['channel_name']); ?></div>
                                <div class="webhook-name">Webhook: <?php echo htmlspecialchars($webhook['webhook_name']); ?></div>
                            </div>
                            <div class="webhook-actions">
                                <form method="post" action="" onsubmit="return confirm('Are you sure you want to delete this webhook?');">
                                    <input type="hidden" name="action" value="delete_webhook">
                                    <input type="hidden" name="webhook_id" value="<?php echo $webhook['id']; ?>">
                                    <button type="submit" title="Delete Webhook"><i class="fas fa-trash"></i> Delete</button>
                                </form>
                                <button onclick="testWebhook(<?php echo $webhook['id']; ?>)" title="Test Webhook"><i class="fas fa-vial"></i> Test</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="webhook-form">
                <h2 class="webhook-form-title">Create New Webhook</h2>
                <form method="post" action="">
                    <input type="hidden" name="action" value="create_webhook">
                    
                    <div class="form-group">
                        <label for="guild_id">Select Discord Server:</label>
                        <select id="guild_id" name="guild_id" required onchange="fetchChannels()">
                            <option value="">-- Select a server --</option>
                            <?php foreach ($guilds as $guild): ?>
                                <?php 
                                    // Check if user has required permissions (Manage Webhooks)
                                    // Permission flag for MANAGE_WEBHOOKS is 1 << 29 = 536870912
                                    $permissions = isset($guild['permissions']) ? intval($guild['permissions']) : 0;
                                    $canManageWebhooks = ($permissions & 536870912) === 536870912 || ($permissions & 8) === 8; // MANAGE_WEBHOOKS or ADMINISTRATOR
                                    
                                    if ($canManageWebhooks):
                                ?>
                                    <option value="<?php echo $guild['id']; ?>" <?php echo $selectedGuild === $guild['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($guild['name']); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="channel_id">Select Channel:</label>
                        <select id="channel_id" name="channel_id" required disabled>
                            <option value="">-- Select a server first --</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="webhook_name">Webhook Name (optional):</label>
                        <input type="text" id="webhook_name" name="webhook_name" placeholder="The Salty Parrot" value="<?php echo htmlspecialchars($webhookName); ?>">
                    </div>
                    
                    <div class="webhook-buttons">
                        <button type="submit" class="btn btn-primary">Create Webhook</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <footer>
        <p>The Salty Parrot is an independent production by Stuart Greenwell. It is not affiliated with Limithron LLC. It is published under the PIRATE BORG Third Party License. PIRATE BORG is ©2022 Limithron LLC.</p>
        <p>&copy; 2025 The Salty Parrot</p>
    </footer>

    <script>
        // Function to fetch channels for selected guild
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
            
            // Fetch channels via AJAX
            fetch(`get_channels.php?guild_id=${guildId}`)
                .then(response => response.json())
                .then(data => {
                    channelSelect.innerHTML = '<option value="">-- Select a channel --</option>';
                    
                    if (data.status === 'success' && data.channels && data.channels.length > 0) {
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
    </script>
</body>
</html>

<?php
// File: discord/get_channels.php
// This file fetches channels for a specific Discord guild/server

require_once '../auth/discord-config.php';

// Check if user is logged in
if (!is_discord_authenticated()) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

// Check if guild ID is provided
if (!isset($_GET['guild_id']) || empty($_GET['guild_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Guild ID is required']);
    exit;
}

// Refresh token if needed
if (!refresh_discord_token_if_needed()) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Token refresh failed']);
    exit;
}

$guild_id = $_GET['guild_id'];
$access_token = $_SESSION['discord_access_token'];

// Fetch channels from Discord API
$channels = discord_api_request('/guilds/' . $guild_id . '/channels', 'GET', [], $access_token);

if (!is_array($channels)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch channels']);
    exit;
}

// Filter to include only text channels (type 0)
$text_channels = array_filter($channels, function($channel) {
    return $channel['type'] === 0; // 0 is text channel
});

header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'channels' => array_values($text_channels) // reset array keys
]);
exit;
?>

<?php
// File: discord/test_webhook.php
// This file tests a webhook by sending a test message

require_once '../auth/discord-config.php';
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
    
    // Get webhook details
    $stmt = $conn->prepare("SELECT * FROM discord_webhooks WHERE id = :id AND user_id = :user_id");
    $stmt->bindParam(':id', $data['webhook_id']);
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
    $stmt = $conn->prepare("INSERT INTO discord_webhook_logs 
        (webhook_id, user_id, generator_type, content_summary, status_code, is_success, request_timestamp, response_timestamp) 
        VALUES 
        (:webhook_id, :user_id, :generator_type, :content_summary, :status_code, :is_success, NOW(), NOW())");
        
    $stmt->bindParam(':webhook_id', $webhook['id']);
    $stmt->bindParam(':user_id', $user_id);
    $generator_type = 'test';
    $stmt->bindParam(':generator_type', $generator_type);
    $content_summary = 'Test message';
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
        $stmt->bindParam(':error_message', $error_message);
        $stmt->bindParam(':webhook_id', $webhook['id']);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Discord API error: ' . $error_message]);
    }
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
    exit;
}
?>
