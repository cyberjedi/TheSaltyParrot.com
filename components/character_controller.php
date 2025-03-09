<?php
/**
 * Character Controller
 * 
 * Handles all character-related operations:
 * - Character creation
 * - Character updates
 * - Loading character data
 * - Character switching
 */

// Start the session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize variables
$character_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$character = null;
$error_message = null;
$success_message = null;
$user_characters = [];

// Map Discord user to database user_id
$user_id = null;
$discord_id = null;
$db_user_id = null;

// Get Discord authentication status
$discord_authenticated = function_exists('is_discord_authenticated') && is_discord_authenticated();

// Get Discord ID from session if authenticated
if ($discord_authenticated && isset($_SESSION['discord_user']['id'])) {
    $discord_id = $_SESSION['discord_user']['id'];
    
    // Get or create a database user_id for this Discord user
    try {
        require_once dirname(__DIR__) . '/config/db_connect.php';
        
        // First check if this Discord user already has a user_id
        $stmt = $conn->prepare("SELECT id FROM discord_users WHERE discord_id = :discord_id");
        $stmt->bindParam(':discord_id', $discord_id);
        $stmt->execute();
        
        $discord_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($discord_user && isset($discord_user['id'])) {
            // Use the existing user ID
            $user_id = $discord_user['id'];
            $db_user_id = $user_id;
        } else {
            // Create a new user entry if needed
            $stmt = $conn->prepare("INSERT INTO discord_users (discord_id, username, created_at, last_login) 
                                    VALUES (:discord_id, :username, NOW(), NOW())");
            $username = $_SESSION['discord_user']['username'] ?? 'Unknown User';
            $stmt->bindParam(':discord_id', $discord_id);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            $user_id = $conn->lastInsertId();
            $db_user_id = $user_id;
        }
        
        error_log("Mapped Discord ID {$discord_id} to database user_id {$user_id}");
    } catch (Exception $e) {
        error_log("Error mapping Discord user to database user: " . $e->getMessage());
        // Fallback to a generic user ID
        $user_id = 1;
        $db_user_id = 1;
    }
} else {
    // Not authenticated, use default user_id
    $user_id = 0;
    $db_user_id = 0;
}

// Load the user's characters from the database
try {
    // Get database connection
    if (!isset($conn)) {
        require_once dirname(__DIR__) . '/config/db_connect.php';
    }
    
    if ($user_id && isset($conn)) {
        // Load all characters for this user
        $stmt = $conn->prepare("SELECT * FROM characters WHERE user_id = :user_id ORDER BY updated_at DESC");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $user_characters = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Loaded " . count($user_characters) . " characters for user_id {$user_id}");
    } else {
        error_log("Cannot load characters: user_id is not set or database connection failed");
    }
} catch (Exception $e) {
    error_log("Error loading user characters: " . $e->getMessage());
}

// If a character ID is provided, load the character from the database
if ($character_id) {
    try {
        if (!isset($conn)) {
            require_once dirname(__DIR__) . '/config/db_connect.php';
        }
        
        // Make sure the character belongs to this user
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
    // No character ID provided, load the most recent character
    if (!empty($user_characters)) {
        // Use the first character (already sorted by updated_at DESC)
        $character = $user_characters[0];
    } else {
        // No characters found, create a default template
        $default_image_path = 'assets/TSP_default_character.jpg';
        
        // Create parent directory if it doesn't exist
        if (!file_exists('assets')) {
            mkdir('assets', 0755, true);
        }
        
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
}

/**
 * Process character creation/update form
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_character') {
    // Get the posted character data
    $char_id = isset($_POST['character_id']) ? $_POST['character_id'] : '';
    $name = htmlspecialchars($_POST['name']);
    $strength = (int)$_POST['strength'];
    $agility = (int)$_POST['agility'];
    $presence = (int)$_POST['presence'];
    $toughness = (int)$_POST['toughness'];
    $spirit = (int)$_POST['spirit'];
    
    // Use the properly mapped user ID
    error_log("Creating/updating character for user_id: $user_id (mapped from Discord ID: " . (isset($discord_id) ? $discord_id : 'not set') . ")");
    
    // Handle image upload
    $image_path = 'assets/TSP_default_character.jpg'; // Default image path
    
    // If character has an image path and it exists, use it
    if (isset($character['image_path']) && !empty($character['image_path']) && file_exists($character['image_path'])) {
        $image_path = $character['image_path'];
    }
    
    if (isset($_FILES['character_image']) && $_FILES['character_image']['error'] === UPLOAD_ERR_OK) {
        // Create uploads directory if it doesn't exist
        $upload_dir = 'uploads/characters/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Get file size and validate (2MB limit)
        $file_size = $_FILES['character_image']['size'];
        if ($file_size > 2 * 1024 * 1024) {
            $error_message = "Image file is too large. Maximum size is 2MB.";
        } else {
            // Sanitize filename and generate a unique name
            $file_extension = pathinfo($_FILES['character_image']['name'], PATHINFO_EXTENSION);
            $new_filename = 'character_' . time() . '_' . uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $new_filename;
            
            // Validate file type
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array(strtolower($file_extension), $allowed_types)) {
                // Move uploaded file
                if (move_uploaded_file($_FILES['character_image']['tmp_name'], $target_path)) {
                    // Process the image to ensure it's not too large
                    if (function_exists('imagecreatefromjpeg')) {
                        // Resize image if GD library is available
                        $max_dimension = 500; // Maximum width or height
                        list($width, $height) = getimagesize($target_path);
                        
                        if ($width > $max_dimension || $height > $max_dimension) {
                            // Calculate new dimensions
                            if ($width > $height) {
                                $new_width = $max_dimension;
                                $new_height = intval($height * $max_dimension / $width);
                            } else {
                                $new_height = $max_dimension;
                                $new_width = intval($width * $max_dimension / $height);
                            }
                            
                            // Create new image
                            $source = null;
                            $new_image = imagecreatetruecolor($new_width, $new_height);
                            
                            // Load source image based on type
                            switch (strtolower($file_extension)) {
                                case 'jpg':
                                case 'jpeg':
                                    $source = imagecreatefromjpeg($target_path);
                                    break;
                                case 'png':
                                    $source = imagecreatefrompng($target_path);
                                    imagecolortransparent($new_image, imagecolorallocate($new_image, 0, 0, 0));
                                    imagealphablending($new_image, false);
                                    imagesavealpha($new_image, true);
                                    break;
                                case 'gif':
                                    $source = imagecreatefromgif($target_path);
                                    break;
                            }
                            
                            if ($source) {
                                // Resize
                                imagecopyresampled($new_image, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                                
                                // Save
                                switch (strtolower($file_extension)) {
                                    case 'jpg':
                                    case 'jpeg':
                                        imagejpeg($new_image, $target_path, 90);
                                        break;
                                    case 'png':
                                        imagepng($new_image, $target_path, 9);
                                        break;
                                    case 'gif':
                                        imagegif($new_image, $target_path);
                                        break;
                                }
                                
                                // Free memory
                                imagedestroy($source);
                                imagedestroy($new_image);
                            }
                        }
                    }
                    
                    $image_path = $target_path;
                } else {
                    $error_message = "Failed to upload image.";
                }
            } else {
                $error_message = "Invalid file type. Allowed types: jpg, jpeg, png, gif.";
            }
        }
    }

    // Use database connection
    try {
        if (!isset($conn)) {
            require_once dirname(__DIR__) . '/config/db_connect.php';
        }
        
        // Create date fields
        $now = date('Y-m-d H:i:s');
        
        // If character_id is empty, this is a new character
        if (empty($char_id)) {
            // Use a minimal insert query
            $query = "INSERT INTO characters (user_id, name, image_path, strength, agility, presence, toughness, spirit, created_at, updated_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([$user_id, $name, $image_path, $strength, $agility, $presence, $toughness, $spirit, $now, $now]);
            
            // Get the new character ID
            $char_id = $conn->lastInsertId();
        } else {
            // Simple update query - make sure it's this user's character
            $query = "UPDATE characters SET name = ?, image_path = ?, strength = ?, agility = ?, 
                    presence = ?, toughness = ?, spirit = ?, updated_at = ? WHERE id = ? AND user_id = ?";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([$name, $image_path, $strength, $agility, $presence, $toughness, $spirit, $now, $char_id, $user_id]);
        }
        
        // Fetch the newly created/updated character
        $stmt = $conn->prepare("SELECT * FROM characters WHERE id = ?");
        $stmt->execute([$char_id]);
        $character = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Log success
        error_log("Successfully saved character with ID: $char_id for user_id: $user_id");
        
        // Reload the user's characters
        $stmt = $conn->prepare("SELECT * FROM characters WHERE user_id = ? ORDER BY updated_at DESC");
        $stmt->execute([$user_id]);
        $user_characters = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Set success message for display
        $success_message = "Character saved successfully!";
        
        // Redirect to prevent form resubmission
        header("Location: /character_sheet.php?id=" . $char_id . "&success=1");
        exit;
        
    } catch (Exception $e) {
        error_log("Database error saving character: " . $e->getMessage());
        $error_message = "Unable to save to database: " . $e->getMessage();
    }
}