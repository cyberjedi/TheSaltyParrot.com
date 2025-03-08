<?php
// fixed_character_sheet.php - A modified version with hardcoded characters

// Start the session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set the current page
$current_page = 'character_sheet';

// Set base path for consistent loading
$base_path = './';

// Always use hardcoded characters for this test page
$_GET['use_hardcoded'] = true;

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

// Initialize variables used in the character sheet component
$user_characters = []; // Will be populated with the user's characters
$discord_authenticated = function_exists('is_discord_authenticated') && is_discord_authenticated();

// Hardcoded characters to ensure they always appear
$user_characters = [
    [
        'id' => 1,
        'user_id' => 1,
        'name' => 'Test Pirate',
        'image_path' => 'uploads/characters/character_1741469717_67ccb815a80be.jpg',
        'strength' => 3,
        'agility' => -2,
        'presence' => 1,
        'toughness' => 0,
        'spirit' => 2
    ],
    [
        'id' => 2,
        'user_id' => 1,
        'name' => 'Test Pirate 2',
        'image_path' => 'uploads/characters/character_1741469717_67ccb815a80be.jpg',
        'strength' => 1,
        'agility' => 2,
        'presence' => 3,
        'toughness' => -1,
        'spirit' => -1
    ],
    [
        'id' => 3,
        'user_id' => 1,
        'name' => 'New Pirate 3',
        'image_path' => 'uploads/characters/character_1741469717_67ccb815a80be.jpg',
        'strength' => 1,
        'agility' => 0,
        'presence' => 1,
        'toughness' => 0,
        'spirit' => 0
    ],
    [
        'id' => 4,
        'user_id' => 1,
        'name' => 'New Pirate',
        'image_path' => 'uploads/characters/character_1741469717_67ccb815a80be.jpg',
        'strength' => 0,
        'agility' => 0,
        'presence' => 0,
        'toughness' => 0,
        'spirit' => 0
    ]
];

