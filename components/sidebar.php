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
    
    <!-- Navigation Links -->
    <div class="sidebar-section">
        <h3>Main Menu</h3>
        <a href="<?php echo $base_path; ?>index.php" class="sidebar-btn <?php echo ($current_page == 'index' || $current_page == 'dashboard') ? 'active' : ''; ?>">
            <i class="fas fa-home"></i> Home
        </a>
    </div>
    
    <div class="sidebar-section">
        <h3>Generators</h3>
        <button id="ship-generator-btn" class="sidebar-btn">
            <i class="fas fa-ship"></i> Ship Generator
        </button>
        <button id="loot-generator-btn" class="sidebar-btn">
            <i class="fas fa-coins"></i> Loot Generator
        </button>
        <button id="dice-roller-btn" class="sidebar-btn">
            <i class="fas fa-dice-d20"></i> Dice Roller
        </button>
        <button id="npc-generator-btn" class="sidebar-btn">
            <i class="fas fa-user-friends"></i> NPC Generator
        </button>
        <button id="treasure-generator-btn" class="sidebar-btn">
            <i class="fas fa-gem"></i> Treasure Generator
        </button>
    </div>
    
    <div class="sidebar-section">
        <h3>Character</h3>
        <button id="character-creator-btn" class="sidebar-btn">
            <i class="fas fa-user-plus"></i> Create Character
        </button>
        <button id="character-list-btn" class="sidebar-btn">
            <i class="fas fa-users"></i> My Characters
        </button>
    </div>
    
    <div class="sidebar-discord-status">
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
                    </div>
                    <div class="discord-actions">
                        <a href="<?php echo $base_path; ?>discord/webhooks.php" class="discord-action-btn" title="Discord Settings">
                            <i class="fas fa-cog"></i>
                        </a>
                        <a href="<?php echo $base_path; ?>discord/discord-logout.php" class="discord-action-btn" title="Disconnect Discord">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?php echo $base_path; ?>discord/discord-login.php" class="discord-connect-btn">
                    <i class="fab fa-discord"></i> Connect Discord
                </a>
            <?php endif; ?>
        <?php else: ?>
            <div class="discord-status-disabled">
                <i class="fab fa-discord"></i>
                <span>Discord Coming Soon</span>
            </div>
        <?php endif; ?>
    </div>


</aside>

<script>
    // Add event listeners for sidebar generator buttons
    document.addEventListener('DOMContentLoaded', function() {
        // Check if we're on the index/dashboard page where the window.Generators object exists
        const isMainPage = window.location.pathname.endsWith('index.php') || 
                         window.location.pathname.endsWith('/') || 
                         window.location.pathname.endsWith('dashboard.php');
        
        // Ship Generator
        const shipGeneratorBtn = document.getElementById('ship-generator-btn');
        if (shipGeneratorBtn) {
            shipGeneratorBtn.addEventListener('click', function() {
                if (isMainPage && window.Generators) {
                    window.Generators.generateShip();
                } else {
                    window.location.href = '<?php echo $base_path; ?>index.php?generator=ship';
                }
            });
        }
        
        // Loot Generator
        const lootGeneratorBtn = document.getElementById('loot-generator-btn');
        if (lootGeneratorBtn) {
            lootGeneratorBtn.addEventListener('click', function() {
                if (isMainPage && window.Generators) {
                    window.Generators.generateLoot();
                } else {
                    window.location.href = '<?php echo $base_path; ?>index.php?generator=loot';
                }
            });
        }
        
        // Dice Roller
        const diceRollerBtn = document.getElementById('dice-roller-btn');
        if (diceRollerBtn) {
            diceRollerBtn.addEventListener('click', function() {
                if (isMainPage && window.Generators) {
                    window.Generators.diceRoller();
                } else {
                    window.location.href = '<?php echo $base_path; ?>index.php?generator=dice';
                }
            });
        }
        
        // NPC Generator
        const npcGeneratorBtn = document.getElementById('npc-generator-btn');
        if (npcGeneratorBtn) {
            npcGeneratorBtn.addEventListener('click', function() {
                if (isMainPage && window.Generators) {
                    window.Generators.npcGenerator();
                } else {
                    window.location.href = '<?php echo $base_path; ?>index.php?generator=npc';
                }
            });
        }
        
        // Treasure Generator
        const treasureGeneratorBtn = document.getElementById('treasure-generator-btn');
        if (treasureGeneratorBtn) {
            treasureGeneratorBtn.addEventListener('click', function() {
                if (isMainPage && window.Generators) {
                    window.Generators.treasureGenerator();
                } else {
                    window.location.href = '<?php echo $base_path; ?>index.php?generator=treasure';
                }
            });
        }
        
        // Character Creator
        const characterCreatorBtn = document.getElementById('character-creator-btn');
        if (characterCreatorBtn) {
            characterCreatorBtn.addEventListener('click', function() {
                alert("Character creator coming soon!");
            });
        }
        
        // Character List
        const characterListBtn = document.getElementById('character-list-btn');
        if (characterListBtn) {
            characterListBtn.addEventListener('click', function() {
                alert("Character list coming soon!");
            });
        }
    });
</script>
