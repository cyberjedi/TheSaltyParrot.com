<?php
// standalone_character_sheet.php - A completely standalone character sheet with no database dependency
session_start();

// Set the base path
$base_path = './';
$current_page = 'character_sheet';

// Hardcoded characters data
$all_characters = [
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
    ],
    [
        'id' => 5,
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

// Store characters in session for persistence
if (!isset($_SESSION['standalone_characters'])) {
    $_SESSION['standalone_characters'] = $all_characters;
}

$user_characters = $_SESSION['standalone_characters'];

// Process character creation/editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_character') {
    $char_id = isset($_POST['character_id']) ? $_POST['character_id'] : '';
    $name = htmlspecialchars($_POST['name']);
    $strength = (int)$_POST['strength'];
    $agility = (int)$_POST['agility'];
    $presence = (int)$_POST['presence'];
    $toughness = (int)$_POST['toughness'];
    $spirit = (int)$_POST['spirit'];
    
    $image_path = 'uploads/characters/character_1741469717_67ccb815a80be.jpg';
    
    if (empty($char_id)) {
        // New character - generate a new ID
        $new_id = count($user_characters) + 1;
        $new_char = [
            'id' => $new_id,
            'user_id' => 1,
            'name' => $name,
            'image_path' => $image_path,
            'strength' => $strength,
            'agility' => $agility,
            'presence' => $presence,
            'toughness' => $toughness,
            'spirit' => $spirit
        ];
        
        // Add to session
        $user_characters[] = $new_char;
        $_SESSION['standalone_characters'] = $user_characters;
        
        // Redirect to show the new character
        header("Location: standalone_character_sheet.php?id=" . $new_id . "&success=1");
        exit;
    } else {
        // Update existing character
        $char_id = (int)$char_id;
        foreach ($user_characters as $key => $char) {
            if ($char['id'] == $char_id) {
                $user_characters[$key]['name'] = $name;
                $user_characters[$key]['strength'] = $strength;
                $user_characters[$key]['agility'] = $agility;
                $user_characters[$key]['presence'] = $presence;
                $user_characters[$key]['toughness'] = $toughness;
                $user_characters[$key]['spirit'] = $spirit;
                break;
            }
        }
        
        // Update session
        $_SESSION['standalone_characters'] = $user_characters;
        
        // Redirect
        header("Location: standalone_character_sheet.php?id=" . $char_id . "&success=1");
        exit;
    }
}

// Get the current character if ID provided
$character = null;
$character_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($character_id) {
    foreach ($user_characters as $char) {
        if ($char['id'] == $character_id) {
            $character = $char;
            break;
        }
    }
}

