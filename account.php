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

// Include Discord configuration
require_once 'discord/discord-config.php';

// Check if user is logged in
if (!isset($_SESSION['firebase_token'])) {
    header('Location: index.php');
    exit;
}

// Get user data from session
$user = [
    'displayName' => $_SESSION['displayName'] ?? 'User',
    'email' => $_SESSION['email'] ?? '',
    'photoURL' => $_SESSION['photoURL'] ?? null
];
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
    <style>
        .account-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: var(--dark);
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .account-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--accent);
        }

        .profile-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--accent);
        }

        .profile-info h1 {
            margin: 0;
            font-size: 2rem;
            color: var(--light);
        }

        .profile-info p {
            margin: 0.5rem 0 0;
            color: var(--accent);
            font-size: 1.1rem;
        }

        .account-section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .account-section h2 {
            color: var(--accent);
            margin: 0 0 1rem;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .account-section h2 i {
            font-size: 1.2rem;
        }

        .discord-status {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            margin-top: 1rem;
        }

        .discord-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
        }

        .discord-info {
            flex: 1;
        }

        .discord-info h3 {
            margin: 0;
            color: var(--light);
            font-size: 1.1rem;
        }

        .discord-info p {
            margin: 0.25rem 0 0;
            color: var(--accent);
            font-size: 0.9rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--accent);
            color: var(--dark);
        }

        .btn-primary:hover {
            background: var(--accent-hover);
            transform: translateY(-1px);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        .btn-discord {
            background: #7289da;
            color: white;
        }

        .btn-discord:hover {
            background: #5b73c7;
            transform: translateY(-1px);
        }

        .btn i {
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .account-container {
                margin: 1rem;
                padding: 1rem;
            }

            .account-header {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .discord-status {
                flex-direction: column;
                text-align: center;
                gap: 0.5rem;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Include the topbar -->
    <?php include 'components/topbar.php'; ?>
    
    <main class="main-content">
        <div class="account-container">
            <div class="account-header">
                <img src="<?php echo $user['photoURL'] ?? 'assets/default-avatar.png'; ?>" alt="Profile" class="profile-image">
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($user['displayName']); ?></h1>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
            </div>

            <div class="account-section">
                <h2><i class="fas fa-user"></i> Account Settings</h2>
                <button id="sign-out-btn" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Sign Out
                </button>
            </div>

            <div class="account-section">
                <h2><i class="fab fa-discord"></i> Discord Integration</h2>
                <?php if (isset($_SESSION['discord_user'])): ?>
                    <div class="discord-status">
                        <img src="<?php echo $_SESSION['discord_user']['avatar_url']; ?>" alt="Discord Avatar" class="discord-avatar">
                        <div class="discord-info">
                            <h3><?php echo htmlspecialchars($_SESSION['discord_user']['username']); ?></h3>
                            <p>Connected as <?php echo htmlspecialchars($_SESSION['discord_user']['username']); ?></p>
                        </div>
                    </div>
                    <a href="discord/discord-logout.php" class="btn btn-danger">
                        <i class="fas fa-unlink"></i> Disconnect Discord
                    </a>
                <?php else: ?>
                    <button id="connect-discord-btn" class="btn btn-discord">
                        <i class="fab fa-discord"></i> Connect Discord
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer>
        <p>The Salty Parrot is an independent production by Stuart Greenwell. It is not affiliated with Limithron LLC. It is published under the PIRATE BORG Third Party License. PIRATE BORG is ©2022 Limithron LLC.</p>
        <p>&copy; 2025 The Salty Parrot</p>
    </footer>

    <!-- Pass Discord client ID to JavaScript -->
    <script>
        window.DISCORD_CLIENT_ID = '<?php echo DISCORD_CLIENT_ID; ?>';
    </script>
    <script type="module">
        import { signOutUser } from './js/firebase-auth.js';
        import { initDiscordAuth } from './js/discord_integration.js';

        // Get DOM elements
        const signOutBtn = document.getElementById('sign-out-btn');
        const connectDiscordBtn = document.getElementById('connect-discord-btn');

        // Handle sign out
        if (signOutBtn) {
            signOutBtn.addEventListener('click', async () => {
                try {
                    await signOutUser();
                    window.location.reload();
                } catch (error) {
                    console.error('Error signing out:', error);
                }
            });
        }

        // Handle Discord connection
        if (connectDiscordBtn) {
            connectDiscordBtn.addEventListener('click', () => {
                initDiscordAuth();
            });
        }
    </script>
</body>
</html> 