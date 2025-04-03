<?php
/**
 * Get Character Sheet API
 * 
 * Retrieves a single character sheet by ID
 */

// Start the session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set JSON content type
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Check if sheet ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Sheet ID is required']);
    exit;
}

$sheet_id = (int)$_GET['id'];
$user_id = $_SESSION['uid'];

try {
    // Include database connection
    require_once '../../config/db_connect.php';
    
    if (!isset($conn) || $conn === null) {
        throw new Exception('Database connection failed');
    }

    // First get the main sheet data
    $stmt = $conn->prepare("SELECT * FROM character_sheets WHERE id = ? AND user_id = ?");
    $stmt->execute([$sheet_id, $user_id]);
    $sheet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sheet) {
        http_response_code(404);
        echo json_encode(['error' => 'Sheet not found or access denied']);
        exit;
    }
    
    // Based on the system, load the appropriate system-specific data
    if ($sheet['system'] === 'pirate_borg') {
        $stmt = $conn->prepare("SELECT * FROM pirate_borg_sheets WHERE sheet_id = ?");
        $stmt->execute([$sheet_id]);
        $system_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Merge system data into the sheet array
        if ($system_data) {
            $sheet = array_merge($sheet, $system_data);
        }
    }
    // Add elseif blocks for other systems here in the future
    
    // Return sheet as JSON
    echo json_encode([
        'success' => true,
        'sheet' => $sheet
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to load character sheet: ' . $e->getMessage()
    ]);
    exit;
} 