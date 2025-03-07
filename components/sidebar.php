<?php
// Determine the active page
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Determine base path (different for pages in subdirectories)
function getBasePath() {
    return strpos($_SERVER['PHP_SELF'], '/pages/') !== false ? '../' : './';
}
$base_path = getBasePath();

// Check if Discord is enabled and user is authenticated
$discord_enabled = false;
$discord_authenticated = false;
if (file_exists($base_path . 'discord/discord-config.php')) {
    try {
        require_once $base_path . 'discord/discord-config.php';
        $discord_enabled = true;
        $discord_authenticated = function_exists('is_discord_authenticated') && is_discord_authenticated();
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
    
    <div class="sidebar-section">
        <h3>Resources</h3>
        <button id="rules-reference-btn" class="sidebar-btn">
            <i class="fas fa-book"></i> Rules Reference
        </button>
        <button id="treasure-maps-btn" class="sidebar-btn">
            <i class="fas fa-map"></i> Treasure Maps
        </button>
    </div>
    
    <!-- Discord connection button at the bottom -->
    <div class="sidebar-footer">
        <?php if ($discord_enabled): ?>
            <?php if ($discord_authenticated): ?>
                <a href="<?php echo $base_path; ?>discord/webhooks.php" class="discord-sidebar-btn connected">
                    <i class="fab fa-discord"></i> Manage Discord
                </a>
            <?php else: ?>
                <a href="<?php echo $base_path; ?>discord/discord-login.php" class="discord-sidebar-btn">
                    <i class="fab fa-discord"></i> Connect Discord
                </a>
            <?php endif; ?>
        <?php else: ?>
            <button class="discord-sidebar-btn disabled" disabled>
                <i class="fab fa-discord"></i> Discord Coming Soon
            </button>
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
        
        // Rules Reference
        const rulesReferenceBtn = document.getElementById('rules-reference-btn');
        if (rulesReferenceBtn) {
            rulesReferenceBtn.addEventListener('click', function() {
                alert("Rules reference coming soon!");
            });
        }
        
        // Treasure Maps
        const treasureMapsBtn = document.getElementById('treasure-maps-btn');
        if (treasureMapsBtn) {
            treasureMapsBtn.addEventListener('click', function() {
                alert("Treasure maps coming soon!");
            });
        }
    });
</script>
