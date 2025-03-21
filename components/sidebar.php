<?php
// File: components/sidebar.php

// Determine the active page
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Determine base path (different for pages in subdirectories)
function getBasePath() {
    // Check if base_path is already set from the parent script
    if (isset($GLOBALS['base_path'])) {
        return $GLOBALS['base_path'];
    }
    
    return strpos($_SERVER['PHP_SELF'], '/discord/') !== false ? '../' : './';
}
$base_path = getBasePath();

// Check if Discord is enabled and user is authenticated
$discord_enabled = false;
$discord_authenticated = false;
$discord_user = null;
if (file_exists($base_path . 'discord/discord-config.php')) {
    try {
        require_once $base_path . 'discord/discord-config.php';
        $discord_enabled = true;
        $discord_authenticated = function_exists('is_discord_authenticated') && is_discord_authenticated();
        
        // Get user info if authenticated
        if ($discord_authenticated && isset($_SESSION['discord_user'])) {
            $discord_user = $_SESSION['discord_user'];
        }
    } catch (Exception $e) {
        error_log('Discord integration error in sidebar: ' . $e->getMessage());
    }
}
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <img src="<?php echo $base_path; ?>assets/TSP_Logo_3inch.svg" alt="The Salty Parrot" height="40">
        <h2>The Salty Parrot</h2>
    </div>
    
    <!-- Discord Connection Status - Moved to top -->
    <?php if ($discord_enabled): ?>
        <?php if ($discord_authenticated && $discord_user): ?>
            <div class="discord-status-connected">
                <?php
                // Get Discord user avatar
                $avatarUrl = isset($discord_user['avatar']) && !empty($discord_user['avatar']) 
                    ? 'https://cdn.discordapp.com/avatars/' . $discord_user['id'] . '/' . $discord_user['avatar'] . '.png' 
                    : $base_path . 'assets/discord-default-avatar.png';
                
                // Get username
                $username = isset($discord_user['username']) ? $discord_user['username'] : 'User';
                ?>
                <div class="discord-user-info">
                    <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Discord Avatar" class="discord-avatar">
                    <div class="discord-username"><?php echo htmlspecialchars($username); ?></div>
                    <div class="discord-connection-label">Connected</div>
                    <?php
                    // Try to get the default webhook
                    $default_webhook_name = '';
                    $default_channel_name = '';
                    try {
                        require_once $base_path . 'config/db_connect.php';
                        
                        // Get the database user ID based on Discord ID
                        $userStmt = $conn->prepare("SELECT id FROM discord_users WHERE discord_id = :discord_id");
                        $discord_id = $discord_user['id'];
                        $userStmt->bindParam(':discord_id', $discord_id);
                        $userStmt->execute();
                        $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($userData) {
                            $user_id = $userData['id'];
                            
                            // Get default webhook
                            $webhookStmt = $conn->prepare("SELECT webhook_name, channel_name FROM discord_webhooks WHERE user_id = :user_id AND is_default = 1 LIMIT 1");
                            $webhookStmt->bindParam(':user_id', $user_id);
                            $webhookStmt->execute();
                            $webhook = $webhookStmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($webhook) {
                                $default_webhook_name = $webhook['webhook_name'];
                                $default_channel_name = $webhook['channel_name'];
                            }
                        }
                    } catch (Exception $e) {
                        // Silently fail
                    }
                    
                    if (!empty($default_webhook_name)) {
                        echo '<div class="active-webhook">
                            <span class="webhook-indicator"><i class="fas fa-link"></i></span>
                            <div class="webhook-server">' . htmlspecialchars($default_webhook_name) . '</div>
                            <div class="channel-name">#' . htmlspecialchars($default_channel_name) . '</div>
                        </div>';
                    }
                    ?>
                </div>
                <div class="discord-actions">
                    <a href="javascript:void(0);" onclick="window.location.href='<?php echo $base_path; ?>discord/webhooks.php';" class="discord-action-btn" title="Discord Settings">
                        <i class="fas fa-cog"></i>
                    </a>
                    <a href="javascript:void(0);" onclick="window.location.href='<?php echo $base_path; ?>discord/discord-logout.php';" class="discord-action-btn" title="Disconnect Discord">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <a href="javascript:void(0);" onclick="openDiscordAuthPopup(event);" class="discord-connect-btn" id="discord-login-btn">
                <i class="fab fa-discord"></i> Connect Discord
            </a>
            <script>
            // Global variable to track the popup
            let discordAuthWindow = null;

            // Function to open the Discord auth in a popup
            function openDiscordAuthPopup(event) {
                event.preventDefault();
                
                // Close any existing popup
                if (discordAuthWindow && !discordAuthWindow.closed) {
                    discordAuthWindow.close();
                }
                
                // Configure the popup
                const popupWidth = 600;
                const popupHeight = 800;
                const left = (window.innerWidth - popupWidth) / 2 + window.screenX;
                const top = (window.innerHeight - popupHeight) / 2 + window.screenY;
                
                // Open the Discord auth directly in the popup with proper size and position
                discordAuthWindow = window.open(
                    '<?php echo $base_path; ?>discord/discord-direct-popup.php',
                    'DiscordAuth',
                    `width=${popupWidth},height=${popupHeight},left=${left},top=${top},resizable=yes,scrollbars=yes`
                );

                // Focus the popup
                if (discordAuthWindow) {
                    discordAuthWindow.focus();
                    
                    // Set up an interval to check if the popup is closed
                    const checkPopupInterval = setInterval(() => {
                        if (discordAuthWindow.closed) {
                            clearInterval(checkPopupInterval);
                            // Reload the parent page to reflect auth changes
                            window.location.reload();
                        }
                    }, 500);
                } else {
                    alert('Please allow popups for this site to use Discord authentication.');
                }
            }
            </script>
        <?php endif; ?>
    <?php else: ?>
        <div class="discord-status-disabled">
            <i class="fab fa-discord"></i>
            <span>Discord Coming Soon</span>
        </div>
    <?php endif; ?>
    
    <!-- Add more space between Discord buttons and navigation -->
    <div style="margin-bottom: 25px;"></div>
    
    <!-- Navigation Links -->
    <div class="sidebar-section">
        <h3>Main Menu</h3>
        <a href="<?php echo $base_path; ?>index.php" class="sidebar-btn <?php echo ($current_page == 'index' || $current_page == 'dashboard') ? 'active' : ''; ?>">
            <i class="fas fa-dice"></i> Generators
        </a>
        <a href="<?php echo $base_path; ?>character_sheet.php" class="sidebar-btn <?php echo ($current_page == 'character_sheet') ? 'active' : ''; ?>">
            <i class="fas fa-scroll"></i> Character Sheet
        </a>
    </div>
    
    <!-- Generators section removed as requested -->
    

</aside>

<!-- Generator button event listeners removed as they're now handled in the main page -->
