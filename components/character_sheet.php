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
                            // Get character image
                            $charImage = !empty($char['image_path']) ? htmlspecialchars($char['image_path']) : 'assets/TSP_default_character.jpg';
                            ?>
                            <div class="character-list-avatar">
                                <img src="<?php echo $charImage; ?>" alt="Character Portrait" onerror="this.src='assets/TSP_default_character.jpg'">
                            </div>
                            <div class="character-list-details">
                                <span class="character-name"><?php echo htmlspecialchars($char['name']); ?></span>
                                <span class="character-user-id">(User ID: <?php echo isset($char['user_id']) ? $char['user_id'] : 'unknown'; ?>)</span>
                                <?php if ($character && $character['id'] == $char['id']): ?>
                                    <span class="current-badge">Current</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="character-list-actions">
                            <a href="character_sheet.php?id=<?php echo $char['id']; ?>" class="btn btn-primary btn-sm">
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
</div><?php
// components/character_sheet.php

// Check if a character ID is provided in the URL
$character_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$character = null;
$error_message = null;

// If user is logged in, load their character
// Get user ID from Discord session if authenticated
$user_id = 1; // Default fallback

// Set a safe default
$user_id = 1;

// First, try to fetch Discord info if authenticated
if ($discord_authenticated && isset($_SESSION['discord_user'])) {
    try {
        require_once 'config/db_connect.php';
        $discord_id = $_SESSION['discord_user']['id']; 
        
        // Get user ID from discord_users table
        $stmt = $conn->prepare("SELECT id FROM discord_users WHERE discord_id = ?");
        $stmt->execute([$discord_id]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userData) {
            $user_id = $userData['id'];
        }
    } catch (Exception $e) {
        // Fail silently
    }
}

// Fetch all characters from database to see what's available
$user_characters = [];
try {
    require_once 'config/db_connect.php';
    
    // TEMPORARY: Show all characters in database for debugging
    $stmt = $conn->prepare("SELECT id, name, image_path, user_id FROM characters");
    $stmt->execute();
    $user_characters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    // Fail silently
}

// If a character ID is provided, load the character from the database
if ($character_id) {
    try {
        require_once 'config/db_connect.php';
        
        $stmt = $conn->prepare("SELECT * FROM characters WHERE id = :id AND user_id = :user_id");
        $stmt->bindParam(':id', $character_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $character = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$character) {
            $error_message = "Character not found or you don't have permission to view it.";
        }
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
} else {
    // No character ID provided, load the most recent character or create a default one
    try {
        require_once 'config/db_connect.php';
        
        $stmt = $conn->prepare("SELECT * FROM characters WHERE user_id = :user_id ORDER BY updated_at DESC LIMIT 1");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $character = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Set default image path
        $default_image_path = 'assets/TSP_default_character.jpg';
        
        // Create parent directory if it doesn't exist
        if (!file_exists('assets')) {
            mkdir('assets', 0755, true);
        }
        
        if (!$character) {
            // No characters found, create a default template
            $character = [
                'id' => null,
                'name' => 'New Pirate',
                'image_path' => $default_image_path,
                'strength' => 0,
                'agility' => 0,
                'presence' => 0,
                'toughness' => 0,
                'spirit' => 0
            ];
        }
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
}

// Process form submission for editing character
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_character') {
    try {
        require_once 'config/db_connect.php';
        
        $char_id = $_POST['character_id'];
        $name = htmlspecialchars($_POST['name']);
        $strength = (int)$_POST['strength'];
        $agility = (int)$_POST['agility'];
        $presence = (int)$_POST['presence'];
        $toughness = (int)$_POST['toughness'];
        $spirit = (int)$_POST['spirit'];
        
        // Handle image upload
        $image_path = isset($character['image_path']) ? $character['image_path'] : 'assets/images/default_character.png';
        
        if (isset($_FILES['character_image']) && $_FILES['character_image']['error'] === UPLOAD_ERR_OK) {
            // Create uploads directory if it doesn't exist
            $upload_dir = 'uploads/characters/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Sanitize filename and generate a unique name
            $file_extension = pathinfo($_FILES['character_image']['name'], PATHINFO_EXTENSION);
            $new_filename = 'character_' . time() . '_' . uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $new_filename;
            
            // Validate file type
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array(strtolower($file_extension), $allowed_types)) {
                // Move uploaded file
                if (move_uploaded_file($_FILES['character_image']['tmp_name'], $target_path)) {
                    $image_path = $target_path;
                } else {
                    $error_message = "Failed to upload image.";
                }
            } else {
                $error_message = "Invalid file type. Allowed types: jpg, jpeg, png, gif.";
            }
        }
        
        // If character_id is empty, this is a new character
        if (empty($char_id)) {
            $stmt = $conn->prepare("INSERT INTO characters 
                (user_id, name, image_path, strength, agility, presence, toughness, spirit) 
                VALUES 
                (:user_id, :name, :image_path, :strength, :agility, :presence, :toughness, :spirit)");
        } else {
            // Update existing character
            $stmt = $conn->prepare("UPDATE characters 
                SET name = :name, image_path = :image_path, strength = :strength, agility = :agility, 
                presence = :presence, toughness = :toughness, spirit = :spirit 
                WHERE id = :id AND user_id = :user_id");
            $stmt->bindParam(':id', $char_id);
        }
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':image_path', $image_path);
        $stmt->bindParam(':strength', $strength);
        $stmt->bindParam(':agility', $agility);
        $stmt->bindParam(':presence', $presence);
        $stmt->bindParam(':toughness', $toughness);
        $stmt->bindParam(':spirit', $spirit);
        
        $stmt->execute();
        
        // If this was a new character, get the new ID
        if (empty($char_id)) {
            $char_id = $conn->lastInsertId();
        }
        
        // Redirect to prevent form resubmission
        header("Location: character_sheet.php?id=" . $char_id . "&success=1");
        exit;
        
    } catch (PDOException $e) {
        $error_message = "Error saving character: " . $e->getMessage();
    }
}
?>

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

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success">
    Character saved successfully!
</div>
<?php endif; ?>

<div class="content-container">
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
                            <img src="<?php echo htmlspecialchars($character['image_path']); ?>" alt="Current Image" id="image-preview" style="max-width: 150px; max-height: 150px; width: auto; height: auto; object-fit: contain;">
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

<style>
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

/* Character Switcher Modal Styles */
.character-list {
    margin: 20px 0;
    max-height: 400px;
    overflow-y: auto;
}

.character-list-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    border: 1px solid #e0e0e0;
    margin-bottom: 10px;
    border-radius: 8px;
    transition: all 0.2s ease;
    background-color: #fff;
}

