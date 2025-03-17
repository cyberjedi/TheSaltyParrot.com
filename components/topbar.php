<?php
/**
 * Topbar Navigation Component (New Design)
 * 
 * A minimalist topbar with hamburger menu and Discord integration
 */

// Include Discord service
require_once __DIR__ . '/../discord/discord_service.php';
?>
<div class="topbar">
    <div class="topbar-container">
        <!-- Discord connection button or user profile -->
        <div class="topbar-discord">
            <?php echo render_discord_user_profile(); ?>
        </div>
        
        <!-- Navigation buttons moved to the right -->
        <div class="topbar-nav">
            <a href="generators.php" class="btn btn-primary">
                <i class="fas fa-dice"></i> Generators
            </a>
            <a href="character_sheet.php" class="btn btn-primary">
                <i class="fas fa-scroll"></i> Character Sheet
            </a>
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
    
    <!-- Dropdown menu with Discord options and navigation -->
    <div id="dropdown-menu" class="dropdown-menu">
        <!-- Navigation Links -->
        <div class="dropdown-section">
            <h3>Navigation</h3>
            <a href="generators.php" class="discord-menu-item">
                <i class="fas fa-dice"></i> Generators
            </a>
            <a href="character_sheet.php" class="discord-menu-item">
                <i class="fas fa-scroll"></i> Character Sheet
            </a>
        </div>
        
        <!-- Discord Options -->
        <div class="dropdown-section">
            <h3>Discord</h3>
            <?php if (is_discord_authenticated()): ?>
                <!-- Show Discord-related options when logged in -->
                <?php 
                    // Determine correct path for webhook management based on current directory
                    $baseDir = dirname($_SERVER['PHP_SELF']);
                    $webhooksUrl = (strpos($baseDir, '/discord') === 0) ? 'webhooks.php' : 'discord/webhooks.php';
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
                    $authUrl = (strpos($baseDir, '/discord') === 0) ? 'simple_auth.php' : 'discord/simple_auth.php';
                ?>
                <a href="<?php echo $authUrl; ?>" class="discord-menu-item">
                    <i class="fab fa-discord"></i> Connect to Discord
                </a>
            <?php endif; ?>
        </div>
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

    // No need for AJAX refresh as the webhook status is directly rendered from PHP
});
</script>