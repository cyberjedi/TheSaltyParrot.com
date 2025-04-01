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
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--accent);
            background-color: rgba(255, 255, 255, 0.1);
        }

        .profile-image-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
            font-size: 2rem;
        }

        .profile-info h1 {
            margin: 0;
            font-size: 1.8rem;
            color: var(--light);
            font-weight: 500;
        }

        .profile-info p {
            margin: 0.25rem 0 0;
            color: var(--accent);
            font-size: 1rem;
            opacity: 0.8;
        }

        .account-section {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            padding: 1.25rem;
            margin-bottom: 1rem;
        }

        .account-section h2 {
            color: var(--light);
            margin: 0 0 1rem;
            font-size: 1.2rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .account-section h2 i {
            color: var(--accent);
            font-size: 1.1rem;
        }

        .discord-status {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        .discord-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .discord-info {
            flex: 1;
        }

        .discord-info h3 {
            margin: 0;
            color: var(--light);
            font-size: 1rem;
            font-weight: 500;
        }

        .discord-info p {
            margin: 0.15rem 0 0;
            color: var(--accent);
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 6px;
            font-size: 0.95rem;
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
            width: auto;
        }

        .btn-discord:hover {
            background: #5b73c7;
            transform: translateY(-1px);
        }

        .btn i {
            font-size: 1rem;
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
                padding-bottom: 1rem;
            }

            .discord-status {
                flex-direction: row;
                text-align: left;
                gap: 0.75rem;
                padding: 0.75rem;
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
                <?php if ($user['photoURL']): ?>
                    <img src="<?php echo htmlspecialchars($user['photoURL']); ?>" alt="Profile" class="profile-image" onerror="this.outerHTML='<div class=\'profile-image-placeholder\'><i class=\'fas fa-user\'></i></div>'">
                <?php else: ?>
                    <div class="profile-image-placeholder">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>
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
                <?php if (isset($_SESSION['discord_user']) && isset($_SESSION['discord_user']['avatar_url'])): ?>
                    <div class="discord-status">
                        <img 
                            src="<?php echo htmlspecialchars($_SESSION['discord_user']['avatar_url']); ?>" 
                            alt="Discord Avatar" 
                            class="discord-avatar"
                            onerror="this.src='https://cdn.discordapp.com/embed/avatars/0.png'"
                        >
                        <div class="discord-info">
                            <h3><?php echo htmlspecialchars($_SESSION['discord_user']['username']); ?></h3>
                            <p>Connected</p>
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
        import { initDiscordAuth } from './js/discord_integration.js';

        // Handle Discord connection
        const connectDiscordBtn = document.getElementById('connect-discord-btn');
        if (connectDiscordBtn) {
            connectDiscordBtn.addEventListener('click', () => {
                initDiscordAuth();
            });
        }

        // Handle sign out
        const signOutBtn = document.getElementById('sign-out-btn');
        if (signOutBtn) {
            signOutBtn.addEventListener('click', async () => {
                try {
                    const { signOutUser } = await import('./js/firebase-auth.js');
                    await signOutUser();
                    window.location.reload();
                } catch (error) {
                    console.error('Error signing out:', error);
                }
            });
        }
    </script>
</body>
</html> 