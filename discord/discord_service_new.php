<?php
/**
 * Discord Service for New UI
 * Provides helper functions for the new interface's Discord integration
 */

// Reuse existing Discord configuration
require_once 'discord-config.php';

/**
 * Check if user is authenticated with Discord
 * 
 * @return bool True if authenticated
 */
function is_discord_authenticated_new() {
    return is_discord_authenticated();
}

/**
 * Get Discord user information
 * 
 * @return array|null User data or null if not authenticated
 */
function get_discord_user_new() {
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
function get_default_webhook_new() {
    if (!is_discord_authenticated()) {
        return null;
    }
    
    try {
        require_once '../config/db_connect.php';
        
        // Get Discord user ID from session
        $discord_id = $_SESSION['discord_user']['id'] ?? null;
        if (!$discord_id) {
            return null;
        }
        
        // Get webhook from database
        $stmt = $conn->prepare("
            SELECT id, server_name, channel_name 
            FROM discord_webhooks 
            WHERE user_id = (SELECT id FROM discord_users WHERE discord_id = :discord_id)
            AND is_default = 1
            LIMIT 1
        ");
        $stmt->bindParam(':discord_id', $discord_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Error fetching default webhook: ' . $e->getMessage());
        return null;
    }
}

/**
 * Render Discord connect button
 * 
 * @return string HTML for button
 */
function render_discord_connect_button() {
    $button = '<a href="discord/discord-login_new.php" class="discord-connect-button">';
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
    $user = get_discord_user_new();
    if (!$user) {
        return render_discord_connect_button();
    }
    
    $avatar_url = get_discord_avatar_url($user);
    $username = get_discord_username($user);
    
    // Get default webhook info
    $webhook = get_default_webhook_new();
    $webhook_info = '';
    
    if ($webhook) {
        $webhook_info = '<div class="discord-server">';
        $webhook_info .= '<span class="discord-status connected"></span>';
        $webhook_info .= htmlspecialchars($webhook['server_name']) . ' / #' . htmlspecialchars($webhook['channel_name']);
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