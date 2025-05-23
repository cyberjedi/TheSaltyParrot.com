<?php
/**
 * Discord Service for New UI
 * Provides helper functions for the new interface's Discord integration
 */

// Use our new UI Discord configuration
if (file_exists(__DIR__ . '/discord-config.php')) {
    require_once __DIR__ . '/discord-config.php';
} else {
    // Fallback for when we're included from another directory
    require_once 'discord/discord-config.php';
}

/**
 * Check if user is authenticated with Discord
 * 
 * @return bool True if authenticated
 */
function is_discord_authenticated() {
    // Simple check for session variables indicating Discord authentication
    return isset($_SESSION['discord_user']) && 
           isset($_SESSION['discord_access_token']) && 
           isset($_SESSION['discord_token_expires']);
}

/**
 * Get Discord user information
 * 
 * @return array|null User data or null if not authenticated
 */
function get_discord_user() {
    if (!is_discord_authenticated()) {
        return null;
    }
    
    return $_SESSION['discord_user'] ?? null;
}

/**
 * Get Discord avatar URL for a user
 * 
 * @param array $user Discord user data
 * @return string Avatar URL
 */
function get_discord_avatar_url($user) {
    if (empty($user) || empty($user['id'])) {
        return 'https://cdn.discordapp.com/embed/avatars/0.png';
    }
    
    return $user['avatar']
        ? 'https://cdn.discordapp.com/avatars/' . $user['id'] . '/' . $user['avatar'] . '.png' 
        : 'https://cdn.discordapp.com/embed/avatars/0.png';
}

/**
 * Get formatted Discord username
 * 
 * @param array $user Discord user data
 * @return string Formatted username
 */
function get_discord_username($user) {
    if (empty($user) || empty($user['username'])) {
        return 'Unknown User';
    }
    
    $username = htmlspecialchars($user['username']);
    
    // Add discriminator if present and not zero
    if (!empty($user['discriminator']) && $user['discriminator'] !== '0') {
        $username .= '#' . htmlspecialchars($user['discriminator']);
    }
    
    return $username;
}

/**
 * Get user's default webhook server and channel
 * 
 * @return array|null Webhook data or null if none found
 */
