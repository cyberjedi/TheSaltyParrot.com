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
            padding: 0.75rem 1.5rem;
            background: var(--accent);
            color: #000;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn:hover {
            background: var(--accent-hover);
            transform: translateY(-1px);
        }

        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
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
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 2rem;
            border-radius: 8px;
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
        }

        .login-form button:hover {
            background: var(--accent-hover);
            transform: translateY(-1px);
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

                    <!-- Create/Join Party Forms -->
                    <div id="party-forms" style="display: none;">
                        <div class="party-form-group">
                            <h3>Create a Party</h3>
                            <form id="create-party-form" class="party-form">
                                <input type="text" id="party-name" placeholder="Party Name" required>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Create Party
                                </button>
                            </form>
                        </div>

                        <div class="party-form-group">
                            <h3>Join a Party</h3>
                            <form id="join-party-form" class="party-form">
                                <input type="text" id="party-code" placeholder="Enter 6-digit Party Code" required pattern="[A-Za-z0-9]{6}">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt"></i> Join Party
                                </button>
                            </form>
                        </div>
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
                                <img src="${member.photoURL || 'assets/default-avatar.png'}" 
                                     alt="${member.displayName}" 
                                     class="party-member-avatar"
                                     onerror="this.src='assets/default-avatar.png'">
                                <div class="party-member-info">
                                    <p class="party-member-name">${member.displayName}</p>
                                    <p class="party-member-role">
                                        ${party.creator_id === member.uid ? 'Party Leader' : 'Member'}
                                    </p>
                                </div>
                                ${party.creator_id === '<?php echo $_SESSION['uid']; ?>' && member.uid !== '<?php echo $_SESSION['uid']; ?>' ? `
                                    <button class="btn btn-danger btn-small" onclick="partySection.removeMember('${party.id}', '${member.uid}')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                ` : ''}
                            </div>
                        `).join('')}
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

            showError(message) {
                // You can implement a better error display system
                alert(message);
            }
        };

        // Initialize party section
        partySection.init();

        // Make partySection available globally for event handlers
        window.partySection = partySection;
    </script>
</body>
</html> 