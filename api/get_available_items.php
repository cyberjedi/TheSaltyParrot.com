<?php
/**
 * API Endpoint: Get Available Items
 * Returns a list of items that can be added to a character's inventory
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

// Get character ID from query params
$character_id = isset($_GET['character_id']) ? (int)$_GET['character_id'] : 0;

if (!$character_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Character ID is required'
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
    
    // Get all available items
    $stmt = $conn->prepare("
        SELECT * FROM inventory_items
        ORDER BY item_type, item_name
    ");
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return items as JSON
    echo json_encode([
        'status' => 'success',
        'items' => $items
    ]);
    
} catch (Exception $e) {
    error_log("API Error (get_available_items): " . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}