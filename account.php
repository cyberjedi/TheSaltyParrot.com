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

// Check if user is logged in first
if (!isset($_SESSION['uid'])) {
    header('Location: index.php');
    exit;
}

// Include Firebase configuration
require_once 'config/firebase-config.php';

// Include Discord configuration
require_once 'discord/discord-config.php';

// Initialize user data with session values
$user = [
    'displayName' => $_SESSION['displayName'] ?? 'User',
    'email' => $_SESSION['email'] ?? '',
    'photoURL' => $_SESSION['photoURL'] ?? null
];

// Try to get additional data from database
try {
    require_once 'config/db_connect.php';
    
    if (isset($conn) && $conn !== null) {
        $stmt = $conn->prepare("SELECT display_name, email, photo_url FROM users WHERE uid = ?");
        $stmt->execute([$_SESSION['uid']]);
        $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($dbUser) {
            // Update user data with database values if they exist
            $user['displayName'] = $dbUser['display_name'] ?? $user['displayName'];
            $user['email'] = $dbUser['email'] ?? $user['email'];
            $user['photoURL'] = $dbUser['photo_url'] ?? $user['photoURL'];

            // Update session with latest values
            $_SESSION['displayName'] = $user['displayName'];
            $_SESSION['photoURL'] = $user['photoURL'];
        }
    }
} catch (Exception $e) {
    // Log the error but continue with session data
    error_log("Error fetching user data: " . $e->getMessage());
}
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
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--accent);
            color: var(--dark);
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            min-width: 140px;
        }

        .btn:hover {
            background: var(--accent-hover);
            transform: translateY(-1px);
        }

        .btn i {
            font-size: 1rem;
        }

        .btn-danger {
            background: #dc3545;
            color: #fff;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-discord {
            background: #7289da;
            color: #fff;
        }

        .btn-discord:hover {
            background: #5b73c7;
        }

        .btn-primary {
            background: var(--accent);
            color: var(--dark);
        }

        .btn-primary:hover {
            background: var(--accent-hover);
        }

        .btn-small {
            padding: 0.5rem 1rem;
            min-width: auto;
            font-size: 0.9rem;
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
            }

            .party-actions {
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
            }
        }

        .party-form-group {
            margin-bottom: 1.5rem;
            padding: 1.5rem;
            background: rgba(0, 0, 0, 0.4);
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .party-form-group h3 {
            margin: 0 0 1rem;
            font-size: 1.1rem;
            color: var(--accent);
        }

        .party-form {
            display: flex;
            gap: 0.75rem;
        }

        .party-form input {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 4px;
            background: rgba(0, 0, 0, 0.3);
            color: #fff;
            font-size: 0.95rem;
        }

        .party-form input:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(0, 0, 0, 0.4);
        }

        .party-form input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .party-form button {
            padding: 0.75rem 1.5rem;
            background: var(--accent);
            color: #000;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .party-form button:hover {
            background: var(--accent-hover);
            transform: translateY(-1px);
        }

        .party-form button i {
            margin-right: 0.5rem;
        }

        .party-members {
            margin-top: 1rem;
        }

        .party-member {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
            margin-bottom: 0.5rem;
        }

        .party-member:last-child {
            margin-bottom: 0;
        }

        .party-member-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        .party-member-info {
            flex: 1;
        }

        .party-member-name {
            margin: 0;
            color: var(--light);
            font-size: 0.95rem;
        }

        .party-member-role {
            margin: 0;
            color: var(--accent);
            font-size: 0.8rem;
            opacity: 0.8;
        }

        .party-code {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 4px;
            font-family: monospace;
            font-size: 1.2rem;
            letter-spacing: 2px;
            color: var(--accent);
            margin: 1rem 0;
        }

        .party-actions {
            margin-top: 1rem;
            display: flex;
            gap: 0.5rem;
        }

        @media (max-width: 768px) {
            .party-form {
                flex-direction: column;
            }

            .party-form button {
                width: 100%;
            }

            .party-actions {
                flex-direction: column;
            }
        }

        /* Login form styles */
        .login-form {
            background-color: #41C8D4 !important;
            border: none !important;
        }

        .login-form input {
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border: 1px solid var(--accent);
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.1);
            color: var(--light);
            font-size: 0.95rem;
        }

        .login-form input:focus {
            outline: none;
            border-color: var(--accent-hover);
            background: rgba(255, 255, 255, 0.15);
        }

        .login-form input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .login-form label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--light);
            font-weight: 500;
        }

        .login-form button {
            background: var(--accent);
            color: var(--dark);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 100%;
        }

        .login-form button:hover {
            background: var(--accent-hover);
            transform: translateY(-1px);
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: #41C8D4;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            position: relative;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .modal-header h3 {
            margin: 0;
            color: #000;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .close-modal {
            background: none;
            border: none;
            color: #000;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            line-height: 1;
        }

        .modal-body {
            padding: 1.5rem;
            background-color: #41C8D4;
        }

        .modal-body .form-group {
            margin-bottom: 1rem;
        }

        .modal-body .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #000;
            font-weight: 500;
        }

        .modal-body input {
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(0, 0, 0, 0.2);
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.9);
            color: #000;
            font-size: 0.95rem;
        }

        .modal-body input:focus {
            outline: none;
            border-color: #000;
            background: #fff;
        }

        .modal-body input::placeholder {
            color: rgba(0, 0, 0, 0.5);
        }

        .modal-body .btn-primary {
            background: #000;
            color: #fff;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 100%;
        }

        .modal-body .btn-primary:hover {
            background: #333;
            transform: translateY(-1px);
        }

        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                margin: 1rem;
            }
        }

        .settings-form {
            max-width: 500px;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--light);
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--accent);
            border-radius: 4px;
            color: var(--light);
            font-size: 1rem;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent-hover);
            background: rgba(255, 255, 255, 0.15);
        }

        .photo-preview {
            margin-top: 1rem;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid var(--accent);
        }

        .photo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Profile Settings Styles */
        .profile-form {
            margin-top: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--light);
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            color: var(--light);
            font-size: 1rem;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent);
        }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            display: none;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .alert-error {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
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
                </div>
            </div>

            <div class="account-section">
                <h2><i class="fas fa-user"></i> Account Settings</h2>
                <div class="account-info">
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                </div>
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

            <div class="account-section">
                <h2><i class="fas fa-users"></i> Party</h2>
                <div id="party-section">
                    <div id="party-loading" class="text-center">
                        <i class="fas fa-spinner fa-spin"></i> Loading party information...
                    </div>
                    
                    <!-- Party information will be loaded here -->
                    <div id="party-info" style="display: none;">
                        <!-- Content will be dynamically updated -->
                    </div>

                    <!-- Party Action Buttons -->
                    <div id="party-forms" style="display: none;">
                        <div class="party-actions">
                            <button id="create-party-btn" class="btn btn-danger">
                                <i class="fas fa-plus"></i> Create Party
                            </button>
                            <button id="join-party-btn" class="btn btn-danger">
                                <i class="fas fa-sign-in-alt"></i> Join Party
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="account-section">
                <h2><i class="fas fa-user-edit"></i> Profile Settings</h2>
                <div id="alert" class="alert"></div>
                <form id="profile-form" class="profile-form">
                    <div class="form-group">
                        <label for="displayName">Display Name</label>
                        <input type="text" 
                               id="displayName" 
                               name="displayName" 
                               value="<?php echo htmlspecialchars($user['displayName']); ?>" 
                               required>
                    </div>
                    <div class="form-group">
                        <label for="photoURL">Photo URL</label>
                        <input type="url" 
                               id="photoURL" 
                               name="photoURL" 
                               value="<?php echo htmlspecialchars($user['photoURL'] ?? ''); ?>"
                               placeholder="https://example.com/photo.jpg">
                    </div>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-save"></i>
                        Save Changes
                    </button>
                </form>
            </div>

            <script>
            document.getElementById('profile-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const displayName = document.getElementById('displayName').value;
                const photoURL = document.getElementById('photoURL').value;

                try {
                    const response = await fetch('/api/update_profile.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            displayName,
                            photoURL
                        })
                    });

                    const data = await response.json();
                    if (data.success) {
                        // Update the UI without reloading
                        document.querySelector('.profile-info h1').textContent = displayName;
                        const profileImage = document.querySelector('.profile-image');
                        if (photoURL) {
                            profileImage.src = photoURL;
                        } else {
                            profileImage.outerHTML = '<div class="profile-image-placeholder"><i class="fas fa-user"></i></div>';
                        }
                        
                        // Show success message
                        const alert = document.getElementById('alert');
                        alert.className = 'alert alert-success';
                        alert.textContent = 'Profile updated successfully!';
                        alert.style.display = 'block';
                        setTimeout(() => {
                            alert.style.display = 'none';
                        }, 3000);
                    } else {
                        throw new Error(data.error || 'Failed to update profile');
                    }
                } catch (error) {
                    console.error('Error updating profile:', error);
                    const alert = document.getElementById('alert');
                    alert.className = 'alert alert-error';
                    alert.textContent = error.message || 'Failed to update profile. Please try again.';
                    alert.style.display = 'block';
                }
            });

            // Preview photo URL when entered
            document.getElementById('photoURL').addEventListener('input', (e) => {
                const preview = document.getElementById('photo-preview');
                const url = e.target.value;
                if (url) {
                    preview.src = url;
                } else {
                    preview.src = 'assets/TSP_default_character.jpg';
                }
            });
            </script>

            <!-- Party Modals -->
            <div id="create-party-modal" class="modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Create a Party</h3>
                        <button class="close-modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form id="create-party-form" class="party-form">
                            <div class="form-group">
                                <label for="party-name">Party Name</label>
                                <input type="text" id="party-name" placeholder="Enter party name" required>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Create Party
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div id="join-party-modal" class="modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Join a Party</h3>
                        <button class="close-modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form id="join-party-form" class="party-form">
                            <div class="form-group">
                                <label for="party-code">Party Code</label>
                                <input type="text" id="party-code" placeholder="Enter 6-digit party code" required pattern="[A-Za-z0-9]{6}">
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt"></i> Join Party
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
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

    <script type="module">
        // Party management functions
        const partySection = {
            async init() {
                await this.loadPartyInfo();
                this.setupEventListeners();
            },

            async loadPartyInfo() {
                try {
                    const response = await fetch('/party/api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=get_party'
                    });

                    const data = await response.json();
                    
                    if (data.success) {
                        if (data.party) {
                            await this.displayPartyInfo(data.party);
                        } else {
                            this.showPartyForms();
                        }
                    } else {
                        throw new Error(data.error);
                    }
                } catch (error) {
                    console.error('Error loading party info:', error);
                    this.showError('Failed to load party information');
                } finally {
                    document.getElementById('party-loading').style.display = 'none';
                }
            },

            async displayPartyInfo(party) {
                const members = await this.getPartyMembers(party.id);
                const partyInfo = document.getElementById('party-info');
                
                partyInfo.innerHTML = `
                    <h3>${party.name}</h3>
                    <div class="party-code">
                        ${party.code}
                        <button class="btn btn-small" onclick="navigator.clipboard.writeText('${party.code}')">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <div class="party-members">
                        ${members.map(member => `
                            <div class="party-member">
                                <img src="${member.photoURL || 'assets/TSP_default_character.jpg'}" 
                                     alt="${member.displayName}" 
                                     class="party-member-avatar"
                                     onerror="this.src='assets/TSP_default_character.jpg'">
                                <div class="party-member-info">
                                    <p class="party-member-name">${member.displayName}</p>
                                    <p class="party-member-role">
                                        ${party.creator_id === member.uid ? 'Party Leader' : 'Member'}
                                    </p>
                                </div>
                                ${party.creator_id === '<?php echo $_SESSION['uid']; ?>' && member.uid !== '<?php echo $_SESSION['uid']; ?>' ? `
                                    <button class="btn btn-danger btn-small" onclick="partySection.removeMember('${party.id}', '${member.uid}')">
                                        <i class="fas fa-user-minus"></i> Kick
                                    </button>
                                ` : ''}
                            </div>
                        `).join('')}
                    </div>
                    <div class="party-actions">
                        <button class="btn btn-danger" onclick="partySection.leaveParty('${party.id}')">
                            <i class="fas fa-sign-out-alt"></i> Leave Party
                        </button>
                    </div>
                `;
                
                partyInfo.style.display = 'block';
                document.getElementById('party-forms').style.display = 'none';
            },

            async getPartyMembers(partyId) {
                try {
                    const response = await fetch('/party/api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=get_members&party_id=${partyId}`
                    });

                    const data = await response.json();
                    return data.success ? data.members : [];
                } catch (error) {
                    console.error('Error getting party members:', error);
                    return [];
                }
            },

            showPartyForms() {
                document.getElementById('party-info').style.display = 'none';
                document.getElementById('party-forms').style.display = 'block';
            },

            setupEventListeners() {
                // Create party button
                document.getElementById('create-party-btn').addEventListener('click', () => {
                    modals.show('createParty');
                });

                // Join party button
                document.getElementById('join-party-btn').addEventListener('click', () => {
                    modals.show('joinParty');
                });

                // Close modal buttons
                document.querySelectorAll('.close-modal').forEach(button => {
                    button.addEventListener('click', () => {
                        const modal = button.closest('.modal');
                        if (modal.id === 'create-party-modal') {
                            modals.hide('createParty');
                        } else {
                            modals.hide('joinParty');
                        }
                    });
                });

                // Close modals when clicking outside
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.addEventListener('click', (e) => {
                        if (e.target === modal) {
                            modals.hide(modal.id.replace('-modal', ''));
                        }
                    });
                });

                // Create party form
                document.getElementById('create-party-form').addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const name = document.getElementById('party-name').value;
                    
                    try {
                        const response = await fetch('/party/api.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `action=create&name=${encodeURIComponent(name)}`
                        });

                        const data = await response.json();
                        if (data.success) {
                            modals.hide('createParty');
                            await this.loadPartyInfo();
                        } else {
                            throw new Error(data.error);
                        }
                    } catch (error) {
                        console.error('Error creating party:', error);
                        this.showError('Failed to create party');
                    }
                });

                // Join party form
                document.getElementById('join-party-form').addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const code = document.getElementById('party-code').value;
                    
                    try {
                        const response = await fetch('/party/api.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `action=join&code=${encodeURIComponent(code)}`
                        });

                        const data = await response.json();
                        if (data.success) {
                            modals.hide('joinParty');
                            await this.loadPartyInfo();
                        } else {
                            throw new Error(data.error);
                        }
                    } catch (error) {
                        console.error('Error joining party:', error);
                        this.showError('Failed to join party');
                    }
                });
            },

            async removeMember(partyId, memberId) {
                if (!confirm('Are you sure you want to remove this member?')) {
                    return;
                }

                try {
                    const response = await fetch('/party/api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=remove_member&party_id=${partyId}&member_id=${memberId}`
                    });

                    const data = await response.json();
                    if (data.success) {
                        await this.loadPartyInfo();
                    } else {
                        throw new Error(data.error);
                    }
                } catch (error) {
                    console.error('Error removing member:', error);
                    this.showError('Failed to remove member');
                }
            },

            async leaveParty(partyId) {
                if (!confirm('Are you sure you want to leave this party?')) {
                    return;
                }

                try {
                    const response = await fetch('/party/api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=leave_party&party_id=${partyId}`
                    });

                    const data = await response.json();
                    if (data.success) {
                        await this.loadPartyInfo();
                    } else {
                        throw new Error(data.error);
                    }
                } catch (error) {
                    console.error('Error leaving party:', error);
                    this.showError('Failed to leave party');
                }
            },

            showError(message) {
                // You can implement a better error display system
                alert(message);
            }
        };

        // Initialize party section
        partySection.init();

        // Make partySection available globally for event handlers
        window.partySection = partySection;

        // Add modal functionality
        const modals = {
            createParty: document.getElementById('create-party-modal'),
            joinParty: document.getElementById('join-party-modal'),
            
            show(modalId) {
                const modal = this[modalId];
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            },
            
            hide(modalId) {
                const modal = this[modalId];
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
        };
    </script>

    <script>
        // Profile Settings
        const profileForm = document.getElementById('profile-form');
        const alertDiv = document.getElementById('alert');
        const profileImage = document.querySelector('.profile-image');

        // Show alert message
        function showAlert(message, type = 'error') {
            alertDiv.textContent = message;
            alertDiv.className = `alert alert-${type}`;
            alertDiv.style.display = 'block';
            setTimeout(() => {
                alertDiv.style.display = 'none';
            }, 5000);
        }

        // Handle form submission
        profileForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                displayName: document.getElementById('displayName').value.trim(),
                photoURL: document.getElementById('photoURL').value.trim()
            };

            try {
                const response = await fetch('/api/update_profile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (response.ok) {
                    showAlert('Profile updated successfully!', 'success');
                    // Reload the page to show updated data
                    window.location.reload();
                } else {
                    showAlert(data.error || 'Failed to update profile');
                }
            } catch (error) {
                showAlert('An error occurred while updating your profile');
            }
        });

        // Handle profile image errors
        if (profileImage) {
            profileImage.addEventListener('error', function() {
                this.src = 'assets/default-avatar.png';
            });
        }
    </script>
</body>
</html> 