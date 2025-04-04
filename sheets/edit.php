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
        'notes' => '',
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
    
    // Get system-specific attributes
    if ($system === 'pirate_borg') {
        $strength = isset($_POST['strength']) ? (int)$_POST['strength'] : 0;
        $agility = isset($_POST['agility']) ? (int)$_POST['agility'] : 0;
        $presence = isset($_POST['presence']) ? (int)$_POST['presence'] : 0;
        $toughness = isset($_POST['toughness']) ? (int)$_POST['toughness'] : 0;
        $spirit = isset($_POST['spirit']) ? (int)$_POST['spirit'] : 0;
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
            
            try {
                // If sheet_id is empty, this is a new sheet
                if (empty($sheet_id)) {
                    // First insert the main sheet data
                    $query = "INSERT INTO character_sheets (user_id, system, name, image_path, created_at, updated_at) 
                             VALUES (?, ?, ?, ?, ?, ?)";
                    
                    $stmt = $conn->prepare($query);
                    $stmt->execute([$user_id, $system, $name, $image_path, $now, $now]);
                    
                    // Get the new sheet ID
                    $sheet_id = $conn->lastInsertId();
                    
                    // Insert system-specific data
                    if ($system === 'pirate_borg') {
                        $query = "INSERT INTO pirate_borg_sheets (sheet_id, character_type, strength, agility, presence, toughness, spirit, notes) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                        
                        $stmt = $conn->prepare($query);
                        $stmt->execute([$sheet_id, $_POST['character_type'], $strength, $agility, $presence, $toughness, $spirit, $notes]);
                    }
                    
                    $success_message = "Character sheet created successfully!";
                } else {
                    // First update the main sheet data
                    $query = "UPDATE character_sheets SET system = ?, name = ?, image_path = ?, updated_at = ? 
                             WHERE id = ? AND user_id = ?";
                    
                    $stmt = $conn->prepare($query);
                    $stmt->execute([$system, $name, $image_path, $now, $sheet_id, $user_id]);
                    
                    // Update system-specific data based on selected system
                    if ($system === 'pirate_borg') {
                        // Check if system data exists
                        $check = $conn->prepare("SELECT 1 FROM pirate_borg_sheets WHERE sheet_id = ?");
                        $check->execute([$sheet_id]);
                        
                        if ($check->fetchColumn()) {
                            // Update
                            $query = "UPDATE pirate_borg_sheets SET character_type = ?, strength = ?, agility = ?, presence = ?, 
                                    toughness = ?, spirit = ?, notes = ? WHERE sheet_id = ?";
                            
                            $stmt = $conn->prepare($query);
                            $stmt->execute([$_POST['character_type'], $strength, $agility, $presence, $toughness, $spirit, $notes, $sheet_id]);
                        } else {
                            // Insert
                            $query = "INSERT INTO pirate_borg_sheets (sheet_id, character_type, strength, agility, presence, toughness, spirit, notes) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                            
                            $stmt = $conn->prepare($query);
                            $stmt->execute([$sheet_id, $_POST['character_type'], $strength, $agility, $presence, $toughness, $spirit, $notes]);
                        }
                    }
                    
                    $success_message = "Character sheet updated successfully!";
                }
                
                // Commit transaction
                $conn->commit();
                
                // Redirect to the sheets page after successful save
                header("Location: /sheets.php?success=1&id={$sheet_id}");
                exit;
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollBack();
                throw $e;
            }
            
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// Include header
require_once '../components/topbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo empty($sheet_id) ? 'Create Character Sheet' : 'Edit ' . htmlspecialchars($sheet['name']); ?></title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/topbar.css">
    <link rel="stylesheet" href="../css/character-sheet.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
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
                                    <select id="system" name="system" class="form-control">
                                        <option value="pirate_borg" <?php echo (isset($sheet['system']) && $sheet['system'] === 'pirate_borg') ? 'selected' : ''; ?>>Pirate Borg</option>
                                        <!-- More game systems can be added here in the future -->
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name">Character Name</label>
                                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($sheet['name']); ?>" required>
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
                                        <img id="image-preview" src="<?php echo htmlspecialchars($sheet['image_path']); ?>" 
                                             alt="Character Portrait" 
                                             onerror="this.src='../assets/TSP_default_character.jpg'">
                                        <div class="edit-icon" onclick="openPhotoManagement(<?php echo $sheet_id ? $sheet_id : 'null'; ?>)">
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
                                <div class="attributes-grid">
                                    <div class="attribute-item">
                                        <label for="strength">Strength</label>
                                        <input type="text" id="strength" name="strength" value="<?php echo (int)$sheet['strength']; ?>" class="attribute-field">
                                    </div>
                                    <div class="attribute-item">
                                        <label for="agility">Agility</label>
                                        <input type="text" id="agility" name="agility" value="<?php echo (int)$sheet['agility']; ?>" class="attribute-field">
                                    </div>
                                    <div class="attribute-item">
                                        <label for="presence">Presence</label>
                                        <input type="text" id="presence" name="presence" value="<?php echo (int)$sheet['presence']; ?>" class="attribute-field">
                                    </div>
                                    <div class="attribute-item">
                                        <label for="toughness">Toughness</label>
                                        <input type="text" id="toughness" name="toughness" value="<?php echo (int)$sheet['toughness']; ?>" class="attribute-field">
                                    </div>
                                    <div class="attribute-item">
                                        <label for="spirit">Spirit</label>
                                        <input type="text" id="spirit" name="spirit" value="<?php echo (int)$sheet['spirit']; ?>" class="attribute-field">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="edit-section">
                                <div class="form-group">
                                    <label for="notes">Notes</label>
                                    <textarea id="notes" name="notes"><?php echo htmlspecialchars($sheet['notes']); ?></textarea>
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
    
    <!-- Photo Management Modal -->
    <div id="photo-management-modal" class="photo-management-modal">
        <div class="photo-management-container">
            <div class="photo-management-header">
                <h3>Character Image Manager</h3>
                <button class="photo-management-close">&times;</button>
            </div>
            
            <div class="photo-gallery" id="photo-gallery">
                <!-- Photos will be loaded here -->
                <div class="loading-photos">
                    <i class="fas fa-spinner fa-spin"></i> Loading photos...
                </div>
            </div>
            
            <div class="upload-section">
                <h4>Upload New Image</h4>
                <div class="upload-dropzone" id="upload-dropzone">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Drag and drop an image here, or click to select a file</p>
                </div>
                <form id="upload-form" class="upload-form" enctype="multipart/form-data">
                    <input type="file" id="file-input" name="image" accept="image/*" style="display: none;">
                    <input type="hidden" id="sheet-id-input" name="sheet_id">
                </form>
            </div>
            
            <div class="photo-management-actions">
                <button id="cancel-photo-selection" class="btn btn-secondary">Cancel</button>
                <button id="apply-photo-selection" class="btn btn-primary">Apply Selected Photo</button>
            </div>
        </div>
    </div>

    <!-- Delete Photo Confirmation Modal -->
    <div id="delete-photo-modal" class="delete-photo-modal">
        <div class="delete-photo-container">
            <div class="delete-photo-header">
                <h3>Delete Photo</h3>
            </div>
            <div class="delete-photo-body">
                <p>Are you sure you want to delete this photo?</p>
                <div id="delete-photo-sheets" class="delete-photo-sheets" style="display: none;">
                    <p><strong>Warning:</strong> This photo is currently used by the following character sheets:</p>
                    <ul id="delete-photo-sheets-list"></ul>
                    <p>If you delete this photo, it will be replaced with the default image on these sheets.</p>
                </div>
            </div>
            <div class="delete-photo-actions">
                <button id="cancel-photo-delete" class="btn btn-secondary">Cancel</button>
                <button id="confirm-photo-delete" class="btn btn-danger">Delete Photo</button>
            </div>
        </div>
    </div>
    
    <script>
        // Image preview functionality
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('image-preview').src = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Show/hide system-specific fields based on system selection
        document.getElementById('system').addEventListener('change', function() {
            const system = this.value;
            
            // Hide all system-specific fields
            document.querySelectorAll('.system-specific-fields').forEach(el => {
                el.classList.remove('active');
            });
            
            // Show the selected system's fields
            if (system === 'pirate_borg') {
                document.getElementById('pirate-borg-fields').classList.add('active');
            }
            // Add more system handlers here in the future
        });

        // Input validation for attribute fields
        document.querySelectorAll('.attribute-field').forEach(input => {
            // Allow only numbers (including negative)
            input.addEventListener('input', function(e) {
                let value = this.value;
                // Remove any non-digit characters except minus sign at the beginning
                if (value.startsWith('-')) {
                    value = '-' + value.substring(1).replace(/[^\d]/g, '');
                } else {
                    value = value.replace(/[^\d]/g, '');
                }
                this.value = value;
            });

            // Ensure proper number formatting when focus is lost
            input.addEventListener('blur', function() {
                if (this.value === '') {
                    this.value = '0';
                } else if (this.value === '-') {
                    this.value = '0';
                }
            });
        });

        // Form validation
        document.getElementById('sheet-form').addEventListener('submit', function(e) {
            // Ensure all attribute fields contain valid numbers
            const attributeFields = document.querySelectorAll('.attribute-field');
            let isValid = true;
            
            attributeFields.forEach(field => {
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

        // Photo management functionality
        let currentPhotoManagementSheetId = null;
        let selectedPhotoPath = null;
        let photoToDelete = null;

        // Open photo management modal
        function openPhotoManagement(sheetId) {
            currentPhotoManagementSheetId = sheetId;
            document.getElementById('sheet-id-input').value = sheetId;
            document.getElementById('photo-management-modal').style.display = 'block';
            loadUserPhotos();
        }

        // Close photo management modal
        function closePhotoManagement() {
            document.getElementById('photo-management-modal').style.display = 'none';
            document.getElementById('photo-gallery').innerHTML = '';
            currentPhotoManagementSheetId = null;
            selectedPhotoPath = null;
        }

        // Load user photos
        function loadUserPhotos() {
            const photoGallery = document.getElementById('photo-gallery');
            photoGallery.innerHTML = '<div class="loading-photos"><i class="fas fa-spinner fa-spin"></i> Loading photos...</div>';
            
            fetch('../sheets/api/get_user_photos.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderPhotoGallery(data.photos);
                    } else {
                        photoGallery.innerHTML = `<div class="error-message">${data.error || 'Failed to load photos'}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Error loading photos:', error);
                    photoGallery.innerHTML = '<div class="error-message">Failed to load photos. Please try again.</div>';
                });
        }

        // Render photo gallery
        function renderPhotoGallery(photos) {
            const photoGallery = document.getElementById('photo-gallery');
            
            if (!photos || photos.length === 0) {
                photoGallery.innerHTML = '<div class="no-photos">You don\'t have any uploaded photos yet.</div>';
                return;
            }
            
            // Get current image path
            let currentImagePath = document.getElementById('image-preview').src;
            // Convert relative URL to path if needed
            if (currentImagePath.includes('/')) {
                const pathParts = currentImagePath.split('/');
                const filename = pathParts[pathParts.length - 1];
                if (filename !== 'TSP_default_character.jpg') {
                    currentImagePath = '../uploads/character_sheets/' + filename;
                }
            }
            
            // Create gallery HTML
            photoGallery.innerHTML = '';
            photos.forEach(photo => {
                const isSelected = photo.path === currentImagePath || photo.path === selectedPhotoPath;
                const photoItem = document.createElement('div');
                photoItem.className = `photo-item ${isSelected ? 'selected' : ''}`;
                photoItem.innerHTML = `
                    <img src="${photo.path}" alt="Character Photo">
                    <div class="photo-actions">
                        <button class="photo-action-btn" onclick="selectPhoto('${photo.path}', this.parentNode.parentNode)">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="photo-action-btn photo-action-delete" onclick="confirmDeletePhoto('${photo.path}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
                photoGallery.appendChild(photoItem);
            });
        }

        // Select a photo
        function selectPhoto(path, photoElement) {
            selectedPhotoPath = path;
            
            // Update selected class
            document.querySelectorAll('.photo-item').forEach(item => {
                item.classList.remove('selected');
            });
            
            photoElement.classList.add('selected');
        }

        // Apply selected photo to the form
        function applySelectedPhoto() {
            if (!selectedPhotoPath) {
                return;
            }
            
            // Update the preview image
            document.getElementById('image-preview').src = selectedPhotoPath;
            
            // Set the value in the hidden input
            document.getElementById('selected_image_path').value = selectedPhotoPath;
            
            // Close the modal
            closePhotoManagement();
        }

        // Confirm photo deletion
        function confirmDeletePhoto(path) {
            photoToDelete = path;
            
            // Check if the photo is used by any sheets
            fetch(`../sheets/api/check_photo_usage.php?path=${encodeURIComponent(path)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const usedBySheets = data.sheets || [];
                        const deletePhotoSheets = document.getElementById('delete-photo-sheets');
                        const deletePhotoSheetsList = document.getElementById('delete-photo-sheets-list');
                        
                        if (usedBySheets.length > 0) {
                            deletePhotoSheetsList.innerHTML = '';
                            usedBySheets.forEach(sheet => {
                                const li = document.createElement('li');
                                li.textContent = sheet.name;
                                deletePhotoSheetsList.appendChild(li);
                            });
                            deletePhotoSheets.style.display = 'block';
                        } else {
                            deletePhotoSheets.style.display = 'none';
                        }
                        
                        document.getElementById('delete-photo-modal').style.display = 'flex';
                    } else {
                        alert(data.error || 'Failed to check photo usage');
                    }
                })
                .catch(error => {
                    console.error('Error checking photo usage:', error);
                    alert('Failed to check photo usage. Please try again.');
                });
        }

        // Delete photo
        function deletePhoto() {
            if (!photoToDelete) {
                return;
            }
            
            fetch('../sheets/api/delete_photo.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    path: photoToDelete
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close delete modal
                    closeDeletePhotoModal();
                    
                    // Reload photos
                    loadUserPhotos();
                    
                    // If the current image was this photo, reset it to default
                    if (document.getElementById('image-preview').src.includes(photoToDelete)) {
                        document.getElementById('image-preview').src = '../assets/TSP_default_character.jpg';
                        document.getElementById('selected_image_path').value = '';
                    }
                } else {
                    alert(data.error || 'Failed to delete photo');
                }
            })
            .catch(error => {
                console.error('Error deleting photo:', error);
                alert('Failed to delete photo. Please try again.');
            });
        }

        // Close delete photo modal
        function closeDeletePhotoModal() {
            document.getElementById('delete-photo-modal').style.display = 'none';
            photoToDelete = null;
        }

        // Set up upload functionality
        document.addEventListener('DOMContentLoaded', function() {
            const photoManagementModal = document.getElementById('photo-management-modal');
            const photoManagementClose = document.querySelector('.photo-management-close');
            const uploadDropzone = document.getElementById('upload-dropzone');
            const fileInput = document.getElementById('file-input');
            const uploadForm = document.getElementById('upload-form');
            const applyPhotoBtn = document.getElementById('apply-photo-selection');
            const cancelPhotoBtn = document.getElementById('cancel-photo-selection');
            const confirmDeleteBtn = document.getElementById('confirm-photo-delete');
            const cancelDeleteBtn = document.getElementById('cancel-photo-delete');
            
            // Close photo management modal
            photoManagementClose.addEventListener('click', closePhotoManagement);
            cancelPhotoBtn.addEventListener('click', closePhotoManagement);
            
            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === photoManagementModal) {
                    closePhotoManagement();
                }
            });
            
            // Apply selected photo
            applyPhotoBtn.addEventListener('click', applySelectedPhoto);
            
            // Delete photo confirmation
            confirmDeleteBtn.addEventListener('click', deletePhoto);
            cancelDeleteBtn.addEventListener('click', closeDeletePhotoModal);
            
            // Upload dropzone click
            uploadDropzone.addEventListener('click', function() {
                fileInput.click();
            });
            
            // File selection
            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    uploadPhoto(this.files[0]);
                }
            });
            
            // Drag and drop
            uploadDropzone.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.style.borderColor = 'var(--accent-primary)';
                this.style.backgroundColor = 'rgba(255, 255, 255, 0.05)';
            });
            
            uploadDropzone.addEventListener('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.style.borderColor = 'var(--accent-secondary)';
                this.style.backgroundColor = '';
            });
            
            uploadDropzone.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.style.borderColor = 'var(--accent-secondary)';
                this.style.backgroundColor = '';
                
                if (e.dataTransfer.files.length > 0) {
                    uploadPhoto(e.dataTransfer.files[0]);
                }
            });
            
            // Upload photo
            function uploadPhoto(file) {
                // Check file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Invalid file type. Please upload a JPEG, PNG, or GIF image.');
                    return;
                }
                
                // Create form data
                const formData = new FormData();
                formData.append('image', file);
                formData.append('sheet_id', currentPhotoManagementSheetId);
                
                // Show loading state
                uploadDropzone.innerHTML = '<i class="fas fa-spinner fa-spin"></i><p>Uploading...</p>';
                
                // Upload file
                fetch('../sheets/api/upload_photo.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reset dropzone
                        uploadDropzone.innerHTML = '<i class="fas fa-cloud-upload-alt"></i><p>Drag and drop an image here, or click to select a file</p>';
                        
                        // Select the new photo
                        selectedPhotoPath = data.path;
                        
                        // Reload photos
                        loadUserPhotos();
                    } else {
                        alert(data.error || 'Failed to upload photo');
                        uploadDropzone.innerHTML = '<i class="fas fa-cloud-upload-alt"></i><p>Drag and drop an image here, or click to select a file</p>';
                    }
                })
                .catch(error => {
                    console.error('Error uploading photo:', error);
                    alert('Failed to upload photo. Please try again.');
                    uploadDropzone.innerHTML = '<i class="fas fa-cloud-upload-alt"></i><p>Drag and drop an image here, or click to select a file</p>';
                });
            }
        });
    </script>
</body>
</html> 