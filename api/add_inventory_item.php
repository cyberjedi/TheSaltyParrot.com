<?php
/**
 * API Endpoint: Add Inventory Item
 * Adds an item to a character's inventory
 */

// Set content type to JSON
header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is authenticated
$is_authenticated = isset($_SESSION['discord_authenticated']) && $_SESSION['discord_authenticated'] === true;

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
$character_id = isset($data['character_id']) ? (int)$data['character_id'] : 0;
$item_id = isset($data['item_id']) ? (int)$data['item_id'] : 0;

if (!$character_id || !$item_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Character ID and Item ID are required'
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
    
    // Verify that the item exists
    $stmt = $conn->prepare("
        SELECT item_id FROM inventory_items
        WHERE item_id = :item_id
    ");
    $stmt->bindParam(':item_id', $item_id);
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Item not found'
        ]);
        exit;
    }
    
    // Check if this item is already in the character's inventory
    $stmt = $conn->prepare("
        SELECT map_id, map_quantity FROM inventory_map
        WHERE character_id = :character_id AND item_id = :item_id
    ");
    $stmt->bindParam(':character_id', $character_id);
    $stmt->bindParam(':item_id', $item_id);
    $stmt->execute();
    
    $existing_item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_item) {
        // Item already exists, increment quantity
        $new_quantity = $existing_item['map_quantity'] + 1;
        
        $stmt = $conn->prepare("
            UPDATE inventory_map 
            SET map_quantity = :quantity
            WHERE map_id = :map_id
        ");
        $stmt->bindParam(':quantity', $new_quantity);
        $stmt->bindParam(':map_id', $existing_item['map_id']);
        $stmt->execute();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Item quantity increased',
            'map_id' => $existing_item['map_id'],
            'quantity' => $new_quantity
        ]);
    } else {
        // Add new item to inventory with quantity 1
        $stmt = $conn->prepare("
            INSERT INTO inventory_map (character_id, item_id, map_quantity)
            VALUES (:character_id, :item_id, 1)
        ");
        $stmt->bindParam(':character_id', $character_id);
        $stmt->bindParam(':item_id', $item_id);
        $stmt->execute();
        
        $map_id = $conn->lastInsertId();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Item added to inventory',
            'map_id' => $map_id,
            'quantity' => 1
        ]);
    }
    
} catch (Exception $e) {
    error_log("API Error (add_inventory_item): " . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}