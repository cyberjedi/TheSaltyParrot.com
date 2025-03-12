<?php
/**
 * API Endpoint: Update Item Quantity
 * Increases or decreases the quantity of an item in a character's inventory
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
$map_id = isset($data['map_id']) ? (int)$data['map_id'] : 0;
$change = isset($data['change']) ? (int)$data['change'] : 0;

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
    
    // First, get the inventory item details
    $stmt = $conn->prepare("
        SELECT im.*, c.id as char_id
        FROM inventory_map im
        JOIN characters c ON im.character_id = c.id
        JOIN discord_users u ON c.user_id = u.id
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
    
    // Calculate new quantity
    $new_quantity = $inventory_item['map_quantity'] + $change;
    
    // Ensure quantity doesn't go below 1
    if ($new_quantity < 1) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Quantity cannot be less than 1'
        ]);
        exit;
    }
    
    // Update the quantity
    $stmt = $conn->prepare("
        UPDATE inventory_map 
        SET map_quantity = :quantity
        WHERE map_id = :map_id
    ");
    $stmt->bindParam(':quantity', $new_quantity);
    $stmt->bindParam(':map_id', $map_id);
    $stmt->execute();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Quantity updated',
        'map_id' => $map_id,
        'quantity' => $new_quantity
    ]);
    
} catch (Exception $e) {
    error_log("API Error (update_item_quantity): " . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}