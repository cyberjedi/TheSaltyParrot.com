<?php
/**
 * Inventory System API: Remove Item
 * Removes an item instance (map_id) from a character sheet's inventory.
 * Prevents removal of non-empty containers.
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

// Get POST data as JSON (should be DELETE request, but PHP handles body similarly)
// Consider switching to DELETE method in JS and checking $_SERVER['REQUEST_METHOD'] === 'DELETE'
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Validate request data
$map_id = isset($data['map_id']) ? (int)$data['map_id'] : 0;

if (!$map_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Inventory map ID (map_id) is required'
    ]);
    http_response_code(400); // Bad Request
    exit;
}

// Connect to database
try {
    require_once dirname(__DIR__) . '/config/db_connect.php';
    
    // Verify that this inventory item belongs to a sheet owned by the user
    // Also fetch item_type to check if it's a container
    $stmt = $conn->prepare("
        SELECT im.map_id, im.character_id, i.item_type, i.item_name
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

    // If it's a container, check if it's empty
    if ($inventory_item['item_type'] === 'Container') {
        $checkContainerStmt = $conn->prepare("
            SELECT COUNT(*) FROM inventory_map WHERE container_id = :container_map_id
        ");
        $checkContainerStmt->bindParam(':container_map_id', $map_id, PDO::PARAM_INT);
        $checkContainerStmt->execute();
        $itemCount = $checkContainerStmt->fetchColumn();

        if ($itemCount > 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Cannot remove container: It is not empty. Please remove items inside first.'
            ]);
            http_response_code(400); // Bad Request
            exit;
        }
    }

    // Proceed with deletion
    $deleteStmt = $conn->prepare("DELETE FROM inventory_map WHERE map_id = :map_id");
    $deleteStmt->bindParam(':map_id', $map_id, PDO::PARAM_INT);
    
    $conn->beginTransaction();
    $deleteStmt->execute();
    $rowCount = $deleteStmt->rowCount();
    $conn->commit();

    if ($rowCount > 0) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Item "' . htmlspecialchars($inventory_item['item_name']) . '" removed from inventory',
            'map_id' => $map_id
        ]);
    } else {
        // Should not happen if the initial check passed, but good practice
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to remove item (already removed or error occurred).'
        ]);
        http_response_code(500);
    }
    
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Database Error (inventory/remove_item): " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred'
    ]);
    http_response_code(500); // Internal Server Error
} catch (Exception $e) {
     if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("API Error (inventory/remove_item): " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
    http_response_code(500); // Internal Server Error
}
?> 