<?php
/**
 * Account Management Page
 * 
 * Handles user account settings, authentication, and integrations
 */

// Start the session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include Firebase configuration
require_once 'config/firebase-config.php';

// Redirect to login if not authenticated
if (!is_firebase_authenticated()) {
    header('Location: index.php');
    exit;
}

$user = get_firebase_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - The Salty Parrot</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/topbar.css">
    <link rel="stylesheet" href="css/discord.css">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
</head>
<body>
    <!-- Include the topbar -->
    <?php include 'components/topbar.php'; ?>
    
    <!-- Main Content Area -->
    <main class="main-content-new">
        <div class="account-container">
            <h1>Account Settings</h1>
            
            <!-- User Profile Section -->
            <section class="account-section">
                <h2>Profile Information</h2>
                <div class="profile-info">
                    <?php if ($user['photoURL']): ?>
                        <img src="<?php echo htmlspecialchars($user['photoURL']); ?>" alt="Profile Picture" class="profile-picture">
                    <?php endif; ?>
                    <div class="profile-details">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($user['displayName'] ?? 'Not set'); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p><strong>Account ID:</strong> <?php echo htmlspecialchars($user['uid']); ?></p>
                    </div>
                </div>
            </section>

            <!-- Authentication Section -->
            <section class="account-section">
                <h2>Authentication</h2>
                <div class="auth-options">
                    <button id="signout-btn" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt"></i> Sign Out
                    </button>
                </div>
            </section>

            <!-- Integrations Section -->
            <section class="account-section">
                <h2>Integrations</h2>
                <div class="integration-options">
                    <div class="discord-integration">
                        <h3>Discord Integration</h3>
                        <p>Connect your Discord account to enable additional features.</p>
                        <button id="connect-discord-btn" class="btn btn-discord">
                            <i class="fab fa-discord"></i> Connect Discord
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <footer>
        <p>The Salty Parrot is an independent production by Stuart Greenwell. It is not affiliated with Limithron LLC. It is published under the PIRATE BORG Third Party License. PIRATE BORG is ©2022 Limithron LLC.</p>
        <p>&copy; 2025 The Salty Parrot</p>
    </footer>

    <!-- Firebase Auth Script -->
    <script type="module">
        import { signOutUser } from './js/firebase-auth.js';
        import { initDiscordAuth } from './js/discord_integration.js';

        // Get DOM elements
        const signoutBtn = document.getElementById('signout-btn');
        const connectDiscordBtn = document.getElementById('connect-discord-btn');

        // Sign out handler
        signoutBtn?.addEventListener('click', async () => {
            try {
                const result = await signOutUser();
                if (result.success) {
                    window.location.href = 'index.php';
                } else {
                    console.error('Error signing out:', result.error);
                }
            } catch (error) {
                console.error('Error signing out:', error);
            }
        });

        // Discord connection handler
        connectDiscordBtn?.addEventListener('click', () => {
            initDiscordAuth();
        });
    </script>
</body>
</html> 