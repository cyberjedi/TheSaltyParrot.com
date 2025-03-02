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
        <h2>Navigation</h2>
    </div>
    
    <!-- Auth Section -->
    <div id="auth-section" class="sidebar-section">
        <!-- Login button will be dynamically inserted here by auth.js -->
        <button id="login-btn" class="sidebar-btn">
            <i class="fas fa-sign-in-alt"></i> Login
        </button>
    </div>
    
    <!-- Navigation Links -->
    <div class="sidebar-section">
        <h3>Tools</h3>
        <a href="<?php echo $base_path; ?>pages/dashboard.php" class="sidebar-btn <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="<?php echo $base_path; ?>" class="sidebar-btn <?php echo ($current_page == 'index') ? 'active' : ''; ?>">
            <i class="fas fa-home"></i> Home
        </a>
    </div>
    
    <div class="sidebar-section">
        <h3>Reference</h3>
        <a href="<?php echo $base_path; ?>pages/ship_generator.php" class="sidebar-btn <?php echo ($current_page == 'ship_generator') ? 'active' : ''; ?>">
            <i class="fas fa-ship"></i> Ship Generator
        </a>
        <button class="sidebar-btn disabled" data-page="loot_generator">
            <i class="fas fa-coins"></i> Loot Generator (Coming Soon)
        </button>
    </div>
</aside>
