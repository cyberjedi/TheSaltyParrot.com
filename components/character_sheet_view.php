<?php
/**
 * Character Sheet View Component
 * Displays the character information and UI elements
 */
?>

<!-- Character Switcher Modal -->
<div id="character-switcher-modal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3>Character Selection</h3>
        
        <div class="character-debug-info">
            <p><strong>Using Database Characters:</strong> Found <?php echo count($user_characters); ?> characters</p>
            <?php if (isset($discord_id) && $discord_id): ?>
                <p><small>Discord ID: <?php echo $discord_id; ?> → User ID: <?php echo isset($db_user_id) ? $db_user_id : 'not set'; ?></small></p>
                <p><small>Discord User: <?php echo isset($_SESSION['discord_user']['username']) ? $_SESSION['discord_user']['username'] : 'unknown'; ?></small></p>
            <?php else: ?>
                <p><small>Not authenticated with Discord</small></p>
            <?php endif; ?>
        </div>
        
        <?php if (count($user_characters) > 0): ?>
            <div class="character-list">
                <?php foreach ($user_characters as $char): ?>
                    <div class="character-list-item <?php echo ($character && $character['id'] == $char['id']) ? 'active' : ''; ?>">
                        <div class="character-list-info">
                            <?php
                            // Get character image with validation
                            $charImagePath = !empty($char['image_path']) ? $char['image_path'] : '';
                            // Check if the image file actually exists, otherwise use default
                            $charImage = (!empty($charImagePath) && file_exists($charImagePath)) 
                                ? htmlspecialchars($charImagePath) 
                                : 'assets/TSP_default_character.jpg';
                            ?>
                            <div class="character-list-avatar">
                                <img src="<?php echo $charImage; ?>" alt="Character Portrait" onerror="this.src='assets/TSP_default_character.jpg'">
                            </div>
                            <div class="character-list-details">
                                <span class="character-name"><?php echo htmlspecialchars($char['name']); ?></span>
                                <span class="character-user-id">(User ID: <?php echo isset($char['user_id']) ? $char['user_id'] : 'unknown'; ?>)</span>
                                
                                <!-- Add additional debug info -->
                                <span class="character-debug" style="font-size: 0.7rem; color: #999;">
                                    ID: <?php echo $char['id']; ?>, 
                                    <?php if (isset($char['strength'])): ?>STR: <?php echo $char['strength']; ?><?php endif; ?>
                                </span>
                                
                                <?php if ($character && $character['id'] == $char['id']): ?>
                                    <span class="current-badge">Current</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="character-list-actions">
                            <a href="/character_sheet.php?id=<?php echo $char['id']; ?>" class="btn btn-primary btn-sm">
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
        <h1>Character Sheet</h1>
    </div>
    <div class="actions">
        <!-- Always show the switch character button, even if there are no characters yet -->
        <?php if ($discord_authenticated): ?>
        <button id="switch-character-btn" class="btn btn-secondary">
            <i class="fas fa-exchange-alt"></i> Switch Character
        </button>
        <?php endif; ?>
        
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

<?php if (!$discord_authenticated): ?>
<div class="alert alert-warning">
    <i class="fab fa-discord"></i> <?php echo isset($auth_message) ? $auth_message : 'Connect with Discord to save and manage characters.'; ?>
</div>
<?php endif; ?>

<?php if ($error_message): ?>
<div class="alert alert-error">
    <?php echo $error_message; ?>
</div>
<?php endif; ?>

<?php if (isset($_GET['success']) || $success_message): ?>
<div class="alert alert-success">
    <?php echo $success_message ?? 'Character saved successfully!'; ?>
</div>
<?php endif; ?>

<div class="content-container">
    <div class="character-sheet">
        <div class="character-sheet-inner">
            <!-- Character Sheet Header -->
            <div class="character-header">
                <div class="character-image">
                    <?php
                    // Validate image path
                    $mainImagePath = !empty($character['image_path']) ? $character['image_path'] : '';
                    $mainImage = (!empty($mainImagePath) && file_exists($mainImagePath)) 
                        ? htmlspecialchars($mainImagePath) 
                        : 'assets/TSP_default_character.jpg';
                    ?>
                    <img src="<?php echo $mainImage; ?>" alt="Character Portrait" onerror="this.src='assets/TSP_default_character.jpg'">
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
</div>

<!-- Edit Character Modal -->
<div id="edit-character-modal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3>Edit Character</h3>
        
        <form method="post" action="" id="edit-character-form" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_character">
            <input type="hidden" name="character_id" value="<?php echo $character['id']; ?>">
            
            <div class="form-group">
                <label for="name">Character Name:</label>
                <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($character['name']); ?>">
            </div>
            
            <div class="form-group">
                <label for="character_image">Character Image:</label>
                <div class="image-upload-container">
                    <div class="image-preview-container">
                        <div class="current-image-wrapper">
                            <?php
                            // Validate modal image path
                            $modalImagePath = !empty($character['image_path']) ? $character['image_path'] : '';
                            $modalImage = (!empty($modalImagePath) && file_exists($modalImagePath)) 
                                ? htmlspecialchars($modalImagePath) 
                                : 'assets/TSP_default_character.jpg';
                            ?>
                            <img src="<?php echo $modalImage; ?>" alt="Current Image" id="image-preview">
                        </div>
                    </div>
                    <div class="file-input-wrapper">
                        <input type="file" id="character_image" name="character_image" accept="image/jpeg,image/png,image/gif">
                        <p class="help-text">Recommended size: 200x200 pixels. Max file size: 2MB.</p>
                    </div>
                </div>
            </div>
            
            <h4>Character Stats</h4>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="strength">Strength:</label>
                    <input type="number" id="strength" name="strength" min="-3" max="10" value="<?php echo (int)$character['strength']; ?>">
                </div>
                
                <div class="form-group">
                    <label for="agility">Agility:</label>
                    <input type="number" id="agility" name="agility" min="-3" max="10" value="<?php echo (int)$character['agility']; ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="presence">Presence:</label>
                    <input type="number" id="presence" name="presence" min="-3" max="10" value="<?php echo (int)$character['presence']; ?>">
                </div>
                
                <div class="form-group">
                    <label for="toughness">Toughness:</label>
                    <input type="number" id="toughness" name="toughness" min="-3" max="10" value="<?php echo (int)$character['toughness']; ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="spirit">Spirit:</label>
                    <input type="number" id="spirit" name="spirit" min="-3" max="10" value="<?php echo (int)$character['spirit']; ?>">
                </div>
            </div>
            
            <div class="form-buttons">
                <button type="submit" class="btn btn-primary">Save Character</button>
                <button type="button" class="btn btn-secondary close-modal-btn">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Character sheet styles are loaded from css/character_sheet.css -->

<!-- Pass authentication status to JS -->
<script>
// Set authentication status for the character sheet JS
window.discord_authenticated = <?php echo $discord_authenticated ? 'true' : 'false'; ?>;
</script>
<!-- External character sheet JS -->
<script src="/js/character_sheet.js"></script>