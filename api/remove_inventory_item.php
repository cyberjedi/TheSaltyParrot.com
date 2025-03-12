<?php
/**
 * API Endpoint: Remove Inventory Item
 * Removes an item from a character's inventory
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
$map_id = isset($data['map_id']) ? (int)$data['map_id'] : 0;

if (!$map_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Inventory map ID is required'
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
    
    // First, verify that this inventory item belongs to the user's character
    $stmt = $conn->prepare("
        SELECT im.*, c.id as char_id, i.item_name
        FROM inventory_map im
        JOIN characters c ON im.character_id = c.id
        JOIN discord_users u ON c.user_id = u.id
        JOIN inventory_items i ON im.item_id = i.item_id
        WHERE im.map_id = :map_id AND u.discord_id = :discord_id
    ");
    $stmt->bindParam(':map_id', $map_id);
    $stmt->bindParam(':discord_id', $discord_id);
    $stmt->execute();
    
    $inventory_item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$inventory_item) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Item not found or unauthorized access'
        ]);
        exit;
    }
    
    // Delete the inventory mapping
    $stmt = $conn->prepare("
        DELETE FROM inventory_map 
        WHERE map_id = :map_id
    ");
    $stmt->bindParam(':map_id', $map_id);
    $stmt->execute();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Item removed from inventory',
        'item_name' => $inventory_item['item_name']
    ]);
    
} catch (Exception $e) {
    error_log("API Error (remove_inventory_item): " . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}