// Discord integration - just for the UI, not for functionality
$discord_authenticated = true;
$discord_enabled = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Standalone Character Sheet - The Salty Parrot</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/discord.css">
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
        
        /* Character Sheet Specific Styles */
        .character-sheet {
            background-color: #fff;
            color: #333;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            padding: 0;
            margin: 0 auto;
            max-width: 800px;
        }

        .character-sheet-inner {
            padding: 30px;
        }

        .character-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            position: relative;
            border-bottom: 2px solid #bf9d61;
            padding-bottom: 20px;
        }

        .character-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid #bf9d61;
            margin-right: 20px;
            flex-shrink: 0;
        }

        .character-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .character-title {
            flex: 1;
        }

        .character-title h2 {
            margin: 0;
            color: #1a2639;
            font-size: 2rem;
        }

        .edit-button {
            position: absolute;
            top: 0;
            right: 0;
        }

        .btn-icon {
            background: none;
            border: none;
            color: #bf9d61;
            cursor: pointer;
            font-size: 1.2rem;
            padding: 5px;
            transition: color 0.2s;
        }

        .btn-icon:hover {
            color: #805d2c;
        }

        .character-stats {
            margin-bottom: 30px;
        }

        .stat-group {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: space-between;
        }

        .stat-box {
            flex: 1;
            min-width: 100px;
            border: 2px solid #bf9d61;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            background-color: #f9f5eb;
        }

        .stat-label {
            font-weight: bold;
            color: #805d2c;
            margin-bottom: 10px;
            font-size: 1rem;
            text-transform: uppercase;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #1a2639;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }

        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #1a2639;
        }

        .form-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #bf9d61;
            border-radius: 4px;
            font-size: 1rem;
        }

        .form-buttons {
            margin-top: 25px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .alert {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Dashboard Header Adjustments */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dashboard-header .actions {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Include the sidebar for navigation -->
        <?php include 'components/sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Character Switcher Modal -->
            <div id="character-switcher-modal" class="modal">
                <div class="modal-content">
                    <span class="close-modal">&times;</span>
                    <h3>Character Selection</h3>
                    
                    <div class="character-debug-info" style="margin-bottom: 20px; padding: 10px; background: #f5f5f5; border-radius: 5px;">
                        <p><strong>STANDALONE MODE:</strong> Found <?php echo count($user_characters); ?> characters</p>
                        <p style="color: green;"><small><strong>Note:</strong> This is a standalone version that uses session storage instead of the database.</small></p>
                    </div>
                    
                    <?php if (count($user_characters) > 0): ?>
                        <div class="character-list">
                            <?php foreach ($user_characters as $char): ?>
                                <div class="character-list-item <?php echo ($character && $character['id'] == $char['id']) ? 'active' : ''; ?>">
                                    <div class="character-list-info">
                                        <div class="character-list-avatar">
                                            <img src="<?php echo htmlspecialchars($char['image_path']); ?>" alt="Character Portrait" onerror="this.src='assets/TSP_default_character.jpg'">
                                        </div>
                                        <div class="character-list-details">
                                            <span class="character-name"><?php echo htmlspecialchars($char['name']); ?></span>
                                            <span class="character-user-id">(ID: <?php echo $char['id']; ?>)</span>
                                            
                                            <span style="font-size: 0.7rem; color: #999;">
                                                STR: <?php echo $char['strength']; ?>,
                                                AGI: <?php echo $char['agility']; ?>,
                                                PRE: <?php echo $char['presence']; ?>,
                                                TOU: <?php echo $char['toughness']; ?>,
                                                SPI: <?php echo $char['spirit']; ?>
                                            </span>
                                            
                                            <?php if ($character && $character['id'] == $char['id']): ?>
                                                <span class="current-badge">Current</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="character-list-actions">
                                        <a href="standalone_character_sheet.php?id=<?php echo $char['id']; ?>" class="btn btn-primary btn-sm">
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
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-buttons">
                        <button type="button" class="btn btn-secondary close-modal-btn">Close</button>
                        <button type="button" class="btn btn-primary" id="create-new-from-switcher">
                            <i class="fas fa-plus-circle"></i> Create New Character
                        </button>
                    </div>
                </div>
            </div>

            <!-- Edit Character Modal -->
            <div id="edit-character-modal" class="modal">
                <div class="modal-content">
                    <span class="close-modal">&times;</span>
                    <h3><?php echo empty($character_id) ? 'Create New Character' : 'Edit Character'; ?></h3>
                    
                    <form method="post" action="standalone_character_sheet.php" id="edit-character-form">
                        <input type="hidden" name="action" value="update_character">
                        <input type="hidden" name="character_id" value="<?php echo $character ? $character['id'] : ''; ?>">
                        
                        <div class="form-group">
                            <label for="name">Character Name:</label>
                            <input type="text" id="name" name="name" required value="<?php echo $character ? htmlspecialchars($character['name']) : 'New Pirate'; ?>">
                        </div>
                        
                        <h4>Character Stats</h4>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="strength">Strength:</label>
                                <input type="number" id="strength" name="strength" min="-3" max="10" value="<?php echo $character ? (int)$character['strength'] : 0; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="agility">Agility:</label>
                                <input type="number" id="agility" name="agility" min="-3" max="10" value="<?php echo $character ? (int)$character['agility'] : 0; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="presence">Presence:</label>
                                <input type="number" id="presence" name="presence" min="-3" max="10" value="<?php echo $character ? (int)$character['presence'] : 0; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="toughness">Toughness:</label>
                                <input type="number" id="toughness" name="toughness" min="-3" max="10" value="<?php echo $character ? (int)$character['toughness'] : 0; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="spirit">Spirit:</label>
                                <input type="number" id="spirit" name="spirit" min="-3" max="10" value="<?php echo $character ? (int)$character['spirit'] : 0; ?>">
                            </div>
                        </div>
                        
                        <div class="form-buttons">
                            <button type="submit" class="btn btn-primary">Save Character</button>
                            <button type="button" class="btn btn-secondary close-modal-btn">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="dashboard-header">
                <div class="logo">
                    <i class="fas fa-scroll"></i>
                    <h1>Standalone Character Sheet</h1>
                </div>
                <div class="actions">
                    <button id="switch-character-btn" class="btn btn-secondary">
                        <i class="fas fa-exchange-alt"></i> Switch Character
                    </button>
                    
                    <button id="print-character-btn" class="btn btn-secondary">
                        <i class="fas fa-print"></i> Print
                    </button>
                    
                    <button id="new-character-btn" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Character
                    </button>
                </div>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                Character saved successfully!
            </div>
            <?php endif; ?>
            
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
            const editModal = document.getElementById('edit-character-modal');
            const switchBtn = document.getElementById('switch-character-btn');
            const newCharBtn = document.getElementById('new-character-btn');
            const editBtn = document.getElementById('edit-character-btn');
            const createNewFromSwitcherBtn = document.getElementById('create-new-from-switcher');
            const closeBtns = document.querySelectorAll('.close-modal');
            const closeFormBtns = document.querySelectorAll('.close-modal-btn');
            const openSwitcherBtn = document.getElementById('open-switcher-btn');
            
            // Open switcher modal
            function openSwitcherModal() {
                switcherModal.style.display = 'block';
                editModal.style.display = 'none';
            }
            
            // Open edit modal
            function openEditModal() {
                editModal.style.display = 'block';
                switcherModal.style.display = 'none';
            }
            
            // Close all modals
            function closeModals() {
                switcherModal.style.display = 'none';
                editModal.style.display = 'none';
            }
            
            // Add event listeners
            if (switchBtn) {
                switchBtn.addEventListener('click', openSwitcherModal);
            }
            
            if (newCharBtn) {
                newCharBtn.addEventListener('click', function() {
                    // Reset form for new character
                    document.getElementById('edit-character-form').reset();
                    document.querySelector('input[name="character_id"]').value = '';
                    document.getElementById('name').value = 'New Pirate';
                    document.getElementById('strength').value = '0';
                    document.getElementById('agility').value = '0';
                    document.getElementById('presence').value = '0';
                    document.getElementById('toughness').value = '0';
                    document.getElementById('spirit').value = '0';
                    
                    // Open the modal
                    openEditModal();
                });
            }
            
            if (editBtn) {
                editBtn.addEventListener('click', openEditModal);
            }
            
            if (createNewFromSwitcherBtn) {
                createNewFromSwitcherBtn.addEventListener('click', function() {
                    // Close switcher modal
                    switcherModal.style.display = 'none';
                    
                    // Reset form for new character
                    document.getElementById('edit-character-form').reset();
                    document.querySelector('input[name="character_id"]').value = '';
                    document.getElementById('name').value = 'New Pirate';
                    document.getElementById('strength').value = '0';
                    document.getElementById('agility').value = '0';
                    document.getElementById('presence').value = '0';
                    document.getElementById('toughness').value = '0';
                    document.getElementById('spirit').value = '0';
                    
                    // Open edit modal
                    openEditModal();
                });
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
                if (event.target === editModal) {
                    closeModals();
                }
            });
            
            // Print button
            const printBtn = document.getElementById('print-character-btn');
            if (printBtn) {
                printBtn.addEventListener('click', function() {
                    window.print();
                });
            }
            
            <?php if (!$character): ?>
            // Automatically open the character switcher if no character is selected
            setTimeout(openSwitcherModal, 500);
            <?php endif; ?>
            
            // Hide success alert after 3 seconds
            const successAlert = document.querySelector('.alert-success');
            if (successAlert) {
                setTimeout(function() {
                    successAlert.style.display = 'none';
                }, 3000);
            }
        });
    </script>
    
    <div id="discord-modal-container"></div>
</body>
</html>