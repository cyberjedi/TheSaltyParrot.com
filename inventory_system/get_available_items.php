<?php
/**
 * Inventory System API: Get Available Items
 * Returns a list of items from the master inventory_items table,
 * optionally filtered by type and search term.
 */

header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in (required to access this list)
if (!isset($_SESSION['uid'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Authentication required'
    ]);
    http_response_code(401); // Unauthorized
    exit;
}

// Get filter parameters from query string
$filter_type = isset($_GET['type']) ? trim($_GET['type']) : '';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Connect to database
try {
    require_once dirname(__DIR__) . '/config/db_connect.php';
    
    // Base query
    $query = "SELECT item_id, item_name, item_type, item_description FROM inventory_items"; // Corrected - Removed 'source'
    $conditions = [];
    $params = [];

    // Add type filter if provided
    if (!empty($filter_type)) {
        $conditions[] = "item_type = :type";
        $params[':type'] = $filter_type;
    }

    // Add search term filter if provided
    if (!empty($search_term)) {
        $conditions[] = "item_name LIKE :search";
        $params[':search'] = '%' . $search_term . '%';
    }

    // Append conditions to the query
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(' AND ', $conditions);
    }

    // Add ordering
    $query .= " ORDER BY item_type, item_name";

    // Prepare and execute the statement
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return items as JSON
    echo json_encode([
        'status' => 'success',
        'items' => $items
    ]);
    
} catch (PDOException $e) {
    error_log("Database Error (inventory/get_available_items): " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred'
    ]);
    http_response_code(500); // Internal Server Error
} catch (Exception $e) {
    error_log("API Error (inventory/get_available_items): " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
    http_response_code(500); // Internal Server Error
}
?> 