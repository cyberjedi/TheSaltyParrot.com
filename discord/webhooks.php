<?php
// File: discord/webhooks.php
// This file displays the webhook management interface

require_once 'discord-config.php';
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
        header('Location: discord-logout.php');
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
                    
                    $webhook_id = $response['id'];
                    $webhook_token = $response['token'];
                        
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->bindParam(':server_id', $selectedGuild);
                    $stmt->bindParam(':channel_id', $selectedChannel);
                    $stmt->bindParam(':channel_name', $channelName);
                    $stmt->bindParam(':webhook_id', $webhook_id);
                    $stmt->bindParam(':webhook_token', $webhook_token);
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
            $webhook_id = $_POST['webhook_id'];
            $stmt = $conn->prepare("SELECT webhook_id, webhook_token, channel_name FROM discord_webhooks WHERE id = :id AND user_id = :user_id");
            $stmt->bindParam(':id', $webhook_id);
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
                $stmt->bindParam(':id', $webhook_id);
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
            
            <!-- Webhook Guide Section -->
            <div class="webhook-guide">
                <h2>Setting Up Discord Webhooks: A User Guide</h2>
                
                <div class="guide-section">
                    <h3><i class="fas fa-key"></i> Prerequisites</h3>
                    <ul>
                        <li>You must have the <strong>"Manage Webhooks"</strong> permission on your Discord server to create webhooks</li>
                        <li>Only servers where you have appropriate permissions will appear in the dropdown menu</li>
                        <li>You need to be logged in with Discord to create and manage webhooks</li>
                    </ul>
                </div>
                
                <div class="guide-section">
                    <h3><i class="fas fa-list-ol"></i> Step-by-Step Guide</h3>
                    <ol>
                        <li><strong>Select a Server</strong> - Choose the Discord server where you want to send content</li>
                        <li><strong>Choose a Channel</strong> - Select the specific text channel that will receive messages</li>
                        <li><strong>Name Your Webhook</strong> - By default, it will be "The Salty Parrot" but you can customize this</li>
                        <li><strong>Create the Webhook</strong> - Click the "Create Webhook" button to set it up</li>
                        <li><strong>Test the Connection</strong> - Use the "Test" button to verify the webhook works</li>
                    </ol>
                </div>
                
                <div class="guide-section">
                    <h3><i class="fas fa-info-circle"></i> How It Works</h3>
                    <p>After creating a webhook, you'll be able to:</p>
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
                        <li><strong>No servers visible?</strong> You need "Manage Webhooks" permission on the server</li>
                        <li><strong>No channels visible?</strong> Make sure there are text channels in your selected server</li>
                        <li><strong>Test message fails?</strong> The webhook may have been deleted from Discord's side</li>
                        <li><strong>Other issues?</strong> Try logging out and back in to refresh your Discord connection</li>
                    </ul>
                </div>
                
                <div class="guide-section">
                    <h3><i class="fas fa-shield-alt"></i> Privacy & Security</h3>
                    <p>The Salty Parrot only stores the minimum information needed to send messages to your Discord channels:</p>
                    <ul>
                        <li>We never store your Discord password</li>
                        <li>Webhook tokens are securely stored and only used to send messages</li>
                        <li>You can delete webhooks at any time from this page</li>
                        <li>You can also delete webhooks directly from Discord's Integrations settings</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
    
    <footer>
        <p>The Salty Parrot is an independent production by Stuart Greenwell. It is not affiliated with Limithron LLC. It is published under the PIRATE BORG Third Party License. PIRATE BORG is ©2022 Limithron LLC.</p>
        <p>&copy; 2025 The Salty Parrot</p>
    </footer>

    <style>
        /* Additional styles specific to the webhook guide */
        .webhook-guide {
            margin-top: 40px;
            padding: 20px;
            background-color: var(--dark);
            border: 1px solid rgba(191, 157, 97, 0.3);
            border-radius: 8px;
        }
        
        .webhook-guide h2 {
            color: var(--secondary);
            border-bottom: 1px solid rgba(191, 157, 97, 0.3);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .guide-section {
            margin-bottom: 25px;
        }
        
        .guide-section h3 {
            color: var(--secondary);
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        
        .guide-section h3 i {
            margin-right: 8px;
        }
        
        .guide-section ul, .guide-section ol {
            padding-left: 25px;
            margin-bottom: 15px;
        }
        
        .guide-section li {
            margin-bottom: 8px;
        }
        
        .guide-section strong {
            color: var(--secondary);
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Webhook page loaded');
        });
        
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
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Data received:', data);
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