// Set a character if one is selected
$character = null;
if (isset($_GET['id'])) {
    $char_id = (int)$_GET['id'];
    
    // Find the character with this ID
    foreach ($user_characters as $char) {
        if ($char['id'] == $char_id) {
            $character = $char;
            break;
        }
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
    <style>
        .character-list { margin: 20px 0; max-height: 400px; overflow-y: auto; }
        .character-list-item { display: flex; justify-content: space-between; align-items: center; padding: 12px; border: 1px solid #e0e0e0; margin-bottom: 10px; border-radius: 8px; background-color: #fff; }
        .character-list-item:hover { border-color: #bf9d61; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1); }
        .character-list-item.active { border-color: #bf9d61; background-color: #fcf7ee; }
        .character-list-info { display: flex; align-items: center; flex: 1; }
        .character-list-avatar { width: 50px; height: 50px; border-radius: 50%; overflow: hidden; border: 2px solid #bf9d61; margin-right: 15px; flex-shrink: 0; }
        .character-list-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .character-list-details { flex: 1; }
        .character-name { font-weight: bold; font-size: 1.1rem; color: #1a2639; display: block; }
        .character-user-id { font-size: 0.8rem; color: #666; display: block; margin-top: 2px; }
        .character-list-actions { margin-left: 10px; }
        .current-badge { display: inline-block; background-color: #e0f0e0; color: #2c8527; font-size: 0.7rem; padding: 2px 8px; border-radius: 20px; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid #c0e0c0; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.7); }
        .modal-content { background-color: #f9f5eb; margin: 10% auto; padding: 25px; border: 1px solid #bf9d61; border-radius: 8px; width: 80%; max-width: 550px; position: relative; }
        .btn-sm { padding: 5px 10px; font-size: 0.85rem; }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Include the sidebar -->
        <?php include 'components/sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Character Switcher Modal -->
            <div id="character-switcher-modal" class="modal">
                <div class="modal-content">
                    <span class="close-modal">&times;</span>
                    <h3>Character Selection</h3>
                    
                    <div class="character-debug-info" style="margin-bottom: 20px; padding: 10px; background: #f5f5f5; border-radius: 5px;">
                        <p><strong>Using Hardcoded Characters:</strong> Found <?php echo count($user_characters); ?> characters</p>
                        <p><small>This is a test page using hardcoded characters</small></p>
                    </div>
                    
                    <?php if (count($user_characters) > 0): ?>
                        <div class="character-list">
                            <?php foreach ($user_characters as $char): ?>
                                <div class="character-list-item <?php echo ($character && $character['id'] == $char['id']) ? 'active' : ''; ?>">
                                    <div class="character-list-info">
                                        <?php
                                        // Get character image
                                        $charImage = !empty($char['image_path']) ? htmlspecialchars($char['image_path']) : 'assets/TSP_default_character.jpg';
                                        ?>
                                        <div class="character-list-avatar">
                                            <img src="<?php echo $charImage; ?>" alt="Character Portrait" onerror="this.src='assets/TSP_default_character.jpg'">
                                        </div>
                                        <div class="character-list-details">
                                            <span class="character-name"><?php echo htmlspecialchars($char['name']); ?></span>
                                            <span class="character-user-id">(User ID: <?php echo isset($char['user_id']) ? $char['user_id'] : 'unknown'; ?>)</span>
                                            
                                            <!-- Add additional debug info -->
                                            <span style="font-size: 0.7rem; color: #999;">
                                                ID: <?php echo $char['id']; ?>, 
                                                STR: <?php echo $char['strength']; ?>
                                            </span>
                                            
                                            <?php if ($character && $character['id'] == $char['id']): ?>
                                                <span class="current-badge">Current</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="character-list-actions">
                                        <a href="fixed_character_sheet.php?id=<?php echo $char['id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-user"></i> Select
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-characters">
                            <i class="fas fa-user-slash" style="font-size: 3rem; color: var(--secondary); margin-bottom: 20px;"></i>
                            <p>You don't have any characters yet.</p>
                            <p>Create your first character to get started!</p>
                            <div style="margin-top: 15px; padding: 10px; background: #ffe; border: 1px solid #ddc; border-radius: 5px;">
                                <p><strong>Note:</strong> The system cannot find any characters in the database. Please check the character table structure.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-buttons">
                        <button type="button" class="btn btn-secondary close-modal-btn">Close</button>
                        <?php if ($discord_authenticated): ?>
                        <button type="button" class="btn btn-primary" id="create-new-from-switcher">
                            <i class="fas fa-plus-circle"></i> Create New Character
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-header">
                <div class="logo">
                    <i class="fas fa-scroll"></i>
                    <h1>Character Sheet (Fixed Version)</h1>
                </div>
                <div class="actions">
                    <button id="switch-character-btn" class="btn btn-secondary">
                        <i class="fas fa-exchange-alt"></i> Switch Character
                    </button>
                    
                    <button id="print-character-btn" class="btn btn-secondary">
                        <i class="fas fa-print"></i> Print
                    </button>
                    
                    <?php if ($discord_authenticated): ?>
                    <button id="new-character-btn" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Character
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="content-container">
                <?php if ($character): ?>
                    <div class="character-sheet">
                        <div class="character-sheet-inner">
                            <!-- Character Sheet Header -->
                            <div class="character-header">
                                <div class="character-image">
                                    <img src="<?php echo htmlspecialchars($character['image_path']); ?>" alt="Character Portrait" onerror="this.src='assets/TSP_default_character.jpg'">
                                </div>
                                <div class="character-title">
                                    <h2 id="character-name"><?php echo htmlspecialchars($character['name']); ?></h2>
                                </div>
                                <div class="edit-button">
                                    <button id="edit-character-btn" class="btn-icon" title="Edit Character">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Character Stats -->
                            <div class="character-stats">
                                <div class="stat-group">
                                    <div class="stat-box">
                                        <div class="stat-label">Strength</div>
                                        <div class="stat-value"><?php echo (int)$character['strength']; ?></div>
                                    </div>
                                    <div class="stat-box">
                                        <div class="stat-label">Agility</div>
                                        <div class="stat-value"><?php echo (int)$character['agility']; ?></div>
                                    </div>
                                    <div class="stat-box">
                                        <div class="stat-label">Presence</div>
                                        <div class="stat-value"><?php echo (int)$character['presence']; ?></div>
                                    </div>
                                    <div class="stat-box">
                                        <div class="stat-label">Toughness</div>
                                        <div class="stat-value"><?php echo (int)$character['toughness']; ?></div>
                                    </div>
                                    <div class="stat-box">
                                        <div class="stat-label">Spirit</div>
                                        <div class="stat-value"><?php echo (int)$character['spirit']; ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Additional character information can be added here in future updates -->
                            <div class="character-details">
                                <!-- Placeholder for additional character details -->
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="placeholder-display" style="text-align: center; padding: 50px 20px;">
                        <i class="fas fa-scroll" style="font-size: 4rem; color: var(--secondary); margin-bottom: 20px;"></i>
                        <h2>Select a Character</h2>
                        <p>Click on "Switch Character" to select a character from your list, or create a new one.</p>
                        <button id="open-switcher-btn" class="btn primary-btn" style="margin-top: 20px;">
                            <i class="fas fa-exchange-alt"></i> Open Character Selection
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <footer>
        <p>The Salty Parrot is an independent production by Stuart Greenwell. It is not affiliated with Limithron LLC. It is published under the PIRATE BORG Third Party License. PIRATE BORG is ©2022 Limithron LLC.</p>
        <p>&copy; 2025 The Salty Parrot</p>
    </footer>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Modal elements
            const switcherModal = document.getElementById('character-switcher-modal');
            const switchBtn = document.getElementById('switch-character-btn');
            const closeBtns = document.querySelectorAll('.close-modal');
            const closeFormBtns = document.querySelectorAll('.close-modal-btn');
            const openSwitcherBtn = document.getElementById('open-switcher-btn');
            
            // Open switcher modal
            function openSwitcherModal() {
                switcherModal.style.display = 'block';
            }
            
            // Close modals
            function closeModals() {
                switcherModal.style.display = 'none';
            }
            
            // Add event listeners
            if (switchBtn) {
                switchBtn.addEventListener('click', openSwitcherModal);
            }
            
            if (openSwitcherBtn) {
                openSwitcherBtn.addEventListener('click', openSwitcherModal);
            }
            
            // Close buttons
            closeBtns.forEach(function(btn) {
                btn.addEventListener('click', closeModals);
            });
            
            closeFormBtns.forEach(function(btn) {
                btn.addEventListener('click', closeModals);
            });
            
            // Close when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === switcherModal) {
                    closeModals();
                }
            });
            
            <?php if (!$character): ?>
            // Automatically open the character switcher if no character is selected
            setTimeout(openSwitcherModal, 500);
            <?php endif; ?>
        });
    </script>
</body>
</html>