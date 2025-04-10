<?php
/**
 * Inventory System API: Get Item Details
 * Returns detailed information about a specific inventory item
 */

// Set content type to JSON
header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is authenticated (using the standard session check)
if (!isset($_SESSION['uid'])) {
    http_response_code(401); // Unauthorized
    echo json_encode([
        'status' => 'error',
        'message' => 'Authentication required'
    ]);
    exit;
}

// Get item ID from query params
$item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;

if (!$item_id) {
    http_response_code(400); // Bad Request
    echo json_encode([
        'status' => 'error',
        'message' => 'Item ID is required'
    ]);
    exit;
}

// Connect to database
try {
    // Path is correct as dirname(__DIR__) now points to the project root
    require_once dirname(__DIR__) . '/config/db_connect.php';
    
    // Get the item details
    $stmt = $conn->prepare("
        SELECT * FROM inventory_items
        WHERE item_id = :item_id
    ");
    $stmt->bindParam(':item_id', $item_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        http_response_code(404); // Not Found
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
    
} catch (PDOException $e) {
    error_log("Database Error (inventory/get_item_details): " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred'
    ]);
    http_response_code(500); // Internal Server Error
} catch (Exception $e) {
    error_log("API Error (inventory/get_item_details): " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
    http_response_code(500); // Internal Server Error
}
?> 