<?php
/**
 * Character Sheet Page
 * 
 * This is the main entry point for the character sheet feature.
 * It loads the character controller which handles data and logic,
 * then includes the view to display the character information.
 */

// Start the session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set the current page for sidebar highlighting
$current_page = 'character_sheet';

// Set base path for consistent loading
$base_path = './';

// Discord integration - safely load if available
$discord_enabled = false;
if (file_exists('discord/discord-config.php')) {
    // Try to include the Discord configuration
    try {
        require_once 'discord/discord-config.php';
        $discord_enabled = true;
    } catch (Exception $e) {
        error_log('Discord integration error: ' . $e->getMessage());
        $discord_enabled = false;
    }
}

// Set Discord authentication status
$discord_authenticated = function_exists('is_discord_authenticated') && is_discord_authenticated();

// Load the character controller - handles data and logic
require_once 'components/character_controller.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Character Sheet - The Salty Parrot</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/character_sheet.css?v=<?php echo time(); ?>">
    <?php if ($discord_enabled && file_exists('css/discord.css')): ?>
    <link rel="stylesheet" href="css/discord.css">
    <?php endif; ?>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
</head>
<body>
    <div class="app-container">
        <!-- Include the sidebar -->
        <?php include 'components/sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <main class="main-content">
            <?php 
            // Include the character sheet view component
            include 'components/character_sheet_view.php'; 
            ?>
        </main>
    </div>
    
    <footer>
        <p>The Salty Parrot is an independent production by Stuart Greenwell. It is not affiliated with Limithron LLC. It is published under the PIRATE BORG Third Party License. PIRATE BORG is ©2022 Limithron LLC.</p>
        <p>&copy; 2025 The Salty Parrot</p>
    </footer>
</body>
</html>