.character-list-item:hover {
    border-color: #bf9d61;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.character-list-item.active {
    border-color: #bf9d61;
    background-color: #fcf7ee;
}

.character-list-info {
    display: flex;
    align-items: center;
    flex: 1;
}

.character-list-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid #bf9d61;
    margin-right: 15px;
    flex-shrink: 0;
}

.character-list-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.character-list-details {
    flex: 1;
}

.character-name {
    font-weight: bold;
    font-size: 1.1rem;
    color: #1a2639;
    display: block;
}

.character-user-id {
    font-size: 0.8rem;
    color: #666;
    display: block;
    margin-top: 2px;
}

.character-list-actions {
    margin-left: 10px;
}

.current-badge {
    display: inline-block;
    background-color: #e0f0e0;
    color: #2c8527;
    font-size: 0.7rem;
    padding: 2px 8px;
    border-radius: 20px;
    margin-top: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: 1px solid #c0e0c0;
}

.empty-characters {
    text-align: center;
    padding: 30px 0;
    color: #666;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 0.85rem;
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

.character-details {
    margin-top: 30px;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.7);
}

.modal-content {
    background-color: #f9f5eb;
    margin: 10% auto;
    padding: 25px;
    border: 1px solid #bf9d61;
    border-radius: 8px;
    width: 80%;
    max-width: 550px;
    position: relative;
}

.close-modal, .close-modal-btn {
    cursor: pointer;
    color: #aaa;
}

.close-modal {
    position: absolute;
    right: 15px;
    top: 10px;
    font-size: 28px;
}

