<?php
/**
 * Inventory System API: Update Item Quantity
 * Updates the quantity of an item instance (map_id) in a character sheet's inventory.
 * If quantity reaches 0 or less, the item is removed.
 */

header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['uid'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Authentication required'
    ]);
    http_response_code(401); // Unauthorized
    exit;
}

// Get user ID from session
$user_id = $_SESSION['uid'];

// Get POST data as JSON (Should be PUT or PATCH, but PHP handles body similarly)
// Consider switching to PUT/PATCH method in JS and checking $_SERVER['REQUEST_METHOD']
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Validate request data
$map_id = isset($data['map_id']) ? (int)$data['map_id'] : 0;
$change = isset($data['change']) ? (int)$data['change'] : 0; // Expecting +1 or -1 generally

if (!$map_id || $change === 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Inventory map ID (map_id) and a non-zero change value are required'
    ]);
    http_response_code(400); // Bad Request
    exit;
}

// Connect to database
try {
    require_once dirname(__DIR__) . '/config/db_connect.php';
    
    // Verify that this inventory item belongs to a sheet owned by the user
    // and get the current quantity
    $stmt = $conn->prepare("
        SELECT im.map_id, im.character_id, im.map_quantity, i.item_name
        FROM inventory_map im
        JOIN character_sheets cs ON im.character_id = cs.id
        JOIN inventory_items i ON im.item_id = i.item_id
        WHERE im.map_id = :map_id AND cs.user_id = :user_id
    ");
    $stmt->bindParam(':map_id', $map_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $inventory_item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$inventory_item) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Item not found in your inventory or unauthorized access'
        ]);
        http_response_code(404); // Not Found or Forbidden
        exit;
    }

    // Calculate new quantity
    $current_quantity = (int)$inventory_item['map_quantity'];
    $new_quantity = $current_quantity + $change;

    $conn->beginTransaction();

    if ($new_quantity <= 0) {
        // Quantity is zero or less, remove the item
        $deleteStmt = $conn->prepare("DELETE FROM inventory_map WHERE map_id = :map_id");
        $deleteStmt->bindParam(':map_id', $map_id, PDO::PARAM_INT);
        $deleteStmt->execute();
        $conn->commit();

        echo json_encode([
            'status' => 'success',
            'action' => 'deleted',
            'message' => 'Item "' . htmlspecialchars($inventory_item['item_name']) . '" removed (quantity reached zero)',
            'map_id' => $map_id,
            'quantity' => 0
        ]);

    } else {
        // Update the quantity
        $updateStmt = $conn->prepare("UPDATE inventory_map SET map_quantity = :quantity WHERE map_id = :map_id");
        $updateStmt->bindParam(':quantity', $new_quantity, PDO::PARAM_INT);
        $updateStmt->bindParam(':map_id', $map_id, PDO::PARAM_INT);
        $updateStmt->execute();
        $conn->commit();

        echo json_encode([
            'status' => 'success',
            'action' => 'updated',
            'message' => 'Item quantity updated',
            'map_id' => $map_id,
            'quantity' => $new_quantity
        ]);
    }
    
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Database Error (inventory/update_quantity): " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred'
    ]);
    http_response_code(500); // Internal Server Error
} catch (Exception $e) {
     if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("API Error (inventory/update_quantity): " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
    http_response_code(500); // Internal Server Error
}
?> 