<?php
/**
 * Topbar Navigation Component (New Design)
 * 
 * A minimalist topbar with hamburger menu and Discord integration
 */

// Include Discord service
require_once __DIR__ . '/../discord/discord_service_new.php';
?>
<div class="topbar">
    <div class="topbar-container">
        <!-- Discord connection button or user profile -->
        <div class="topbar-discord">
            <?php echo render_discord_user_profile(); ?>
        </div>
        
        <!-- Hamburger menu icon -->
        <div class="hamburger-menu">
            <button id="menu-toggle" class="menu-toggle" aria-label="Toggle menu">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>
        </div>
    </div>
    
    <!-- Dropdown menu with Discord options -->
    <div id="dropdown-menu" class="dropdown-menu">
        <?php if (is_discord_authenticated_new()): ?>
            <!-- Show Discord-related options when logged in -->
            <?php 
                // Determine correct path for webhook management based on current directory
                $baseDir = dirname($_SERVER['PHP_SELF']);
                $webhooksUrl = (strpos($baseDir, '/discord') === 0) ? 'webhooks_new.php' : 'discord/webhooks_new.php';
                $logoutUrl = (strpos($baseDir, '/discord') === 0) ? 'discord-logout.php' : 'discord/discord-logout.php';
            ?>
            <a href="<?php echo $webhooksUrl; ?>" class="discord-menu-item">
                <i class="fas fa-cog"></i> Configure Webhooks
            </a>
            <a href="<?php echo $logoutUrl; ?>" class="discord-menu-item">
                <i class="fas fa-sign-out-alt"></i> Disconnect Discord
            </a>
        <?php else: ?>
            <!-- Show login option when not logged in -->
            <?php 
                // Determine correct path for auth based on current directory
                $authUrl = (strpos($baseDir, '/discord') === 0) ? 'simple_auth_new.php' : 'discord/simple_auth_new.php';
            ?>
            <a href="<?php echo $authUrl; ?>" class="discord-menu-item">
                <i class="fab fa-discord"></i> Connect to Discord
            </a>
        <?php endif; ?>
    </div>
</div>

<script>
// Simple toggle for the dropdown menu
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menu-toggle');
    const dropdownMenu = document.getElementById('dropdown-menu');
    
    if (menuToggle && dropdownMenu) {
        menuToggle.addEventListener('click', function() {
            dropdownMenu.classList.toggle('active');
            menuToggle.classList.toggle('active');
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!menuToggle.contains(event.target) && !dropdownMenu.contains(event.target)) {
                dropdownMenu.classList.remove('active');
                menuToggle.classList.remove('active');
            }
        });
    }

    // Function to refresh the active webhook status
    function refreshWebhookStatus() {
        fetch('/path/to/your/api/endpoint')
            .then(response => response.json())
            .then(data => {
                // Update the UI with the new webhook status
                // Example: document.getElementById('webhook-status').innerText = data.status;
            })
            .catch(error => console.error('Error fetching webhook status:', error));
    }

    // Refresh webhook status when the page loads
    refreshWebhookStatus();
});
</script>