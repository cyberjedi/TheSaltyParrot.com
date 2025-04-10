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
                                <?php if ($character && $character['id'] == $char['id']): ?>
                                    <span class="current-badge">Current</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="character-list-actions">
                            <a href="/character_sheet.php?id=<?php echo $char['id']; ?>" class="btn btn-primary btn-sm">
                                Select
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-characters">
                <i class="fas fa-user-slash" style="font-size: 3rem; color: var(--secondary); margin-bottom: 20px;"></i>
                <p style="color: #333;">You don't have any characters yet.</p>
                <p style="color: #333;">Create your first character to get started!</p>
            </div>
        <?php endif; ?>
        
        <div class="form-buttons">
            <button type="button" class="btn btn-secondary close-modal-btn">Close</button>
            <?php if ($discord_authenticated): ?>
            <button type="button" class="btn btn-primary" id="create-new-from-switcher">
                Create New Character
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
                <?php if (isset($character['system']) && $character['system'] === 'pirate_borg'): ?>
                <div class="pirate-borg-logo">
                    <img src="assets/Pirate_Borg_Compatible_Vert_White.png" alt="Pirate Borg Compatible">
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Character Stats -->
            <div class="character-stats">
                <h3 class="stats-header">Attributes</h3>
                <table class="stats-table">
                    <thead>
                        <tr>
                            <th class="stat-name-col">Attribute</th>
                            <th class="stat-value-col">Value</th>
                            <th class="stat-roll-col">Roll</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Strength -->
                        <tr class="stat-row" data-attribute="strength" data-value="<?php echo (int)$character['strength']; ?>">
                            <td class="stat-name">Strength</td>
                            <td class="stat-value"><?php echo (int)$character['strength']; ?></td>
                            <td class="stat-roll-cell">
                                <button class="stat-roll-btn" title="Roll Strength check">
                                    <i class="fas fa-dice-d20"></i>
                                </button>
                            </td>
                        </tr>
                        <!-- Agility -->
                        <tr class="stat-row" data-attribute="agility" data-value="<?php echo (int)$character['agility']; ?>">
                            <td class="stat-name">Agility</td>
                            <td class="stat-value"><?php echo (int)$character['agility']; ?></td>
                            <td class="stat-roll-cell">
                                <button class="stat-roll-btn" title="Roll Agility check">
                                    <i class="fas fa-dice-d20"></i>
                                </button>
                            </td>
                        </tr>
                        <!-- Presence -->
                        <tr class="stat-row" data-attribute="presence" data-value="<?php echo (int)$character['presence']; ?>">
                            <td class="stat-name">Presence</td>
                            <td class="stat-value"><?php echo (int)$character['presence']; ?></td>
                            <td class="stat-roll-cell">
                                <button class="stat-roll-btn" title="Roll Presence check">
                                    <i class="fas fa-dice-d20"></i>
                                </button>
                            </td>
                        </tr>
                        <!-- Toughness -->
                        <tr class="stat-row" data-attribute="toughness" data-value="<?php echo (int)$character['toughness']; ?>">
                            <td class="stat-name">Toughness</td>
                            <td class="stat-value"><?php echo (int)$character['toughness']; ?></td>
                            <td class="stat-roll-cell">
                                <button class="stat-roll-btn" title="Roll Toughness check">
                                    <i class="fas fa-dice-d20"></i>
                                </button>
                            </td>
                        </tr>
                        <!-- Spirit -->
                        <tr class="stat-row" data-attribute="spirit" data-value="<?php echo (int)$character['spirit']; ?>">
                            <td class="stat-name">Spirit</td>
                            <td class="stat-value"><?php echo (int)$character['spirit']; ?></td>
                            <td class="stat-roll-cell">
                                <button class="stat-roll-btn" title="Roll Spirit check">
                                    <i class="fas fa-dice-d20"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Additional character information sections -->
            <div class="character-details">
                <!-- Include Inventory Section -->
                <?php 
                // Pass the sheet_id to the inventory display component
                $sheet_id = $character['id'] ?? null; 
                if ($sheet_id) {
                    require_once dirname(__DIR__) . '/inventory_system/inventory_display.php'; 
                } else {
                    echo '<div class="alert alert-warning">Cannot display inventory: Character ID missing.</div>';
                }
                ?>
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

<!-- Dice Roll Modal -->
<div id="dice-roll-modal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3 id="roll-title">Attribute Check</h3>
        <div id="roll-result-container">
            <div id="roll-details">
                <div class="roll-row">
                    <span class="roll-label">Dice Roll (d20):</span>
                    <span id="dice-value" class="roll-value">-</span>
                </div>
                <div class="roll-row">
                    <span class="roll-label">Attribute Bonus:</span>
                    <span id="attribute-value" class="roll-value">-</span>
                </div>
                <div class="roll-row total-row">
                    <span class="roll-label">Total:</span>
                    <span id="total-value" class="roll-value">-</span>
                </div>
            </div>
        </div>
        <div class="roll-actions">
            <button id="copy-roll-btn" class="btn btn-secondary">
                <i class="fas fa-copy"></i> Copy Result
            </button>
            <?php if ($discord_authenticated): ?>
                <button id="send-roll-discord-btn" class="btn btn-discord">
                    <i class="fab fa-discord"></i> Send to Discord
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Character sheet styles are loaded from css/character_sheet.css -->

<!-- Include Discord webhook modal component -->
<?php if ($discord_authenticated): ?>
<?php
// Load the Discord webhook modal component for attribute rolls
require_once dirname(__FILE__) . '/discord_webhook_modal.php';

// Render the hidden container for attribute roll content
echo '<div id="attribute-roll-discord-container" style="display:none">';
echo '<div id="attribute-roll-content"></div>';
echo '</div>';

// Render Discord webhook modal with unique ID
render_discord_webhook_modal(
    '#attribute-roll-content',
    'attribute_roll',
    false,
    '',
    [
        'button_text' => 'Send to Discord',
        'button_icon' => 'fa-discord',
        'button_class' => 'btn-discord',
        'modal_title' => 'Send Attribute Roll to Discord',
        'button_id' => 'open-discord-modal',
        'modal_id' => 'discord-webhook-modal-character',
        'show_character_image' => true
    ]
);
?>
<?php endif; ?>

<!-- Pass authentication status to JS -->
<script>
// Character sheet data is already set in the main page
// This is just for component-specific JS initialization
console.log("Character sheet view component loaded");
</script>