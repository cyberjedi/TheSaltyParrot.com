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
        $strength = (int)$_POST['strength'];
        $agility = (int)$_POST['agility'];
        $presence = (int)$_POST['presence'];
        $toughness = (int)$_POST['toughness'];
        $spirit = (int)$_POST['spirit'];
        $notes = htmlspecialchars($_POST['notes']);
    }
    
    // Handle image upload
    $image_path = isset($sheet['image_path']) ? $sheet['image_path'] : '../assets/TSP_default_character.jpg';
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
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
                
                $image_path = $upload_path;
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
                        $query = "INSERT INTO pirate_borg_sheets (sheet_id, strength, agility, presence, toughness, spirit, notes) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?)";
                        
                        $stmt = $conn->prepare($query);
                        $stmt->execute([$sheet_id, $strength, $agility, $presence, $toughness, $spirit, $notes]);
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
                            $query = "UPDATE pirate_borg_sheets SET strength = ?, agility = ?, presence = ?, 
                                    toughness = ?, spirit = ?, notes = ? WHERE sheet_id = ?";
                            
                            $stmt = $conn->prepare($query);
                            $stmt->execute([$strength, $agility, $presence, $toughness, $spirit, $notes, $sheet_id]);
                        } else {
                            // Insert
                            $query = "INSERT INTO pirate_borg_sheets (sheet_id, strength, agility, presence, toughness, spirit, notes) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?)";
                            
                            $stmt = $conn->prepare($query);
                            $stmt->execute([$sheet_id, $strength, $agility, $presence, $toughness, $spirit, $notes]);
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
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Styles for the sheet editor */
        .sheet-container {
            max-width: 800px;
            margin: 30px auto;
            background-color: #202020;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
            color: #e1e1e1;
        }
        
        .sheet-header {
            background-color: #41C8D4;
            color: #000;
            padding: 15px 20px;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .sheet-header h2 {
            margin: 0;
            font-size: 1.4rem;
        }
        
        .sheet-body {
            padding: 20px;
        }
        
        .edit-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .edit-sections {
                grid-template-columns: 1fr;
            }
        }
        
        .edit-section {
            margin-bottom: 20px;
        }
        
        .form-row {
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 10px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            background-color: #333;
            border: 1px solid #444;
            border-radius: 4px;
            color: #e1e1e1;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .attributes-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .attribute-item {
            display: flex;
            flex-direction: column;
        }
        
        .attribute-item label {
            margin-bottom: 5px;
        }
        
        .attribute-item input {
            width: 60px;
            padding: 5px;
            text-align: center;
            margin-right: 5px;
        }
        
        .profile-image-preview {
            width: 150px;
            height: 150px;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .profile-image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        
        .btn-primary {
            background-color: #41C8D4;
            color: #000;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .alert {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .alert-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .alert-success {
            background-color: #28a745;
            color: white;
        }
        
        /* System-specific styles */
        .system-specific-fields {
            display: none;
        }
        
        .system-specific-fields.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
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
                                <label for="image">Character Image</label>
                                <div class="profile-image-preview">
                                    <img id="image-preview" src="<?php echo htmlspecialchars($sheet['image_path']); ?>" 
                                         alt="Character Portrait" 
                                         onerror="this.src='../assets/TSP_default_character.jpg'">
                                </div>
                                <input type="file" id="image" name="image" accept="image/*">
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
                                    <input type="number" id="strength" name="strength" min="0" max="99" value="<?php echo (int)$sheet['strength']; ?>">
                                </div>
                                <div class="attribute-item">
                                    <label for="agility">Agility</label>
                                    <input type="number" id="agility" name="agility" min="0" max="99" value="<?php echo (int)$sheet['agility']; ?>">
                                </div>
                                <div class="attribute-item">
                                    <label for="presence">Presence</label>
                                    <input type="number" id="presence" name="presence" min="0" max="99" value="<?php echo (int)$sheet['presence']; ?>">
                                </div>
                                <div class="attribute-item">
                                    <label for="toughness">Toughness</label>
                                    <input type="number" id="toughness" name="toughness" min="0" max="99" value="<?php echo (int)$sheet['toughness']; ?>">
                                </div>
                                <div class="attribute-item">
                                    <label for="spirit">Spirit</label>
                                    <input type="number" id="spirit" name="spirit" min="0" max="99" value="<?php echo (int)$sheet['spirit']; ?>">
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
    </script>
</body>
</html> 