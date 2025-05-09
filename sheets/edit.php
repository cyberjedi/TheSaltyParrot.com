<?php
/**
 * Character Sheet Edit Page
 * 
 * Allows users to create or edit character sheets for various game systems
 */

// Start the session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['uid'])) {
    header('Location: /index.php');
    exit;
}

// Initialize variables
$sheet_id = isset($_GET['id']) && !empty($_GET['id']) ? (int)$_GET['id'] : null;
$sheet = null;
$system_data = null;
$error_message = null;
$success_message = null;
$user_id = $_SESSION['uid'];

// Include database connection
require_once '../config/db_connect.php';

// If a sheet ID is provided, load it from the database
if (!empty($sheet_id)) {
    try {
        if (isset($conn) && $conn !== null) {
            // First load the main sheet data
            $stmt = $conn->prepare("SELECT * FROM character_sheets WHERE id = ? AND user_id = ?");
            $stmt->execute([$sheet_id, $user_id]);
            $sheet = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($sheet) {
                // Based on the system, load the appropriate system-specific data
                if ($sheet['system'] === 'pirate_borg') {
                    $stmt = $conn->prepare("SELECT * FROM pirate_borg_sheets WHERE sheet_id = ?");
                    $stmt->execute([$sheet_id]);
                    $system_data = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Merge system data into the sheet array
                    if ($system_data) {
                        $sheet = array_merge($sheet, $system_data);
                    }
                }
                // Add elseif blocks for other systems here in the future
            }
        }
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
}

// If no sheet was loaded, create a default template
if (!$sheet) {
    $default_image_path = '../assets/TSP_default_character.jpg';
    
    $sheet = [
        'id' => null,
        'user_id' => $user_id,
        'system' => 'pirate_borg', // Default system
        'name' => 'New Character',
        'image_path' => $default_image_path,
        'strength' => 0,
        'agility' => 0,
        'presence' => 0,
        'toughness' => 0,
        'spirit' => 0,
        'hp_max' => 10, // Default Max HP
        'hp_current' => 10, // Default Current HP
        'notes' => '',
        'character_type' => '',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_sheet') {
    // Get the posted sheet data
    $sheet_id = isset($_POST['sheet_id']) && !empty($_POST['sheet_id']) ? (int)$_POST['sheet_id'] : null;
    $system = htmlspecialchars($_POST['system']); // Get the selected game system
    $name = htmlspecialchars($_POST['name']);
    $character_type = htmlspecialchars($_POST['character_type'] ?? '');
    
    // Get system-specific attributes
    if ($system === 'pirate_borg') {
        $strength = isset($_POST['strength']) ? (int)$_POST['strength'] : 0;
        $agility = isset($_POST['agility']) ? (int)$_POST['agility'] : 0;
        $presence = isset($_POST['presence']) ? (int)$_POST['presence'] : 0;
        $toughness = isset($_POST['toughness']) ? (int)$_POST['toughness'] : 0;
        $spirit = isset($_POST['spirit']) ? (int)$_POST['spirit'] : 0;
        $hp_max = isset($_POST['hp_max']) ? (int)$_POST['hp_max'] : 1; // Ensure hp_max is at least 1
        $hp_max = max(1, $hp_max); // Explicitly ensure it's >= 1
        $notes = htmlspecialchars($_POST['notes']);
    }
    
    // Handle image upload
    if (!empty($sheet_id)) {
        // For existing sheets, get the current image path from the database
        $stmt = $conn->prepare("SELECT image_path FROM character_sheets WHERE id = ? AND user_id = ?");
        $stmt->execute([$sheet_id, $user_id]);
        $current_image_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $image_path = $current_image_data ? $current_image_data['image_path'] : '../assets/TSP_default_character.jpg';
    } else {
        // For new sheets, use the default image
        $image_path = '../assets/TSP_default_character.jpg';
    }
    
    // Check if a selected image path was provided (from the photo management modal)
    if (isset($_POST['selected_image_path']) && !empty($_POST['selected_image_path'])) {
        $image_path = $_POST['selected_image_path'];
    }
    // Otherwise, process file upload if one was provided
    else if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // Check file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $file_type = finfo_file($file_info, $_FILES['image']['tmp_name']);
        finfo_close($file_info);
        
        if (!in_array($file_type, $allowed_types)) {
            $error_message = "Invalid file type. Please upload a JPEG, PNG, or GIF image.";
        } else {
            // Generate unique filename
            $upload_dir = '../uploads/character_sheets/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $new_filename = $user_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            // Move the uploaded file
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Delete old image if it exists and is not the default
                if ($image_path !== '../assets/TSP_default_character.jpg' && file_exists($image_path)) {
                    @unlink($image_path);
                }
                
                $image_path = '../uploads/character_sheets/' . $new_filename;
            } else {
                $error_message = "Failed to upload image. Please try again.";
            }
        }
    }
    
    // Save to database
    if (!$error_message) {
        try {
            // Create date fields
            $now = date('Y-m-d H:i:s');
            
            // Start a transaction for saving to multiple tables
            $conn->beginTransaction();
            
            // If sheet_id is empty, this is a new sheet
            if (empty($sheet_id)) {
                // First insert the main sheet data
                $query = "INSERT INTO character_sheets (user_id, `system`, name, image_path, created_at, updated_at) 
                         VALUES (?, ?, ?, ?, ?, ?)";
                
                $stmt = $conn->prepare($query);
                $stmt->execute([$user_id, $system, $name, $image_path, $now, $now]);
                
                // Get the new sheet ID
                $sheet_id = $conn->lastInsertId();
                
                // Insert system-specific data
                if ($system === 'pirate_borg') {
                    $query = "INSERT INTO pirate_borg_sheets (sheet_id, character_type, strength, agility, presence, toughness, spirit, hp_max, hp_current, notes)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $stmt = $conn->prepare($query);
                    // Set current HP to max HP for a new character
                    $stmt->execute([$sheet_id, $character_type, $strength, $agility, $presence, $toughness, $spirit, $hp_max, $hp_max, $notes]);
                }
                
                // Commit transaction for new sheet
                $conn->commit();
                $success_message = "Character sheet created successfully!";
                header("Location: ../sheets.php?sheet=" . $sheet_id); // Redirect to the main sheets page
                exit; // Stop script execution after redirect

            } else {
                // First update the main sheet data
                $query = "UPDATE character_sheets SET `system` = ?, name = ?, image_path = ?, updated_at = ? 
                         WHERE id = ? AND user_id = ?";
                
                $stmt = $conn->prepare($query);
                $stmt->execute([$system, $name, $image_path, $now, $sheet_id, $user_id]);
                
                // Update system-specific data based on selected system
                if ($system === 'pirate_borg') {
                    // Check if system data exists
                    $check = $conn->prepare("SELECT 1 FROM pirate_borg_sheets WHERE sheet_id = ?");
                    $check->execute([$sheet_id]);
                    
                    if ($check->fetchColumn()) {
                        // Get current HP values before update
                        $hpCheckStmt = $conn->prepare("SELECT hp_max, hp_current FROM pirate_borg_sheets WHERE sheet_id = ?");
                        $hpCheckStmt->execute([$sheet_id]);
                        $currentHpData = $hpCheckStmt->fetch(PDO::FETCH_ASSOC);
                        $old_hp_max = $currentHpData ? (int)$currentHpData['hp_max'] : $hp_max;
                        $old_hp_current = $currentHpData ? (int)$currentHpData['hp_current'] : $hp_max;

                        // Determine the new current HP
                        // Keep the existing current HP, but ensure it doesn't exceed the new max HP.
                        $new_hp_current = $old_hp_current; 
                        // Ensure current HP doesn't exceed the new max HP, and is not less than 0
                        $new_hp_current = min($hp_max, $new_hp_current);
                        $new_hp_current = max(0, $new_hp_current);

                        // Update
                        $query = "UPDATE pirate_borg_sheets SET character_type = ?, strength = ?, agility = ?, presence = ?, 
                                toughness = ?, spirit = ?, hp_max = ?, hp_current = ?, notes = ? WHERE sheet_id = ?";
                        
                        $stmt = $conn->prepare($query);
                        $stmt->execute([$character_type, $strength, $agility, $presence, $toughness, $spirit, $hp_max, $new_hp_current, $notes, $sheet_id]);
                    } else {
                         // Insert if it somehow didn't exist (data inconsistency?)
                         $query = "INSERT INTO pirate_borg_sheets (sheet_id, character_type, strength, agility, presence, toughness, spirit, hp_max, hp_current, notes)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                         $stmt = $conn->prepare($query);
                         // Set current HP to max HP when inserting missing record
                         $stmt->execute([$sheet_id, $character_type, $strength, $agility, $presence, $toughness, $spirit, $hp_max, $hp_max, $notes]);
                    }
                }
                
                // Commit transaction for update
                $conn->commit();
                $success_message = "Character sheet updated successfully!";
                header("Location: ../sheets.php?sheet=" . $sheet_id); // Redirect to the main sheets page
                exit; // Stop script execution after redirect
            }
            
        } catch (PDOException $e) {
            // Roll back the transaction if something failed
            if ($conn->inTransaction()) {
                 $conn->rollBack();
            }
            // Log the detailed error
            error_log("Database error saving character sheet (User: $user_id, Sheet: $sheet_id): " . $e->getMessage());
            // Set generic error message for the user
            $error_message = "An error occurred while saving the character sheet. Please try again or contact support if the problem persists.";
        }
    }
}

