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

// Debug session info
error_log("Session data: " . print_r($_SESSION, true));

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

error_log("Initial user data from session: " . print_r($user, true));

// Try to get additional data from database
try {
    require_once 'config/db_connect.php';
    
    if (!isset($conn) || $conn === null) {
        error_log("Database connection failed - conn is null or not set");
    } else {
        error_log("Database connection successful");
        
        error_log("Fetching user with UID: " . $_SESSION['uid']);
        $stmt = $conn->prepare("SELECT display_name, email, photo_url FROM users WHERE uid = ?");
        $stmt->execute([$_SESSION['uid']]);
        $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);

        error_log("DB User data fetched: " . print_r($dbUser, true));
        
        if ($dbUser) {
            error_log("Found user in database. Updating values from DB.");
            // Update user data with database values if they exist
            $user['displayName'] = $dbUser['display_name'] ?? $user['displayName'];
            $user['email'] = $dbUser['email'] ?? $user['email'];
            $user['photoURL'] = $dbUser['photo_url'] ?? $user['photoURL'];

            // Update session with latest values
            $_SESSION['displayName'] = $user['displayName'];
            $_SESSION['photoURL'] = $user['photoURL'];
            
            error_log("Updated user data from DB: " . print_r($user, true));
        } else {
            error_log("No user found in database with that UID.");
        }
    }
} catch (Exception $e) {
    // Log the error but continue with session data
    error_log("Error fetching user data: " . $e->getMessage());
    error_log("Error trace: " . $e->getTraceAsString());
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
    <link rel="stylesheet" href="css/account.css">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
</head>
<body class="account-page">
    <!-- Include the topbar -->
    <?php include 'components/topbar.php'; ?>
    
    <main class="main-content">
        <div class="account-container">
            <div class="account-header">
                <div class="profile-image-wrapper">
                    <?php if ($user['photoURL']): ?>
                        <img src="<?php echo htmlspecialchars($user['photoURL']); ?>" alt="Profile Photo" class="profile-image">
                    <?php else: ?>
                        <div class="profile-image-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                    <div class="edit-profile-photo" onclick="openPhotoManagement()">
                        <i class="fas fa-pencil-alt"></i>
                    </div>
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($user['displayName']); ?></h1>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
            </div>

            <div class="account-section">
                <h2><i class="fas fa-user-cog"></i> Profile Settings</h2>
                <div class="profile-form">
                    <div id="profile-alert" class="alert" role="alert"></div>
                    
                    <div class="form-group">
                        <label for="displayName">Display Name</label>
                        <input type="text" id="displayName" value="<?php echo htmlspecialchars($user['displayName']); ?>" placeholder="Your display name">
                    </div>
                    
                    <button id="save-profile" class="btn btn-submit">Save Profile</button>
                </div>
            </div>
            
            <div class="account-section">
                <h2><i class="fas fa-shield-alt"></i> Account Security</h2>
                
                <div id="security-alert" class="alert" role="alert"></div>
                
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" placeholder="New password">
                </div>
                
                <div class="form-group">
                    <label for="confirm-password">Confirm New Password</label>
                    <input type="password" id="confirm-password" placeholder="Confirm new password">
                </div>
                
                <button id="change-password" class="btn btn-submit">Update Password</button>
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

    <script src="js/auth.js"></script>
    <script src="js/account.js"></script>
    
    <!-- Photo Management Modal -->
    <div id="photo-management-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="close-photo-modal">&times;</span>
            <h3>Profile Photo</h3>
            <div class="photo-management-content">
                <div class="current-photo">
                    <h4>Current Photo</h4>
                    <img id="current-profile-photo" src="<?php echo htmlspecialchars($_SESSION['photoURL'] ?? 'assets/TSP_default_character.jpg'); ?>" alt="Current Photo">
                </div>
                <div class="photo-upload">
                    <h4>Upload New Photo</h4>
                    <form id="photo-upload-form">
                        <div class="file-input-wrapper">
                            <input type="file" id="profile_photo" name="profile_photo" accept="image/jpeg,image/png,image/gif">
                            <p class="help-text">Recommended size: 200x200 pixels. Max file size: 2MB.</p>
                        </div>
                        <button type="submit" class="btn btn-danger">Upload Photo</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script type="module">
        // Photo management
        const photoModal = document.getElementById('photo-management-modal');
        const closePhotoModal = document.getElementById('close-photo-modal');
        const photoUploadForm = document.getElementById('photo-upload-form');
        
        // Close modal when clicking X or outside
        closePhotoModal.addEventListener('click', () => {
            photoModal.style.display = 'none';
        });
        
        window.addEventListener('click', (e) => {
            if (e.target === photoModal) {
                photoModal.style.display = 'none';
            }
        });
        
        // Function to open photo management modal
        window.openPhotoManagement = function() {
            // Show the modal
            photoModal.style.display = 'flex';
            
            // Update current photo
            const currentPhoto = document.getElementById('current-profile-photo');
            const userPhoto = '<?php echo htmlspecialchars($_SESSION['photoURL'] ?? ""); ?>';
            currentPhoto.src = userPhoto || 'assets/TSP_default_character.jpg';
            currentPhoto.onerror = function() {
                this.src = 'assets/TSP_default_character.jpg';
            };
        };
        
        // Handle photo upload
        photoUploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const fileInput = document.getElementById('profile_photo');
            if (!fileInput.files || fileInput.files.length === 0) {
                alert('Please select a file');
                return;
            }
            
            const formData = new FormData();
            formData.append('profile_photo', fileInput.files[0]);
            
            try {
                const response = await fetch('/api/update_profile_photo.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Update UI with new photo
                    const userPhoto = document.getElementById('user-photo');
                    if (userPhoto) {
                        userPhoto.src = data.photo_url;
                    }
                    
                    // Update session
                    window.sessionStorage.setItem('photoURL', data.photo_url);
                    
                    // Close modal
                    photoModal.style.display = 'none';
                    
                    // Reload page to refresh all instances of the user's photo
                    window.location.reload();
                } else {
                    alert('Error updating photo: ' + data.error);
                }
            } catch (error) {
                console.error('Error uploading photo:', error);
                alert('Failed to upload photo');
            }
        });

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
                                <div class="party-member-avatar-wrapper">
                                    <img src="${member.activeCharacterImage || member.photo_url || 'assets/TSP_default_character.jpg'}" 
                                         alt="${member.display_name || 'User'}" 
                                         class="party-member-avatar"
                                         onerror="this.src='assets/TSP_default_character.jpg'">
                                </div>
                                <div class="party-member-info">
                                    <p class="party-member-name">${member.display_name || 'Unknown User'}</p>
                                    <p class="party-member-character">${member.activeCharacterName || 'No Active Character'}</p>
                                    <p class="party-member-role ${(party.game_master_id === member.uid) ? 'gm-role' : ''}">
                                        ${party.game_master_id === member.uid ? '<strong>Game Master</strong>' : 
                                          party.creator_id === member.uid ? 'Party Leader' : 'Member'}
                                    </p>
                                </div>
                                <div class="party-member-actions">
                                    ${party.creator_id === '<?php echo $_SESSION['uid']; ?>' && member.uid !== '<?php echo $_SESSION['uid']; ?>' ? `
                                        <button class="btn btn-small" onclick="partySection.removeMember('${party.id}', '${member.uid}')">
                                            <i class="fas fa-user-minus"></i>
                                        </button>
                                        <button class="btn btn-small ${party.game_master_id === member.uid ? 'active' : ''}" 
                                                onclick="partySection.setGameMaster('${party.id}', '${member.uid}')">
                                            <i class="fas fa-chess-king"></i>
                                        </button>
                                    ` : ''}
                                    ${party.creator_id === '<?php echo $_SESSION['uid']; ?>' && member.uid === '<?php echo $_SESSION['uid']; ?>' && party.game_master_id !== member.uid ? `
                                        <button class="btn btn-small" 
                                                onclick="partySection.setGameMaster('${party.id}', '${member.uid}')">
                                            <i class="fas fa-chess-king"></i>
                                        </button>
                                    ` : ''}
                                </div>
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
                    if (data.success) {
                        // Fetch active character info for each member
                        const members = await Promise.all(data.members.map(async (member) => {
                            try {
                                const charResponse = await fetch(`/api/get_active_character.php?user_id=${member.uid}`);
                                const charData = await charResponse.json();
                                
                                if (charData.success && charData.character) {
                                    return {
                                        ...member,
                                        activeCharacterName: charData.character.name,
                                        activeCharacterImage: charData.character.image_path
                                    };
                                }
                            } catch (err) {
                                console.error('Error getting active character:', err);
                            }
                            return member;
                        }));
                        return members;
                    }
                    return [];
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

            async setGameMaster(partyId, gmUserId) {
                if (!confirm('Are you sure you want to set this member as Game Master?')) {
                    return;
                }

                try {
                    const response = await fetch('/party/api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=set_game_master&party_id=${partyId}&gm_user_id=${gmUserId}`
                    });

                    const data = await response.json();
                    if (data.success) {
                        await this.loadPartyInfo();
                    } else {
                        throw new Error(data.error);
                    }
                } catch (error) {
                    console.error('Error setting game master:', error);
                    this.showError('Failed to set game master');
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
</body>
</html> 