function get_default_webhook() {
    if (!is_discord_authenticated()) {
        return null;
    }
    
    // Clear session cache if we're coming from the webhooks page to ensure fresh data
    if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'webhooks') !== false) {
        unset($_SESSION['active_webhook']);
    }
    
    // Check the database for the most current webhook setting
    try {
        // Include the database connection
        if (file_exists(__DIR__ . '/../config/db_connect.php')) {
            require_once __DIR__ . '/../config/db_connect.php';
        } else {
            require_once 'config/db_connect.php';
        }
        
        // Make sure we have a connection
        global $conn;
        if (!isset($conn)) {
            error_log('Database connection not available');
            return null;
        }
        
        // Get Discord user ID from session
        $discord_id = $_SESSION['discord_user']['id'] ?? null;
        if (!$discord_id) {
            return null;
        }
        
        // Get the user ID first
        $userStmt = $conn->prepare("SELECT id FROM discord_users WHERE discord_id = :discord_id");
        $userStmt->bindParam(':discord_id', $discord_id);
        $userStmt->execute();
        $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$userData) {
            error_log('User ID not found for Discord ID: ' . $discord_id);
            return null;
        }
        
        $user_id = $userData['id'];
        
        // Get default webhook
        $webhookStmt = $conn->prepare("SELECT id, webhook_name, channel_name, is_default, is_active, server_id 
                                      FROM discord_webhooks 
                                      WHERE user_id = :user_id AND is_default = 1 AND is_active = 1
                                      LIMIT 1");
        $webhookStmt->bindParam(':user_id', $user_id);
        $webhookStmt->execute();
        $webhook = $webhookStmt->fetch(PDO::FETCH_ASSOC);
        
        // If no default webhook, try to get the most recently updated one
        if (!$webhook) {
            $webhookStmt = $conn->prepare("SELECT id, webhook_name, channel_name, is_default, is_active, server_id
                                          FROM discord_webhooks 
                                          WHERE user_id = :user_id AND is_active = 1
                                          ORDER BY last_updated DESC LIMIT 1");
            $webhookStmt->bindParam(':user_id', $user_id);
            $webhookStmt->execute();
            $webhook = $webhookStmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Update the session with the current webhook
        if ($webhook) {
            $_SESSION['active_webhook'] = $webhook;
        } else {
            // Clear the session if no webhook found
            unset($_SESSION['active_webhook']);
        }
        
        return $webhook;
    } catch (Exception $e) {
        error_log('Error fetching default webhook: ' . $e->getMessage());
        
        // Fall back to session data in case of errors
        if (isset($_SESSION['active_webhook']) && 
            isset($_SESSION['active_webhook']['webhook_name']) && 
            isset($_SESSION['active_webhook']['channel_name'])) {
            return $_SESSION['active_webhook'];
        }
        
        return null;
    }
}

/**
 * Render Discord connect button
 * 
 * @return string HTML for button
 */
function render_discord_connect_button() {
    $button = '<a href="discord/simple_auth.php" class="discord-connect-button">';
    $button .= '<i class="fab fa-discord"></i> Connect to Discord';
    $button .= '</a>';
    
    return $button;
}

/**
 * Render Discord user profile with webhook info
 * 
 * @return string HTML for profile
 */
function render_discord_user_profile() {
    $user = get_discord_user();
    if (!$user) {
        return render_discord_connect_button();
    }
    
    $avatar_url = get_discord_avatar_url($user);
    $username = get_discord_username($user);
    
    // Use the same direct database query approach as in sidebar.php
    $default_webhook_name = '';
    $default_channel_name = '';
    
    try {
        // Include the database connection
        if (file_exists(__DIR__ . '/../config/db_connect.php')) {
            require_once __DIR__ . '/../config/db_connect.php';
        } else {
            require_once 'config/db_connect.php';
        }
        
        // Make sure we have a connection
        global $conn;
        
        // Get the database user ID based on Discord ID
        $discord_id = $user['id'];
        $userStmt = $conn->prepare("SELECT id FROM discord_users WHERE discord_id = :discord_id");
        $userStmt->bindParam(':discord_id', $discord_id);
        $userStmt->execute();
        $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userData) {
            $user_id = $userData['id'];
            
            // Get default webhook directly, just like sidebar.php
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
        // Silently fail, just like in sidebar.php
    }
    
    // If we don't have webhook information yet, attempt one more direct database query
    $active_webhook = $_SESSION['active_webhook'] ?? null;
    if (empty($default_webhook_name) || empty($default_channel_name)) {
        try {
            // Include the database connection
            if (file_exists(__DIR__ . '/../config/db_connect.php')) {
                require_once __DIR__ . '/../config/db_connect.php';
            } else {
                require_once 'config/db_connect.php';
            }
            
            global $conn;
            if (isset($conn) && isset($_SESSION['discord_user']['id'])) {
                $discord_id = $_SESSION['discord_user']['id'];
                
                // Get user ID
                $userStmt = $conn->prepare("SELECT id FROM discord_users WHERE discord_id = :discord_id");
                $userStmt->bindParam(':discord_id', $discord_id);
                $userStmt->execute();
                $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($userData) {
                    $user_id = $userData['id'];
                    
                    // Try one more direct query for the default webhook
                    $webhookStmt = $conn->prepare("SELECT id, webhook_name, channel_name, is_default, is_active, server_id
                                                  FROM discord_webhooks 
                                                  WHERE user_id = :user_id AND is_active = 1
                                                  ORDER BY is_default DESC, last_updated DESC
                                                  LIMIT 1");
                    $webhookStmt->bindParam(':user_id', $user_id);
                    $webhookStmt->execute();
                    $active_webhook = $webhookStmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Update session with this webhook
                    if ($active_webhook) {
                        $_SESSION['active_webhook'] = $active_webhook;
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Error in direct webhook query: ' . $e->getMessage());
        }
    }
    
    // Build webhook info based on what we found
    $webhook_info = '';
    if (!empty($default_webhook_name) && !empty($default_channel_name)) {
        $webhook_info = '<div class="discord-server">';
        $webhook_info .= '<span class="discord-status connected"></span>';
        $webhook_info .= htmlspecialchars($default_webhook_name) . ' / #' . htmlspecialchars($default_channel_name);
        $webhook_info .= '</div>';
    } else {
        $webhook_info = '<div class="discord-webhook-info">';
        $webhook_info .= '<span class="discord-status disconnected"></span>';
        $webhook_info .= 'No webhook configured';
        $webhook_info .= '</div>';
    }
    
    $output = '<div class="discord-profile">';
    $output .= '<img src="' . $avatar_url . '" alt="Discord Avatar" class="discord-avatar">';
    $output .= '<div class="discord-user-info">';
    $output .= '<div class="discord-username">' . $username . '</div>';
    $output .= $webhook_info;
    $output .= '</div>';
    $output .= '</div>';
    
    return $output;
}
?>