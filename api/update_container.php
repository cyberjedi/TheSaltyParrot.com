<?php
/**
 * API Endpoint: Update Container
 * Moves an item into or out of a container
 */

// Set content type to JSON
header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is authenticated
// Load Discord configuration if not already loaded
if (!function_exists('is_discord_authenticated')) {
    require_once dirname(__DIR__) . '/discord/discord-config.php';
}

$is_authenticated = function_exists('is_discord_authenticated') && is_discord_authenticated();

if (!$is_authenticated) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Authentication required'
    ]);
    exit;
}

// Get POST data as JSON
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Validate request data
$item_map_id = isset($data['item_map_id']) ? (int)$data['item_map_id'] : 0;
$container_map_id = isset($data['container_map_id']) ? (int)$data['container_map_id'] : null;
$character_id = isset($data['character_id']) ? (int)$data['character_id'] : 0;

if (!$item_map_id || !$character_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Item map ID and character ID are required'
    ]);
    exit;
}

// Connect to database
try {
    require_once dirname(__DIR__) . '/config/db_connect.php';
    
    // Get user ID from session
    $discord_id = $_SESSION['discord_user']['id'] ?? null;
    
    if (!$discord_id) {
        throw new Exception("Discord user ID not found in session");
    }
    
    // Verify that the character belongs to this user
    $stmt = $conn->prepare("
        SELECT c.id 
        FROM characters c 
        JOIN discord_users u ON c.user_id = u.id 
        WHERE c.id = :character_id AND u.discord_id = :discord_id
    ");
    $stmt->bindParam(':character_id', $character_id);
    $stmt->bindParam(':discord_id', $discord_id);
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Character not found or unauthorized access'
        ]);
        exit;
    }
    
    // Verify that the item exists and belongs to this character
    $stmt = $conn->prepare("
        SELECT im.map_id, im.item_id, i.item_type 
        FROM inventory_map im
        JOIN inventory_items i ON im.item_id = i.item_id
        WHERE im.map_id = :item_map_id 
        AND im.character_id = :character_id
    ");
    $stmt->bindParam(':item_map_id', $item_map_id);
    $stmt->bindParam(':character_id', $character_id);
    $stmt->execute();
    
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Item not found or not authorized to access'
        ]);
        exit;
    }
    
    // If container_map_id is set, verify it exists and belongs to this character
    if ($container_map_id !== null && $container_map_id > 0) {
        $stmt = $conn->prepare("
            SELECT im.map_id, i.item_type
            FROM inventory_map im
            JOIN inventory_items i ON im.item_id = i.item_id
            WHERE im.map_id = :container_map_id 
            AND im.character_id = :character_id
            AND i.item_type = 'Container'
        ");
        $stmt->bindParam(':container_map_id', $container_map_id);
        $stmt->bindParam(':character_id', $character_id);
        $stmt->execute();
        
        $container = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$container) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Container not found or not a valid container'
            ]);
            exit;
        }
        
        // Prevent circular containment - check if this would create a circular reference
        if ($item['item_type'] == 'Container') {
            // The item is a container - check if the target container is inside this container
            $circular = checkCircularReference($conn, $item_map_id, $container_map_id);
            
            if ($circular) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Cannot move a container inside itself or one of its descendants'
                ]);
                exit;
            }
        }
    }
    
    // Update the item's container
    $stmt = $conn->prepare("
        UPDATE inventory_map 
        SET container_id = :container_id
        WHERE map_id = :map_id
        AND character_id = :character_id
    ");
    
    // Set container_id to NULL if moving to root level
    if ($container_map_id === null || $container_map_id === 0) {
        $stmt->bindValue(':container_id', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindParam(':container_id', $container_map_id);
    }
    
    $stmt->bindParam(':map_id', $item_map_id);
    $stmt->bindParam(':character_id', $character_id);
    $stmt->execute();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Item moved successfully',
        'item_map_id' => $item_map_id,
        'container_map_id' => $container_map_id
    ]);
    
} catch (Exception $e) {
    error_log("API Error (update_container): " . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

/**
 * Recursively check if moving a container would create a circular reference
 * 
 * @param PDO $conn Database connection
 * @param int $source_container_id The container being moved
 * @param int $target_container_id The destination container
 * @return bool True if circular reference would be created
 */
function checkCircularReference($conn, $source_container_id, $target_container_id) {
    // If target is the source, that's a circular reference
    if ($source_container_id == $target_container_id) {
        return true;
    }
    
    // Check if target container is inside the source container
    $stmt = $conn->prepare("
        SELECT container_id 
        FROM inventory_map
        WHERE map_id = :target_id
    ");
    $stmt->bindParam(':target_id', $target_container_id);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If target has no container, it's at root level
    if (!$result || $result['container_id'] === null) {
        return false;
    }
    
    // If target's container is the source, that's circular
    if ($result['container_id'] == $source_container_id) {
        return true;
    }
    
    // Check recursively up the container hierarchy
    return checkCircularReference($conn, $source_container_id, $result['container_id']);
}