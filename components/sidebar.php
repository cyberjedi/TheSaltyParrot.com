<!-- components/sidebar.php -->
<?php
// Determine the active page
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-skull-crossbones"></i>
        <h2>Navigation</h2>
    </div>
    
    <!-- Auth Section -->
    <div id="auth-section" class="sidebar-section">
        <!-- Login button will be dynamically inserted here by auth.js -->
        <button id="login-btn" class="sidebar-btn">
            <i class="fas fa-sign-in-alt"></i> Login
        </button>
    </div>
    
    <!-- Navigation Links (disabled initially) -->
    <div class="sidebar-section">
        <h3>Tools</h3>
        <button class="sidebar-btn disabled" data-page="dice_roller">
            <i class="fas fa-dice-d20"></i> Dice Roller (Coming Soon)
        </button>
        <button class="sidebar-btn disabled" data-page="character_manager">
            <i class="fas fa-user"></i> Character Manager (Coming Soon)
        </button>
    </div>
    
    <div class="sidebar-section">
        <h3>Reference</h3>
        <button id="ship-generator-btn" class="sidebar-btn <?php echo ($current_page == 'ship_generator') ? 'active' : ''; ?>" onclick="window.location.href='<?php echo getBasePath(); ?>pages/ship_generator.php';">
            <i class="fas fa-ship"></i> Ship Generator
        </button>
        <button class="sidebar-btn disabled" data-page="loot_generator">
            <i class="fas fa-coins"></i> Loot Generator (Coming Soon)
        </button>
    </div>
</aside>

<script>
// Helper function to determine base path
function getBasePath() {
    // Check if we're in a subdir
    if (window.location.pathname.includes('/pages/')) {
        return '../';
    } else {
        return './';
    }
}

// Make sure other sidebar buttons navigate properly
document.addEventListener('DOMContentLoaded', function() {
    const navButtons = document.querySelectorAll('.sidebar-btn:not(.disabled):not([onclick])');
    navButtons.forEach(button => {
        const page = button.getAttribute('data-page');
        if (page) {
            button.addEventListener('click', function() {
                window.location.href = getBasePath() + 'pages/' + page + '.php';
            });
        }
    });
});
</script>