.close-modal:hover {
    color: #bf9d61;
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

/* Responsive Adjustments */
@media (max-width: 768px) {
    .character-header {
        flex-direction: column;
        text-align: center;
    }
    
    .character-image {
        margin-right: 0;
        margin-bottom: 15px;
    }
    
    .edit-button {
        position: static;
        margin-top: 15px;
    }
    
    .stat-group {
        flex-direction: column;
    }
    
    .form-row {
        flex-direction: column;
        gap: 15px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get modal elements
    const editModal = document.getElementById('edit-character-modal');
    const switcherModal = document.getElementById('character-switcher-modal');
    const editBtn = document.getElementById('edit-character-btn');
    const switchBtn = document.getElementById('switch-character-btn');
    const closeBtns = document.querySelectorAll('.close-modal');
    const closeFormBtns = document.querySelectorAll('.close-modal-btn');
    const newCharacterBtn = document.getElementById('new-character-btn');
    const createNewFromSwitcherBtn = document.getElementById('create-new-from-switcher');
    const printBtn = document.getElementById('print-character-btn');
    const imageInput = document.getElementById('character_image');
    const imagePreview = document.getElementById('image-preview');
    
    // Discord authentication status
    const isAuthenticated = <?php echo $discord_authenticated ? 'true' : 'false'; ?>;
    
    // Open edit modal when edit button is clicked
    if (editBtn) {
        editBtn.addEventListener('click', function() {
            if (isAuthenticated) {
                editModal.style.display = 'block';
            } else {
                alert('You must connect with Discord to edit characters.');
            }
        });
    }
    
    // Open switcher modal when switch button is clicked
    if (switchBtn) {
        switchBtn.addEventListener('click', function() {
            switcherModal.style.display = 'block';
        });
    }
    
    // Close modals when X is clicked
    if (closeBtns) {
        closeBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                editModal.style.display = 'none';
                switcherModal.style.display = 'none';
            });
        });
    }
    
    // Close modals when Cancel button is clicked
    if (closeFormBtns) {
        closeFormBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                editModal.style.display = 'none';
                switcherModal.style.display = 'none';
            });
        });
    }
    
    // Close modals when clicking outside of them
    window.addEventListener('click', function(event) {
        if (event.target == editModal) {
            editModal.style.display = 'none';
        }
        if (event.target == switcherModal) {
            switcherModal.style.display = 'none';
        }
    });
    
    // New Character button functionality
    if (newCharacterBtn) {
        newCharacterBtn.addEventListener('click', function() {
            if (!isAuthenticated) {
                alert('You must connect with Discord to create characters.');
                return;
            }
            
            // Reset the form for a new character
            document.getElementById('edit-character-form').reset();
            document.querySelector('input[name="character_id"]').value = '';
            document.getElementById('name').value = 'New Pirate';
            document.getElementById('strength').value = '0';
            document.getElementById('agility').value = '0';
            document.getElementById('presence').value = '0';
            document.getElementById('toughness').value = '0';
            document.getElementById('spirit').value = '0';
            
            // Reset image preview to default
            imagePreview.src = 'assets/TSP_default_character.jpg';
            
            // Show the modal
            editModal.style.display = 'block';
        });
    }
    
    // Create New from switcher button
    if (createNewFromSwitcherBtn) {
        createNewFromSwitcherBtn.addEventListener('click', function() {
            switcherModal.style.display = 'none';
            if (newCharacterBtn) {
                newCharacterBtn.click();
            }
        });
    }
    
    // Print button functionality
    if (printBtn) {
        printBtn.addEventListener('click', function() {
            window.print();
        });
    }
    
    // Image preview functionality
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    // Create a temporary image to check dimensions
                    const tempImg = new Image();
                    tempImg.src = e.target.result;
                    
    tempImg.onload = function() {
                        // Apply strict size constraints to prevent oversized images
                        const img = new Image();
                        img.src = e.target.result;
                        
                        // Set max dimensions for display
                        img.style.maxWidth = '150px';
                        img.style.maxHeight = '150px';
                        img.style.width = 'auto';
                        img.style.height = 'auto';
                        
                        // Update the image preview
                        imagePreview.src = e.target.result;
                        
                        // Force size constraints on the preview element
                        imagePreview.style.maxWidth = '150px';
                        imagePreview.style.maxHeight = '150px';
                        imagePreview.style.width = 'auto';
                        imagePreview.style.height = 'auto';
                    };
                };
                
                reader.readAsDataURL(this.files[0]);
                
                // Check file size
                const fileSize = this.files[0].size / 1024 / 1024; // in MB
                if (fileSize > 2) {
                    alert('File size exceeds 2MB. Please choose a smaller image.');
                    this.value = ''; // Clear the input
                    imagePreview.src = imagePreview.getAttribute('data-original') || 'assets/TSP_default_character.jpg';
                }
            }
        });
        
        // Store original image path for reset
        if (imagePreview) {
            imagePreview.setAttribute('data-original', imagePreview.src);
        }
    }
    
    // Hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    if (alerts.length > 0) {
        setTimeout(function() {
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 5000);
    }
});
</script>
