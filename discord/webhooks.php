<?php
// At the very top of the file - enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1: Starting webhooks.php<br>";

// Log PHP version and loaded extensions for troubleshooting
echo "PHP Version: " . phpversion() . "<br>";
echo "Loaded Extensions: " . implode(', ', get_loaded_extensions()) . "<br>";

// Show current directory and file info
echo "Current Directory: " . __DIR__ . "<br>";
echo "Current File: " . __FILE__ . "<br>";

// Try to include required files
echo "Step 2: Including required files<br>";

try {
    if (file_exists('discord-config.php')) {
        require_once 'discord-config.php';
        echo "discord-config.php included successfully<br>";
    } else {
        echo "Error: discord-config.php not found<br>";
        exit;
    }
    
    if (file_exists('../config/db_connect.php')) {
        require_once '../config/db_connect.php';
        echo "db_connect.php included successfully<br>";
    } else {
        echo "Error: ../config/db_connect.php not found<br>";
        exit;
    }
} catch (Exception $e) {
    echo "Error including files: " . $e->getMessage() . "<br>";
    exit;
}

// Check if connection is established
echo "Step 3: Checking database connection<br>";
if (isset($conn) && $conn instanceof PDO) {
    echo "Database connection is established<br>";
} else {
    echo "Error: Database connection not established<br>";
    exit;
}

// Check for Discord authentication
echo "Step 4: Checking Discord authentication<br>";
if (function_exists('is_discord_authenticated')) {
    echo "is_discord_authenticated function exists<br>";
    if (is_discord_authenticated()) {
        echo "User is authenticated with Discord<br>";
    } else {
        echo "User is NOT authenticated with Discord<br>";
        $_SESSION['discord_error'] = 'You need to log in with Discord first.';
        echo "Redirecting to index.php...";
        echo '<script>setTimeout(function() { window.location.href = "../index.php"; }, 5000);</script>';
        exit;
    }
} else {
    echo "Error: is_discord_authenticated function does not exist<br>";
    exit;
}

// Refresh token if needed
echo "Step 5: Checking if token refresh is needed<br>";
if (function_exists('refresh_discord_token_if_needed')) {
    $token_refreshed = refresh_discord_token_if_needed();
    echo "Token refresh result: " . ($token_refreshed ? "Success" : "Failed or not needed") . "<br>";
    
    if (!$token_refreshed) {
        echo "Token refresh failed - redirecting to index.php<br>";
        $_SESSION['discord_error'] = 'Your Discord session has expired. Please log in again.';
        echo '<script>setTimeout(function() { window.location.href = "../index.php"; }, 5000);</script>';
        exit;
    }
} else {
    echo "Warning: refresh_discord_token_if_needed function does not exist<br>";
}

// Get user's Discord ID
echo "Step 6: Getting user's Discord ID<br>";
if (isset($_SESSION['discord_user']['id'])) {
    $discord_id = $_SESSION['discord_user']['id'];
    echo "Discord ID found: " . $discord_id . "<br>";
} else {
    echo "Error: Discord ID not found in session<br>";
    exit;
}

// Continue with the original code, but wrap in try/catch blocks
echo "Step 7: Fetching user from database<br>";
try {
    $stmt = $conn->prepare("SELECT id FROM discord_users WHERE discord_id = :discord_id");
    $stmt->bindParam(':discord_id', $discord_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "Error: User not found in database<br>";
        $_SESSION['discord_error'] = 'User not found in database. Please log in again.';
        echo '<script>setTimeout(function() { window.location.href = "../auth/discord-logout.php"; }, 5000);</script>';
        exit;
    }
    
    $user_id = $user['id'];
    echo "User ID from database: " . $user_id . "<br>";
} catch (PDOException $e) {
    echo "Database error when fetching user: " . $e->getMessage() . "<br>";
    error_log('Database error: ' . $e->getMessage());
    $_SESSION['discord_error'] = 'Database error. Please try again later.';
    echo '<script>setTimeout(function() { window.location.href = "../index.php"; }, 5000);</script>';
    exit;
}

// Continue with rest of the code...
echo "Step 8: Setting up page variables<br>";

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
    echo "Processing POST request...<br>";
    // ... continue with the original form processing code
}

// Fetch user's guilds from Discord API
echo "Step 9: Fetching user's Discord guilds<br>";
$guildResponse = discord_api_request('/users/@me/guilds', 'GET', [], $_SESSION['discord_access_token']);

if (is_array($guildResponse)) {
    $guilds = $guildResponse;
    echo "Successfully fetched " . count($guilds) . " guilds<br>";
} else {
    echo "Error: Failed to fetch Discord servers<br>";
    $message = 'Failed to fetch your Discord servers.';
    $messageType = 'error';
}

// Fetch user's existing webhooks from database
echo "Step 10: Fetching existing webhooks<br>";
try {
    $stmt = $conn->prepare("SELECT * FROM discord_webhooks WHERE user_id = :user_id ORDER BY last_updated DESC");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Successfully fetched " . count($webhooks) . " webhooks<br>";
} catch (PDOException $e) {
    echo "Database error when fetching webhooks: " . $e->getMessage() . "<br>";
    error_log('Database error: ' . $e->getMessage());
    $message = 'Error fetching your webhooks.';
    $messageType = 'error';
}

// At this point, display the page content
echo "Step 11: Displaying page content<br>";

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
        <?php 
        try {
            if (file_exists('../components/sidebar.php')) {
                include '../components/sidebar.php';
                echo "<!-- Sidebar included successfully -->";
            } else {
                echo "<!-- Sidebar file not found -->";
            }
        } catch (Exception $e) {
            echo "<!-- Error including sidebar: " . $e->getMessage() . " -->";
        }
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
        </main>
    </div>
    
    <footer>
        <p>The Salty Parrot is an independent production by Stuart Greenwell. It is not affiliated with Limithron LLC. It is published under the PIRATE BORG Third Party License. PIRATE BORG is ©2022 Limithron LLC.</p>
        <p>&copy; 2025 The Salty Parrot</p>
    </footer>

    <script>
        // Function to fetch channels for selected guild
        function fetchChannels() {
            console.log('fetchChannels called');
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
            console.log('Fetching channels for guild ID:', guildId);
            fetch(`get_channels.php?guild_id=${guildId}`)
                .then(response => {
                    console.log('Response received:', response);
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
            console.log('testWebhook called for ID:', webhookId);
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
                .then(response => {
                    console.log('Test webhook response:', response);
                    return response.json();
                })
                .then(data => {
                    console.log('Test webhook data:', data);
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