// Prepare display data (ensure $sheet exists even after failed save attempt)
// Re-fetch or use existing $sheet data before displaying the form
// This part might need adjustment depending on how you want to handle partial data after save failure
if (empty($sheet) && !empty($sheet_id)) {
     // If save failed and $sheet got wiped, try reloading
      try {
         if (isset($conn) && $conn !== null) {
             // Load main sheet data
             $stmt = $conn->prepare("SELECT * FROM character_sheets WHERE id = ? AND user_id = ?");
             $stmt->execute([$sheet_id, $user_id]);
             $sheet = $stmt->fetch(PDO::FETCH_ASSOC);
            
             if ($sheet) {
                 // Load system-specific data
                 if ($sheet['system'] === 'pirate_borg') {
                     $stmt = $conn->prepare("SELECT * FROM pirate_borg_sheets WHERE sheet_id = ?");
                     $stmt->execute([$sheet_id]);
                     $system_data = $stmt->fetch(PDO::FETCH_ASSOC);
                     if ($system_data) {
                         $sheet = array_merge($sheet, $system_data);
                     }
                 }
                 // Add other systems
             }
         }
     } catch (PDOException $e) {
         // Log error if reload fails too
         error_log("Database error reloading sheet after save failure (User: $user_id, Sheet: $sheet_id): " . $e->getMessage());
         if (empty($error_message)) { // Don't overwrite the save error
            $error_message = "An error occurred while loading character sheet data.";
         }
         // Potentially clear $sheet or redirect? For now, let the form potentially show defaults/old data.
          $sheet = null; // Prevent potential errors trying to access properties of null
     }
}

