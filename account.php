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
<body class="account-page" data-user-uid="<?php echo htmlspecialchars($_SESSION['uid']); ?>">
    <!-- Include the topbar -->
    <?php include 'components/topbar.php'; ?>
    
    <main class="main-content">
        <div class="account-container">
            <div id="notification-area"></div>
            <div class="account-header">
                <div class="profile-image-wrapper">
                    <?php if ($user['photoURL']): ?>
                        <img src="<?php echo htmlspecialchars($user['photoURL']); ?>" alt="Profile Photo" class="profile-image">
                    <?php else: ?>
                        <div class="profile-image-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
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
                    
                    <button id="profile-image-btn" class="btn btn-submit">
                        <i class="fas fa-image"></i> Profile Image
                    </button>

                    <button id="change-password-btn" class="btn btn-submit">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                    
                    <button id="save-profile" class="btn btn-submit">
                        <i class="fas fa-save"></i> Save Profile
                    </button>
                </div>
            </div>
            
            <div class="account-section">
                <h2><i class="fab fa-discord"></i> Discord Integration</h2>
                <?php if (isset($_SESSION['discord_user']) && isset($_SESSION['discord_user']['avatar_url'])): ?>
                    <div class="discord-connected">
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
                    <a href="discord/discord-login.php" id="connect-discord-btn" class="btn btn-discord">
                        <i class="fab fa-discord"></i> Connect Discord
                    </a>
                <?php endif; ?>
            </div>

            <!-- Discord Webhooks Section -->
            <div class="account-section discord-webhooks-section">
                <h2><i class="fas fa-cloud-upload-alt"></i> Discord Webhooks</h2>
                
                <p class="description">Manage your Discord webhooks to send generated content directly to your Discord channels.</p>
                
                <div id="webhook-alert" class="alert" role="alert"></div>
                
                <!-- Webhook List -->
                <h3>Your Webhooks</h3>
                <div id="webhook-list-container">
                    <div id="webhook-list-loading" style="display: none; text-align: center; padding: 1rem;">
                        <i class="fas fa-spinner fa-spin"></i> Loading webhooks...
                    </div>
                     <div id="webhook-list-empty" style="display: none; text-align: center; padding: 1rem; opacity: 0.7;">
                        No webhooks added yet.
                    </div>
                    <ul id="webhook-list" class="webhook-list"></ul>
                </div>

                <!-- Add Webhook Button (triggers modal) -->
                <button id="show-add-webhook-modal-btn" class="btn btn-submit" style="margin-right: 1rem;">
                    <i class="fas fa-plus"></i> Add Webhook
                </button>

                <!-- Test Default Button -->
                 <button id="test-webhook-btn" class="btn btn-discord">
                    <i class="fas fa-paper-plane"></i> Test Default Webhook
                </button>
                
            </div>
            <!-- End Discord Webhooks Section -->

            <div class="account-section">
                <h2><i class="fas fa-users"></i> Party</h2>
                <div id="party-section">
                    <div id="party-loading" class="text-center">
                        <i class="fas fa-spinner fa-spin"></i> Loading party information...
                    </div>
                    
                    <!-- Party information will be loaded here -->
                    <div id="party-info" style="display: none;">
                        <!-- Container for dynamic party details (name, code) -->
                        <div id="party-details-content">
                            <!-- Party name and code will be injected here by JS -->
                        </div>
                        
                        <!-- Container for dynamic member list -->
                        <h4>Members</h4>
                        <div id="party-members-list" class="party-members">
                             <!-- Member list will be injected here by JS -->
                        </div>

                        <!-- Party Member Actions (visible only when in a party) -->
                        <div class="party-member-actions" style="display: none; margin-top: 1rem;">
                             <button id="rename-party-btn" class="btn btn-submit">
                                <i class="fas fa-edit"></i> Rename Party
                            </button>
                            <button id="leave-party-btn" class="btn btn-danger">
                                <i class="fas fa-sign-out-alt"></i> Leave Party
                            </button>
                        </div>
                    </div>

                    <!-- Party Action Buttons (visible when not in a party)-->
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

    <!-- Change Password Modal -->
    <div id="change-password-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal" data-modal-id="change-password-modal">&times;</span>
            <h3>Change Password</h3>
            <div id="change-password-alert" class="alert" role="alert"></div>
            <form id="change-password-form">
                <div class="form-group">
                    <label for="modal-password">New Password</label>
                    <input type="password" id="modal-password" placeholder="New password" required>
                </div>
                <div class="form-group">
                    <label for="modal-confirm-password">Confirm New Password</label>
                    <input type="password" id="modal-confirm-password" placeholder="Confirm new password" required>
                </div>
                <button type="submit" id="update-password-btn" class="btn btn-submit">Update Password</button>
            </form>
        </div>
    </div>

    <!-- Add Webhook Modal -->
    <div id="add-webhook-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h3>Add New Discord Webhook</h3>
            <form id="add-webhook-form" class="webhook-form">
                <div class="form-group form-group-url">
                    <label for="webhookUrl">Webhook URL</label>
                    <input type="url" id="webhookUrl" placeholder="Paste Discord Webhook URL here" required>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="serverName">Discord Server</label>
                        <input type="text" id="serverName" name="serverName" placeholder="e.g., My Campaign Server" required>
                    </div>
                    <div class="form-group">
                        <label for="discordChannelName">Discord Channel</label>
                        <input type="text" id="discordChannelName" name="discordChannelName" placeholder="e.g., #dice-rolls" required>
                    </div>
                </div>
                <button type="submit" id="add-webhook-btn" class="btn btn-submit">
                     <i class="fas fa-plus"></i> Add Webhook
                </button>
            </form>
            <div id="modal-webhook-alert" class="alert" role="alert" style="margin-top: 1rem;"></div> <!-- Alert specific to modal -->
        </div>
    </div>
    <!-- End Add Webhook Modal -->

    <!-- Edit Webhook Modal -->
    <div id="edit-webhook-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h3>Edit Discord Webhook</h3>
            <form id="edit-webhook-form" class="webhook-form">
                 <input type="hidden" id="editWebhookId" name="webhookId"> <!-- Hidden field for ID -->
                 <div class="form-group form-group-url">
                    <label for="editWebhookUrl">Webhook URL</label>
                    <input type="url" id="editWebhookUrl" name="webhookUrl" readonly disabled> <!-- Display only, not editable -->
                    <small>Webhook URL cannot be changed. Delete and re-add if needed.</small>
                </div>
                 <div class="form-grid">
                     <div class="form-group">
                         <label for="editServerName">Discord Server</label>
                         <input type="text" id="editServerName" name="serverName" placeholder="e.g., My Campaign Server" required>
                     </div>
                     <div class="form-group">
                         <label for="editDiscordChannelName">Discord Channel</label>
                         <input type="text" id="editDiscordChannelName" name="discordChannelName" placeholder="e.g., #dice-rolls" required>
                     </div>
                 </div>
                 <button type="submit" id="save-webhook-btn" class="btn btn-submit">
                      <i class="fas fa-save"></i> Save Changes
                 </button>
            </form>
             <div id="modal-edit-webhook-alert" class="alert" role="alert" style="margin-top: 1rem;"></div> <!-- Alert specific to edit modal -->
        </div>
    </div>
    <!-- End Edit Webhook Modal -->

    <!-- Pass Discord client ID to JavaScript -->
    <script>
        window.DISCORD_CLIENT_ID = '<?php echo DISCORD_CLIENT_ID; ?>';
    </script>

    <!-- Firebase Libraries (Order is important!) -->
    <script type="module" src="/js/firebase-auth.js"></script>
    <script src="/image_management/photo_manager.js"></script>
    <script type="module" src="/js/account.js"></script>
    <!-- Note: party functionality is now integrated directly in account.js -->
    <script type="module" src="/js/modal.js"></script>
    
    <?php include 'image_management/photo_manager_modal.php'; // UPDATED path ?>
    
    <!-- Create Party Modal -->
    <div id="create-party-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h3>Create New Party</h3>
            <form id="create-party-form">
                <div class="form-group">
                    <label for="party-name">Party Name</label>
                    <input type="text" id="party-name" required>
                </div>
                <button type="submit" class="btn btn-submit">Create Party</button>
            </form>
        </div>
    </div>

    <!-- Join Party Modal -->
    <div id="join-party-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h3>Join Existing Party</h3>
            <form id="join-party-form">
                <div class="form-group">
                    <label for="party-code">Party Code</label>
                    <input type="text" id="party-code" required>
                </div>
                <button type="submit" class="btn btn-submit">Join Party</button>
            </form>
        </div>
    </div>

    <!-- Rename Party Modal -->
    <div id="rename-party-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal" data-modal-id="rename-party-modal">&times;</span>
            <h3>Rename Party</h3>
            <div id="rename-party-alert" class="alert" role="alert"></div>
            <form id="rename-party-form">
                <input type="hidden" id="rename-party-id" name="partyId"> <!-- Hidden field for ID -->
                <div class="form-group">
                    <label for="rename-party-name">New Party Name</label>
                    <input type="text" id="rename-party-name" name="partyName" required>
                </div>
                <button type="submit" id="save-party-name-btn" class="btn btn-submit">Save Name</button>
            </form>
        </div>
    </div>

    <script src="js/utils.js" defer></script>
</body>
</html>