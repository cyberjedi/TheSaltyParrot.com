<?php
// Set the current page
$current_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - The Salty Parrot</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
    <div class="app-container">
        <!-- Include the sidebar -->
        <?php include '../components/sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <main class="main-content">
            <div class="dashboard-header">
                <div class="logo">
                    <i class="fas fa-skull-crossbones"></i>
                    <h1>Dashboard</h1>
                </div>
                <div class="user-controls">
                    <span id="user-email" class="user-email">Loading...</span>
                    <button id="logout-btn-top" class="btn btn-secondary btn-sm">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </div>
            </div>
            
            <!-- New Crew Bar -->
            <div class="crew-bar" id="crew-bar">
                <!-- No Session View - Will be shown/hidden via JavaScript -->
                <div id="no-session" style="display: block; width: 100%;">
                    <div class="no-crew-message">
                        <i class="fas fa-anchor"></i> No active crew - Create or join a game session
                    </div>
                    <div class="crew-actions">
                        <button id="create-session-btn" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Create Game Session
                        </button>
                        <button id="join-session-btn" class="btn btn-secondary btn-sm">
                            <i class="fas fa-sign-in-alt"></i> Join Game Session
                        </button>
                    </div>
                </div>
                
                <!-- Active Session View - Will be shown/hidden via JavaScript -->
                <div id="active-session" style="display: none; width: 100%;">
                    <div class="session-info">
                        <h4 id="session-name">The Salty Voyage</h4>
                        <div id="session-code">Join Code: <span id="join-code">ABC123</span></div>
                    </div>
                    
                    <div class="crew-members" id="crew-members-list">
                        <!-- Crew member tags will be added here dynamically -->
                        <div class="crew-member gm">
                            <i class="fas fa-crown"></i> Captain John (You)
                        </div>
                        <div class="crew-member">
                            <i class="fas fa-user"></i> Sailor Mike
                        </div>
                    </div>
                    
                    <div class="crew-actions">
                        <button id="leave-session-btn" class="btn btn-outline btn-sm">
                            <i class="fas fa-sign-out-alt"></i> Leave Crew
                        </button>
                        <button id="copy-code-btn" class="btn btn-outline btn-sm">
                            <i class="fas fa-copy"></i> Copy Code
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-container">
                <!-- Character Display Box -->
                <div class="character-box">
                    <h3 class="box-title">
                        Current Character
                        <div class="actions">
                            <button id="edit-character-btn" title="Edit Character"><i class="fas fa-edit"></i></button>
                            <button id="character-menu-btn" title="Character Menu"><i class="fas fa-ellipsis-v"></i></button>
                        </div>
                    </h3>
                    <div id="character-display">
                        <div class="no-character">
                            <i class="fas fa-user-slash"></i>
                            <p>No active character selected</p>
                            <button class="btn btn-outline" id="create-character-btn">
                                <i class="fas fa-plus"></i> Create Character
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- My Ship Box - replacing Output Box -->
                <div class="ship-box">
                    <h3 class="box-title">
                        My Ship
                        <div class="actions">
                            <button id="edit-ship-btn" title="Edit Ship"><i class="fas fa-edit"></i></button>
                            <button id="ship-menu-btn" title="Ship Menu"><i class="fas fa-ellipsis-v"></i></button>
                        </div>
                    </h3>
                    <div id="ship-display">
                        <div class="no-ship">
                            <i class="fas fa-ship"></i>
                            <p>No active ship selected</p>
                            <button class="btn btn-outline" id="select-ship-btn">
                                <i class="fas fa-plus"></i> Select Ship
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Game Log Box -->
                <div class="game-log">
                    <h3 class="box-title">
                        Game Log
                        <div class="actions">
                            <button id="save-log-btn" title="Save Log"><i class="fas fa-save"></i></button>
                            <button id="clear-log-btn" title="Clear Log"><i class="fas fa-trash"></i></button>
                        </div>
                    </h3>
                    
                    <!-- Log entries container -->
                    <div id="log-display" style="max-height: calc(100% - 100px); overflow-y: auto;">
                        <p style="text-align: center; padding: 30px 0;">
                            <i class="fas fa-scroll" style="font-size: 2rem; color: var(--secondary); opacity: 0.4; display: block; margin-bottom: 15px;"></i>
                            Game events will appear here once you join a crew
                        </p>
                    </div>
                    
                    <!-- Custom log entry input -->
                    <div id="log-controls" style="display: none; margin-top: 15px;">
                        <div style="display: flex; gap: 10px;">
                            <input type="text" id="custom-log-input" placeholder="Add to the ship's log...">
                            <button id="add-log-btn" class="btn btn-outline btn-sm">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Generator Modal - New Popup for generators -->
    <div id="generator-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-title">Generator Results</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body" id="modal-content">
                <!-- Generated content will be inserted here -->
            </div>
            <div class="modal-footer">
                <button id="regenerate-btn" class="btn btn-primary">
                    <i class="fas fa-redo"></i> Regenerate
                </button>
                <button id="copy-to-clipboard-btn" class="btn btn-secondary">
                    <i class="fas fa-copy"></i> Copy
                </button>
                <button id="print-result-btn" class="btn btn-secondary">
                    <i class="fas fa-print"></i> Print
                </button>
                <button id="send-to-log-btn" class="btn btn-secondary">
                    <i class="fas fa-comment"></i> Send to Log
                </button>
            </div>
        </div>
    </div>
    
    <footer>
        <p>The Salty Parrot is an independent production by Stuart Greenwell. It is not affiliated with Limithron LLC. It is published under the PIRATE BORG Third Party License. PIRATE BORG is ©2022 Limithron LLC.</p>
        <p>&copy; 2025 The Salty Parrot</p>
    </footer>

    <!-- Firebase scripts -->
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-auth-compat.js"></script>
    
    <!-- Core JS -->
    <script src="../js/firebase-config.js"></script>
    <script src="../js/dashboard.js"></script>
    <script src="../js/game-session.js"></script>
    <script src="../js/generators.js"></script>
</body>
</html>
