<!-- components/sidebar.php -->
<?php
// Determine the active page
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Determine base path (different for pages in subdirectories)
function getBasePath() {
    return strpos($_SERVER['PHP_SELF'], '/pages/') !== false ? '../' : './';
}
$base_path = getBasePath();
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-skull-crossbones"></i>
        <h2>The Salty Parrot</h2>
    </div>
    
    <!-- Auth Section -->
    <div id="auth-section" class="sidebar-section">
        <!-- Login/user info will be dynamically inserted here by the auth script -->
    </div>
    
    <!-- Navigation Links -->
    <div class="sidebar-section">
        <h3>Main Menu</h3>
        <a href="<?php echo $base_path; ?>pages/dashboard.php" class="sidebar-btn <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
    </div>
    
    <div class="sidebar-section">
        <h3>Game Tools</h3>
        <a href="<?php echo $base_path; ?>pages/ship_generator.php" class="sidebar-btn <?php echo ($current_page == 'ship_generator') ? 'active' : ''; ?>">
            <i class="fas fa-ship"></i> Ship Generator
        </a>
        <a href="<?php echo $base_path; ?>pages/loot_generator.php" class="sidebar-btn <?php echo ($current_page == 'loot_generator') ? 'active' : ''; ?>">
            <i class="fas fa-coins"></i> Loot Generator
        </a>
        <a href="#" class="sidebar-btn" id="dice-roller-sidebar">
            <i class="fas fa-dice-d20"></i> Dice Roller
        </a>
        <a href="#" class="sidebar-btn" id="character-creator-sidebar">
            <i class="fas fa-user-plus"></i> Character Creator
        </a>
        <a href="#" class="sidebar-btn" id="npc-generator-sidebar">
            <i class="fas fa-user-friends"></i> NPC Generator
        </a>
    </div>
    
    <div class="sidebar-section">
        <h3>Resources</h3>
        <a href="#" class="sidebar-btn" id="rules-reference-sidebar">
            <i class="fas fa-book"></i> Rules Reference
        </a>
        <a href="#" class="sidebar-btn" id="treasure-maps-sidebar">
            <i class="fas fa-map"></i> Treasure Maps
        </a>
    </div>
</aside>

<script>
    // Add event listeners for sidebar links
    document.addEventListener('DOMContentLoaded', function() {
        const comingSoonLinks = [
            'dice-roller-sidebar',
            'character-creator-sidebar',
            'npc-generator-sidebar',
            'rules-reference-sidebar',
            'treasure-maps-sidebar'
        ];
        
        comingSoonLinks.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener('click', function(e) {
                    e.preventDefault();
                    alert("This feature is coming soon!");
                });
            }
        });
    });
</script>