// If still no sheet (e.g., new sheet creation failed), use default template
if (!$sheet) {
    $default_image_path = '/assets/TSP_default_character.jpg'; // Use root-relative path
    $sheet = [
        'id' => null,
        'user_id' => $user_id,
        'system' => 'pirate_borg',
        'name' => isset($_POST['name']) ? htmlspecialchars($_POST['name']) : 'New Character', // Preserve submitted name on failure
        'image_path' => isset($_POST['selected_image_path']) && !empty($_POST['selected_image_path']) ? htmlspecialchars($_POST['selected_image_path']) : $default_image_path, // Preserve selected image
        'strength' => isset($_POST['strength']) ? (int)$_POST['strength'] : 0,
        'agility' => isset($_POST['agility']) ? (int)$_POST['agility'] : 0,
        'presence' => isset($_POST['presence']) ? (int)$_POST['presence'] : 0,
        'toughness' => isset($_POST['toughness']) ? (int)$_POST['toughness'] : 0,
        'spirit' => isset($_POST['spirit']) ? (int)$_POST['spirit'] : 0,
        'hp_max' => isset($_POST['hp_max']) ? (int)$_POST['hp_max'] : 10,
        'hp_current' => isset($_POST['hp_max']) ? (int)$_POST['hp_max'] : 10, // Set current to max on failure recovery
        'notes' => isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : '',
        'character_type' => isset($_POST['character_type']) ? htmlspecialchars($_POST['character_type']) : '',
        'created_at' => null,
        'updated_at' => null
    ];
    $sheet['hp_current'] = $sheet['hp_max']; // Ensure consistency
} else {
     // Ensure image path is root-relative for display
     $currentPath = $sheet['image_path'] ?? null; // Get current path, could be null

     if ($currentPath === null || $currentPath === '') {
         // If path is null or empty, use default
         $sheet['image_path'] = '/assets/TSP_default_character.jpg';
     } elseif (strpos($currentPath, '../') === 0) {
         // If path starts with ../, remove it and ensure leading slash
         $relativePath = substr($currentPath, 3);
         $sheet['image_path'] = '/' . ltrim($relativePath, '/');
     } elseif ($currentPath === 'assets/TSP_default_character.jpg') {
         // If path is the non-relative default, make it root-relative
         $sheet['image_path'] = '/assets/TSP_default_character.jpg';
     } elseif (strpos($currentPath, '/') !== 0) {
         // If path doesn't start with /, assume it's a filename in uploads
         // Ensure it doesn't already contain the full path segment
         if (strpos($currentPath, 'uploads/character_sheets/') === false) {
             $sheet['image_path'] = '/uploads/character_sheets/' . basename($currentPath);
         } else {
             // Path likely contains 'uploads/...' but missing leading '/'
             $sheet['image_path'] = '/' . ltrim($currentPath, '/');
         }
     }
     // If path already starts with /, it's assumed to be correct root-relative path, do nothing
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo empty($sheet_id) ? 'Create Character Sheet' : 'Edit ' . htmlspecialchars($sheet['name']); ?></title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/topbar.css">
    <link rel="stylesheet" href="../css/sheets.css">
    <link rel="stylesheet" href="../css/character-sheet.css">
    <link rel="stylesheet" href="../css/inventory.css">
    <link rel="stylesheet" href="../image_management/photo_manager.css">
    <link rel="icon" href="../favicon.ico" type="image/x-icon">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" integrity="sha512-Fo3rlrZj/k7ujTnHg4CGR2D7kSs0v4LLanw2qksYuRlEzO+tcaEPQogQ0KaoGN26/zrn20ImR1DfuLWnOo7aBA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Pirata+One&family=IM+Fell+English&family=Roboto:wght@400;700&display=swap" rel="stylesheet">

    <!-- Site Styles -->
</head>
<body>
    <?php include '../components/topbar.php'; // Adjusted path ?>

    <div class="main-content-new">
        <div class="page-container">
            <div class="sheet-container">
                <div class="sheet-header">
                    <h2><?php echo empty($sheet_id) ? 'Create New Character Sheet' : 'Edit Character Sheet'; ?></h2>
                </div>
                
                <div class="sheet-body">
                    <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success_message): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    
                    <form id="sheet-form" method="POST" action="/sheets/edit.php" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="save_sheet">
                        <input type="hidden" name="sheet_id" value="<?php echo $sheet_id; ?>">
                        
                        <div class="edit-section">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="system">Game System</label>
                                    <select id="system" name="system" class="form-control" <?php echo !empty($sheet_id) ? 'disabled' : ''; ?>>
                                        <option value="pirate_borg" <?php echo (isset($sheet['system']) && $sheet['system'] === 'pirate_borg') ? 'selected' : ''; ?>>Pirate Borg</option>
                                        <!-- More game systems can be added here in the future -->
                                    </select>
                                    <?php if (!empty($sheet_id)): ?>
                                        <!-- Add a hidden input to submit the system value when disabled -->
                                        <input type="hidden" name="system" value="<?php echo htmlspecialchars($sheet['system']); ?>">
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name">Character Name</label>
                                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($sheet['name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="character_type">Character Type</label>
                                    <input type="text" id="character_type" name="character_type" value="<?php echo htmlspecialchars($sheet['character_type'] ?? ''); ?>" placeholder="Pirate, Navigator, Sea Witch, etc.">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="image">Character Image</label>
                                    <div class="profile-image-preview">
                                        <img id="image-preview" src="<?php echo htmlspecialchars($sheet['image_path'] ?? '/assets/TSP_default_character.jpg'); ?>" 
                                             alt="Character Portrait" 
                                             onerror="this.src='/assets/TSP_default_character.jpg'">
                                        <div class="edit-icon" onclick="openSharedPhotoManager(<?php echo $sheet_id ? $sheet_id : 'null'; ?>)">
                                            <i class="fas fa-pencil-alt"></i>
                                        </div>
                                    </div>
                                    <input type="file" id="image" name="image" accept="image/*" style="display: none;">
                                    <input type="hidden" id="selected_image_path" name="selected_image_path" value="">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pirate Borg specific fields -->
                        <div id="pirate-borg-fields" class="system-specific-fields active">
                            <div class="edit-section">
                                <h3>Attributes</h3>
                                <div class="attributes-grid edit-attributes-grid">
                                    <div class="attribute-item">
                                        <label for="strength">Strength</label>
                                        <div class="input-with-controls">
                                            <input type="number" id="strength" name="strength" value="<?php echo (int)($sheet['strength'] ?? 0); ?>" class="attribute-field">
                                        </div>
                                    </div>
                                    <div class="attribute-item">
                                        <label for="agility">Agility</label>
                                        <div class="input-with-controls">
                                            <input type="number" id="agility" name="agility" value="<?php echo (int)($sheet['agility'] ?? 0); ?>" class="attribute-field">
                                        </div>
                                    </div>
                                    <div class="attribute-item">
                                        <label for="presence">Presence</label>
                                        <div class="input-with-controls">
                                            <input type="number" id="presence" name="presence" value="<?php echo (int)($sheet['presence'] ?? 0); ?>" class="attribute-field">
                                        </div>
                                    </div>
                                    <div class="attribute-item">
                                        <label for="toughness">Toughness</label>
                                        <div class="input-with-controls">
                                            <input type="number" id="toughness" name="toughness" value="<?php echo (int)($sheet['toughness'] ?? 0); ?>" class="attribute-field">
                                        </div>
                                    </div>
                                    <div class="attribute-item">
                                        <label for="spirit">Spirit</label>
                                        <div class="input-with-controls">
                                            <input type="number" id="spirit" name="spirit" value="<?php echo (int)($sheet['spirit'] ?? 0); ?>" class="attribute-field">
                                        </div>
                                    </div>
                                    <div class="attribute-item">
                                        <label for="hp_max">Max HP</label>
                                        <div class="input-with-controls">
                                            <input type="number" id="hp_max" name="hp_max" value="<?php echo (int)($sheet['hp_max'] ?? 1); ?>" class="attribute-field" min="1">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="edit-section">
                                <div class="form-group">
                                    <label for="notes">Notes</label>
                                    <textarea id="notes" name="notes"><?php echo htmlspecialchars($sheet['notes'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Other system-specific fields can be added here in the future -->
                        
                        <div class="action-buttons">
                            <button type="submit" class="btn btn-primary">Save Character</button>
                            <a href="/sheets.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../image_management/photo_manager_modal.php'; // UPDATED path ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Image preview functionality
            const imageInput = document.getElementById('image');
            if (imageInput) {
                imageInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            const preview = document.getElementById('image-preview');
                            if (preview) {
                                preview.src = event.target.result;
                            }
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
            
            // Show/hide system-specific fields based on system selection
            const systemSelect = document.getElementById('system');
            if (systemSelect) {
                systemSelect.addEventListener('change', function() {
                    const system = this.value;
                    document.querySelectorAll('.system-specific-fields').forEach(el => {
                        el.classList.remove('active');
                    });
                    // Construct the ID correctly (replace underscore with hyphen if needed)
                    const systemId = system.replace('_', '-') + '-fields'; 
                    const fieldsToShow = document.getElementById(systemId);
                    if (fieldsToShow) {
                        fieldsToShow.classList.add('active');
                    } else {
                        console.warn('Could not find system fields div with ID:', systemId);
                    }
                });
                // Trigger change once on load to set initial state
                systemSelect.dispatchEvent(new Event('change')); 
            }

            // Input validation for attribute fields
            document.querySelectorAll('.attribute-field').forEach(input => {
                // Allow only numbers (including negative)
                input.addEventListener('input', function(e) {
                    let value = this.value;
                    if (value.startsWith('-')) {
                        value = '-' + value.substring(1).replace(/[^\d]/g, '');
                    } else {
                        value = value.replace(/[^\d]/g, '');
                    }
                    // Prevent multiple leading zeros unless it's just '0'
                    if (value.length > 1 && value.startsWith('0')) {
                        value = value.substring(1);
                    }
                    if (value.length > 2 && value.startsWith('-0')) {
                         value = '-' + value.substring(2);
                    }
                    this.value = value;
                });

                // Ensure proper number formatting when focus is lost
                input.addEventListener('blur', function() {
                    if (this.value === '' || this.value === '-') {
                        this.value = '0';
                    } else {
                         // Convert to number and back to string to remove leading zeros like 05 -> 5
                         this.value = parseInt(this.value, 10).toString(); 
                    }
                });
            });

            // Add event listeners for attribute adjustment buttons
            document.querySelectorAll('.attr-adjust-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const targetId = this.dataset.target;
                    const change = parseInt(this.dataset.change);
                    const targetInput = document.getElementById(targetId);
                    const minVal = this.dataset.min !== undefined ? parseInt(this.dataset.min) : null;

                    if (targetInput) {
                        let currentValue = parseInt(targetInput.value) || 0;
                        let newValue = currentValue + change;
                        
                        // Apply minimum value constraint if present
                        if (minVal !== null && newValue < minVal) {
                            newValue = minVal;
                        }

                        targetInput.value = newValue;
                        // Optionally trigger input or change event if needed by other scripts
                        targetInput.dispatchEvent(new Event('input')); 
                        targetInput.dispatchEvent(new Event('change'));
                    }
                });
            });

            // Form validation
            const sheetForm = document.getElementById('sheet-form');
            if (sheetForm) {
                sheetForm.addEventListener('submit', function(e) {
                    const attributeFields = document.querySelectorAll('.attribute-field');
                    let isValid = true;
                    attributeFields.forEach(field => {
                        field.style.borderColor = ''; // Reset border color
                        if (field.value === '' || isNaN(parseInt(field.value))) {
                            isValid = false;
                            field.style.borderColor = 'red';
                        }
                    });
                    
                    if (!isValid) {
                        e.preventDefault();
                        alert('Please enter valid numbers for all attributes');
                    }
                });
            }

            // Initialize the shared photo manager
            if (window.photoManager) {
                function handleSheetPhotoUpdate(photoUrl) {
                    const previewImg = document.getElementById('image-preview');
                    if (previewImg) {
                        previewImg.src = '/' + photoUrl; // Use root-relative path
                    }
                    const hiddenInput = document.getElementById('selected_image_path');
                    if (hiddenInput) {
                        hiddenInput.value = photoUrl; // Store root-relative path
                    }
                }
                window.photoManager.init(handleSheetPhotoUpdate);
            } else {
                console.error("Photo Manager script not loaded or failed to initialize.");
            }
            
        }); // End of DOMContentLoaded listener

        // Function to open the shared photo manager (can be defined outside DOMContentLoaded)
        function openSharedPhotoManager(sheetId) {
             if (window.photoManager && typeof window.photoManager.show === 'function') {
                 window.photoManager.show('sheet', sheetId); // Pass context and sheet ID
             } else {
                  console.error("Cannot open Photo Manager: Not initialized or show function missing.");
                  alert("Error: Could not open the photo manager. Please refresh the page.");
             }
        }
        
    </script>
    <script src="../image_management/photo_manager.js" defer></script> <!-- UPDATED path -->
</body>
</html> 