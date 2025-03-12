<?php
/**
 * API Endpoint: Get Item Details
 * Returns detailed information about a specific inventory item
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

// Get item ID from query params
$item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;

if (!$item_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Item ID is required'
    ]);
    exit;
}

// Connect to database
try {
    require_once dirname(__DIR__) . '/config/db_connect.php';
    
    // Get the item details
    $stmt = $conn->prepare("
        SELECT * FROM inventory_items
        WHERE item_id = :item_id
    ");
    $stmt->bindParam(':item_id', $item_id);
    $stmt->execute();
    
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Item not found'
        ]);
        exit;
    }
    
    // Return item details as JSON
    echo json_encode([
        'status' => 'success',
        'item' => $item
    ]);
    
} catch (Exception $e) {
    error_log("API Error (get_item_details): " . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}