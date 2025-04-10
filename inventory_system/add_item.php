<?php
/**
 * Inventory System API: Add Item
 * Adds an item to a character sheet's inventory, optionally into a container.
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

// Get POST data as JSON
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Validate request data
$sheet_id = isset($data['sheet_id']) ? (int)$data['sheet_id'] : 0;
$item_id = isset($data['item_id']) ? (int)$data['item_id'] : 0;
$container_id = isset($data['container_id']) && $data['container_id'] !== 'root' && $data['container_id'] !== null ? (int)$data['container_id'] : null;

if (!$sheet_id || !$item_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Sheet ID and Item ID are required'
    ]);
    http_response_code(400); // Bad Request
    exit;
}

// Connect to database
try {
    require_once dirname(__DIR__) . '/config/db_connect.php';
    
    // Verify that the character sheet belongs to this user
    $stmt = $conn->prepare("SELECT id FROM character_sheets WHERE id = :sheet_id AND user_id = :user_id");
    $stmt->bindParam(':sheet_id', $sheet_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT); // Assuming user_id in DB is INT
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Character sheet not found or unauthorized access'
        ]);
        http_response_code(403); // Forbidden
        exit;
    }
    
    // Verify that the item exists in the master item list
    $stmt = $conn->prepare("SELECT item_id FROM inventory_items WHERE item_id = :item_id");
    $stmt->bindParam(':item_id', $item_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Item not found in master list'
        ]);
        http_response_code(404); // Not Found
        exit;
    }

    // If a container_id is specified, verify it belongs to this character sheet
    if ($container_id !== null) {
        $stmt = $conn->prepare("SELECT map_id FROM inventory_map WHERE map_id = :container_id AND character_id = :sheet_id");
        $stmt->bindParam(':container_id', $container_id, PDO::PARAM_INT);
        $stmt->bindParam(':sheet_id', $sheet_id, PDO::PARAM_INT);
        $stmt->execute();
        if (!$stmt->fetch()) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Specified container not found in this inventory'
            ]);
            http_response_code(404); // Not Found
            exit;
        }
        // Optional: Check if the container item itself is of type 'Container'? Maybe later.
    }
    
    // Check if this item already exists in the inventory (in the specified container or root)
    $stmt = $conn->prepare("
        SELECT map_id, map_quantity FROM inventory_map
        WHERE character_id = :sheet_id 
          AND item_id = :item_id 
          AND container_id <=> :container_id
    "); // Using NULL-safe equals for container_id
    $stmt->bindParam(':sheet_id', $sheet_id, PDO::PARAM_INT);
    $stmt->bindParam(':item_id', $item_id, PDO::PARAM_INT);
    $stmt->bindParam(':container_id', $container_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $existing_item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $conn->beginTransaction();

    if ($existing_item) {
        // Item already exists in this location, increment quantity
        $new_quantity = $existing_item['map_quantity'] + 1;
        
        $updateStmt = $conn->prepare("
            UPDATE inventory_map 
            SET map_quantity = :quantity
            WHERE map_id = :map_id
        ");
        $updateStmt->bindParam(':quantity', $new_quantity, PDO::PARAM_INT);
        $updateStmt->bindParam(':map_id', $existing_item['map_id'], PDO::PARAM_INT);
        $updateStmt->execute();
        
        $conn->commit();

        echo json_encode([
            'status' => 'success',
            'action' => 'updated',
            'message' => 'Item quantity increased',
            'map_id' => $existing_item['map_id'],
            'item_id' => $item_id,
            'quantity' => $new_quantity,
            'container_id' => $container_id
        ]);
    } else {
        // Add new item to inventory with quantity 1 in the specified location
        $insertStmt = $conn->prepare("
            INSERT INTO inventory_map (character_id, item_id, map_quantity, container_id)
            VALUES (:sheet_id, :item_id, 1, :container_id)
        ");
        $insertStmt->bindParam(':sheet_id', $sheet_id, PDO::PARAM_INT);
        $insertStmt->bindParam(':item_id', $item_id, PDO::PARAM_INT);
        $insertStmt->bindParam(':container_id', $container_id, PDO::PARAM_INT);
        $insertStmt->execute();
        
        $map_id = $conn->lastInsertId();
        $conn->commit();

        // Optional: Fetch the newly added item details to return to the client?
        // For now, just return success and IDs.
        echo json_encode([
            'status' => 'success',
            'action' => 'added',
            'message' => 'Item added to inventory',
            'map_id' => $map_id,
            'item_id' => $item_id,
            'quantity' => 1,
            'container_id' => $container_id
        ]);
    }
    
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Database Error (inventory/add_item): " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred'
    ]);
    http_response_code(500); // Internal Server Error
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("API Error (inventory/add_item): " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
    http_response_code(500); // Internal Server Error
}
?> 