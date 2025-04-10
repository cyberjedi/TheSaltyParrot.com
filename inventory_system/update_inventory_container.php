<?php
// inventory_system/update_inventory_container.php

session_start();
require_once dirname(__DIR__) . '/config/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in using the standard session check
if (!isset($_SESSION['uid'])) { // Use standard check
    echo json_encode(['success' => false, 'message' => 'User not logged in or session expired.']);
    http_response_code(401); // Unauthorized
    exit;
}

// Get the raw POST data
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// --- Input Validation ---
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data.']);
    exit;
}

$sheet_id = $data['sheet_id'] ?? null;
$item_map_id = $data['item_map_id'] ?? null;
$target_container_map_id = $data['target_container_map_id'] ?? null;
$user_uid = $_SESSION['uid']; // Get UID from session

// Validate required fields
if (empty($sheet_id) || !is_numeric($sheet_id) || empty($item_map_id) || !is_numeric($item_map_id) || $target_container_map_id === null) {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid required parameters.']);
    exit;
}

// Convert 'root' to null for database update
if ($target_container_map_id === 'root') {
    $target_container_map_id = null;
} elseif (!is_numeric($target_container_map_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid target container ID.']);
    exit;
}

// Prevent putting an item inside itself
if ($item_map_id == $target_container_map_id) {
     echo json_encode(['success' => false, 'message' => 'Cannot place an item inside itself.']);
     exit;
}

try {
    $conn->beginTransaction();

    // Step 1: Verify User Permissions (Reinstated for Security)
    // Check if the character sheet being modified actually belongs to the logged-in user.
    $stmt_check_owner = $conn->prepare("SELECT 1 FROM character_sheets WHERE id = :sheet_id AND user_id = :user_uid");
    $stmt_check_owner->bindParam(':sheet_id', $sheet_id, PDO::PARAM_INT);
    $stmt_check_owner->bindParam(':user_uid', $user_uid, PDO::PARAM_STR); // user_uid comes from $_SESSION['uid']
    $stmt_check_owner->execute();

    if ($stmt_check_owner->rowCount() == 0) {
        // If no row is found, the sheet doesn't exist OR it doesn't belong to this user.
        throw new Exception('Authorization failed: You do not own this character sheet or it does not exist.');
    }

    // 2. Verify Item Ownership: Check if the item being moved belongs to the character sheet
    $stmt_check_item = $conn->prepare("SELECT 1 FROM inventory_map WHERE map_id = :item_map_id AND character_id = :sheet_id");
    $stmt_check_item->bindParam(':item_map_id', $item_map_id, PDO::PARAM_INT);
    $stmt_check_item->bindParam(':sheet_id', $sheet_id, PDO::PARAM_INT);
    $stmt_check_item->execute();

    if ($stmt_check_item->rowCount() == 0) {
        throw new Exception('Item being moved does not belong to this character.');
    }

    // 3. Verify Target Container (if not null/root): 
    //    a) Check if it belongs to the same character
    //    b) Check if it is actually a container or source type
    //    c) Check for potential circular nesting (prevent putting a container inside an item already within it)
    if ($target_container_map_id !== null) {
        $stmt_check_target = $conn->prepare("
            SELECT i.item_type
            FROM inventory_map im
            JOIN inventory_items i ON im.item_id = i.item_id
            WHERE im.map_id = :target_map_id AND im.character_id = :sheet_id
        ");
        $stmt_check_target->bindParam(':target_map_id', $target_container_map_id, PDO::PARAM_INT);
        $stmt_check_target->bindParam(':sheet_id', $sheet_id, PDO::PARAM_INT);
        $stmt_check_target->execute();
        $target_item_info = $stmt_check_target->fetch(PDO::FETCH_ASSOC);

        if (!$target_item_info) {
            throw new Exception('Target container does not exist or does not belong to this character.');
        }

        if ($target_item_info['item_type'] !== 'Container' && $target_item_info['item_type'] !== 'Source') {
            throw new Exception('Target item is not a valid container type (must be Container or Source).');
        }
        
        // 3c. Circular Reference Check (Advanced)
        // Start from the target container and traverse up its parents.
        // If we encounter the item being moved (`$item_map_id`), it's a circular reference.
        $current_check_id = $target_container_map_id;
        while ($current_check_id !== null) {
            if ($current_check_id == $item_map_id) {
                throw new Exception('Circular nesting detected: Cannot move a container into an item that is already inside it.');
            }
            // Get the parent container_id of the current item being checked
            $stmt_parent = $conn->prepare("SELECT container_id FROM inventory_map WHERE map_id = :current_map_id");
            $stmt_parent->bindParam(':current_map_id', $current_check_id, PDO::PARAM_INT);
            $stmt_parent->execute();
            $parent_result = $stmt_parent->fetch(PDO::FETCH_ASSOC);
            $current_check_id = $parent_result ? $parent_result['container_id'] : null;
        }
    }

    // 4. Update the item's container_id
    $stmt_update = $conn->prepare("UPDATE inventory_map SET container_id = :target_container_map_id WHERE map_id = :item_map_id AND character_id = :sheet_id");
    $stmt_update->bindParam(':target_container_map_id', $target_container_map_id, PDO::PARAM_INT);
    $stmt_update->bindParam(':item_map_id', $item_map_id, PDO::PARAM_INT);
    $stmt_update->bindParam(':sheet_id', $sheet_id, PDO::PARAM_INT);
    $stmt_update->execute();

    if ($stmt_update->rowCount() > 0) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Item moved successfully.']);
    } else {
        // This could happen if the item didn't exist or didn't belong to the user (already checked, but good failsafe)
        throw new Exception('Failed to update item container. Item might not exist or belong to this sheet.');
    }

} catch (Exception $e) {
    $conn->rollBack();
    error_log("Error updating inventory container: " . $e->getMessage() . " | Data: " . json_encode($data) . " | User: " . $user_uid);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn = null; // Close connection
?> 