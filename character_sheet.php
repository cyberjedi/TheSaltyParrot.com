<?php
// Set the current page
$current_page = 'character_sheet';

// Discord integration - safely load if available
$discord_enabled = false;
if (file_exists('discord/discord-config.php')) {
    // Try to include the Discord configuration
    try {
        require_once 'discord/discord-config.php';
        $discord_enabled = true;
        
        // Get user ID and webhooks if authenticated
        $user_webhooks = [];
        if (function_exists('is_discord_authenticated') && is_discord_authenticated() && 
            isset($conn)) {
            
            try {
                // Get user ID
                $discord_id = $_SESSION['discord_user']['id'];
                
                // Get user ID from database
                $stmt = $conn->prepare("SELECT id FROM discord_users WHERE discord_id = :discord_id");
                $stmt->bindParam(':discord_id', $discord_id);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    $user_id = $user['id'];
                    // Include the discord_service.php file to get the get_user_webhooks function
                    if (file_exists('discord/discord_service.php')) {
                        require_once 'discord/discord_service.php';
                        $user_webhooks = get_user_webhooks($conn, $user_id);
                    }
                }
            } catch (Exception $e) {
                error_log('Discord user webhooks error: ' . $e->getMessage());
            }
        }
    } catch (Exception $e) {
        error_log('Discord integration error: ' . $e->getMessage());
        $discord_enabled = false;
    }
}
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
            <div class="dashboard-header">
                <div class="logo">
                    <i class="fas fa-scroll"></i>
                    <h1>Character Sheet</h1>
                </div>
            </div>
            
            <div class="content-container">
                <!-- Placeholder content for character sheet -->
                <div class="placeholder-display" style="text-align: center; padding: 50px 20px;">
                    <i class="fas fa-scroll" style="font-size: 4rem; color: var(--secondary); margin-bottom: 20px;"></i>
                    <h2>Character Sheet Coming Soon</h2>
                    <p>The character sheet functionality is currently under development.</p>
                </div>
            </div>
        </main>
    </div>
    
    <footer>
        <p>The Salty Parrot is an independent production by Stuart Greenwell. It is not affiliated with Limithron LLC. It is published under the PIRATE BORG Third Party License. PIRATE BORG is ©2022 Limithron LLC.</p>
        <p>&copy; 2025 The Salty Parrot</p>
    </footer>
</body>
</html>