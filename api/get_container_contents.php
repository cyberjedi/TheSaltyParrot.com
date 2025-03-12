<?php
/**
 * API Endpoint: Get Container Contents
 * Returns items stored inside a container
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

// Get container map_id from query params
$container_map_id = isset($_GET['container_map_id']) ? (int)$_GET['container_map_id'] : 0;
$character_id = isset($_GET['character_id']) ? (int)$_GET['character_id'] : 0;

if (!$container_map_id || !$character_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Container map ID and character ID are required'
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
    
    // Verify that the container exists and belongs to this character
    $stmt = $conn->prepare("
        SELECT im.map_id, i.item_name 
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
            'message' => 'Container not found or not authorized to access'
        ]);
        exit;
    }
    
    // Get all items inside the container
    $stmt = $conn->prepare("
        SELECT im.map_id, im.map_quantity, im.container_id, i.* 
        FROM inventory_map im 
        JOIN inventory_items i ON im.item_id = i.item_id 
        WHERE im.character_id = :character_id
        AND im.container_id = :container_map_id
        ORDER BY i.item_type, i.item_name
    ");
    $stmt->bindParam(':character_id', $character_id);
    $stmt->bindParam(':container_map_id', $container_map_id);
    $stmt->execute();
    
    $container_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return items as JSON
    echo json_encode([
        'status' => 'success',
        'container' => [
            'map_id' => $container['map_id'],
            'name' => $container['item_name']
        ],
        'items' => $container_items
    ]);
    
} catch (Exception $e) {
    error_log("API Error (get_container_contents): " . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}