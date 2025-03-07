<?php
// File: discord/discord_login_button.php
// This file provides a reusable Discord login button component

/**
 * Renders a Discord login button
 * 
 * @param string $size Button size (small, medium, large)
 * @param string $color Button color (dark, light)
 * @param string $text Button text (defaults to "Login with Discord")
 * @return void Outputs the HTML button
 */
function renderDiscordLoginButton($size = 'medium', $color = 'dark', $text = 'Login with Discord') {
    $sizeClass = '';
    switch ($size) {
        case 'small':
            $sizeClass = 'discord-btn-sm';
            break;
        case 'large':
            $sizeClass = 'discord-btn-lg';
            break;
        default:
            $sizeClass = 'discord-btn-md';
    }
    
    $colorClass = $color === 'light' ? 'discord-btn-light' : 'discord-btn-dark';
    
    echo '<a href="discord/discord-login.php" class="discord-btn ' . $sizeClass . ' ' . $colorClass . '">';
    echo '<i class="fab fa-discord"></i> ' . htmlspecialchars($text);
    echo '</a>';
}
?>

<?php
// File: components/discord_user_profile.php
// This file provides a component to display Discord user profile info

/**
 * Renders the Discord user profile section
 * 
 * @param array $user Discord user data
 * @return void Outputs the HTML component
 */
function renderDiscordUserProfile($user) {
    if (empty($user)) {
        return;
    }
    
    // Construct avatar URL
    $avatarUrl = $user['avatar'] 
        ? 'https://cdn.discordapp.com/avatars/' . $user['id'] . '/' . $user['avatar'] . '.png' 
        : 'https://cdn.discordapp.com/embed/avatars/0.png';
    
    // Format username
    $usernameDisplay = htmlspecialchars($user['username']);
    if (!empty($user['discriminator']) && $user['discriminator'] !== '0') {
        $usernameDisplay .= '#' . htmlspecialchars($user['discriminator']);
    }
    
    echo '<div class="discord-profile">';
    echo '<img src="' . $avatarUrl . '" alt="Discord Avatar" class="discord-avatar">';
    echo '<div class="discord-info">';
    echo '<div class="discord-username">' . $usernameDisplay . '</div>';
    echo '<div class="discord-buttons">';
    echo '<a href="discord/webhooks.php" class="discord-btn-sm discord-btn-light">Manage Webhooks</a>';
    echo '<a href="auth/discord-logout.php" class="discord-btn-sm discord-btn-light">Logout</a>';
    echo '</div>'; // End discord-buttons
    echo '</div>'; // End discord-info
    echo '</div>'; // End discord-profile
}
?>

<?php
// File: components/discord_connection_status.php
// This file provides a component to display Discord connection status

/**
 * Renders the Discord connection status in dashboard
 * 
 * @return void Outputs the HTML component
 */
function renderDiscordConnectionStatus() {
    // Check if user is logged in with Discord
    if (function_exists('is_discord_authenticated') && is_discord_authenticated()) {
        $user = $_SESSION['discord_user'];
        
        // Format avatar URL
        $avatarUrl = $user['avatar'] 
            ? 'https://cdn.discordapp.com/avatars/' . $user['id'] . '/' . $user['avatar'] . '.png' 
            : 'https://cdn.discordapp.com/embed/avatars/0.png';
        
        // Format username
        $usernameDisplay = htmlspecialchars($user['username']);
        if (!empty($user['discriminator']) && $user['discriminator'] !== '0') {
            $usernameDisplay .= '#' . htmlspecialchars($user['discriminator']);
        }
        
        echo '<div class="discord-connection-status connected">';
        echo '<div class="discord-status-content">';
        echo '<img src="' . $avatarUrl . '" alt="Discord Avatar" class="discord-status-avatar">';
        echo '<div class="discord-status-info">';
        echo '<div class="discord-status-title">Connected to Discord as <strong>' . $usernameDisplay . '</strong></div>';
        echo '<div class="discord-status-text">You can now send generated content to your Discord channels.</div>';
        echo '</div>'; // End discord-status-info
        echo '</div>'; // End discord-status-content
        echo '<div class="discord-status-actions">';
        echo '<a href="discord/webhooks.php" class="btn btn-secondary btn-sm">Manage Webhooks</a>';
        echo '<a href="auth/discord-logout.php" class="btn btn-secondary btn-sm">Disconnect</a>';
        echo '</div>'; // End discord-status-actions
        echo '</div>'; // End discord-connection-status
    } else {
        // Not connected - show login button
        echo '<div class="discord-connection-status not-connected">';
        echo '<div class="discord-status-content">';
        echo '<i class="fab fa-discord discord-status-icon"></i>';
        echo '<div class="discord-status-info">';
        echo '<div class="discord-status-title">Connect with Discord</div>';
        echo '<div class="discord-status-text">Connect your Discord account to send generated content directly to your Discord channels.</div>';
        echo '</div>'; // End discord-status-info
        echo '</div>'; // End discord-status-content
        echo '<div class="discord-status-actions">';
        echo '<a href="discord/discord-login.php" class="btn btn-primary btn-sm"><i class="fab fa-discord"></i> Connect with Discord</a>';
        echo '</div>'; // End discord-status-actions
        echo '</div>'; // End discord-connection-status
    }
    
    // Display any pending messages
    if (isset($_SESSION['discord_error'])) {
        echo '<div class="discord-message error">' . htmlspecialchars($_SESSION['discord_error']) . '</div>';
        unset($_SESSION['discord_error']);
    }
    
    if (isset($_SESSION['discord_success'])) {
        echo '<div class="discord-message success">' . htmlspecialchars($_SESSION['discord_success']) . '</div>';
        unset($_SESSION['discord_success']);
    }
    
    if (isset($_SESSION['discord_warning'])) {
        echo '<div class="discord-message warning">' . htmlspecialchars($_SESSION['discord_warning']) . '</div>';
        unset($_SESSION['discord_warning']);
    }
    
    if (isset($_SESSION['discord_message'])) {
        echo '<div class="discord-message info">' . htmlspecialchars($_SESSION['discord_message']) . '</div>';
        unset($_SESSION['discord_message']);
    }
}
?>
