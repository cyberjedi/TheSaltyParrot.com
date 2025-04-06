<?php
/**
 * Get Character Sheets API
 * 
 * Retrieves all character sheets for the current user
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

$user_id = $_SESSION['uid'];

// Get query parameters
$system_filter = isset($_GET['system']) ? $_GET['system'] : null;

try {
    // Include database connection
    require_once '../../config/db_connect.php';
    
    if (!isset($conn) || $conn === null) {
        throw new Exception('Database connection failed');
    }

    // Base query
    $query = "SELECT * FROM character_sheets WHERE user_id = ?";
    $params = [$user_id];
    
    // Add system filter if provided
    if ($system_filter) {
        $query .= " AND system = ?";
        $params[] = $system_filter;
    }
    
    // Add order by
    $query .= " ORDER BY is_active DESC, updated_at DESC";
    
    // Get all sheets for this user
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $sheets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // For each sheet, get the system-specific data
    foreach ($sheets as $key => $sheet) {
        if ($sheet['system'] === 'pirate_borg') {
            $stmt = $conn->prepare("SELECT * FROM pirate_borg_sheets WHERE sheet_id = ?");
            $stmt->execute([$sheet['id']]);
            $system_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Merge system data into the sheet array
            if ($system_data) {
                $sheets[$key] = array_merge($sheet, $system_data);
            }
        }
        // Add elseif blocks for other systems here in the future
    }
    
    // Return sheets as JSON
    echo json_encode([
        'success' => true,
        'sheets' => $sheets
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to load character sheets: ' . $e->getMessage()
    ]);
    exit;
} 