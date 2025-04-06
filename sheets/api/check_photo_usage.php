<?php
/**
 * Check Photo Usage API
 *
 * Checks if a given image path is currently assigned to any character sheets
 * belonging to the logged-in user.
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Default response
$response = ['inUse' => false, 'count' => 0, 'characterNames' => []];

// Check authentication
if (!isset($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}
$user_id = $_SESSION['uid'];

// Get image_path from request body
$input = json_decode(file_get_contents('php://input'), true);
$image_path = isset($input['image_path']) ? trim($input['image_path']) : null;

if (empty($image_path)) {
    http_response_code(400);
    echo json_encode(['error' => 'Image path not provided']);
    exit;
}

require_once '../../config/db_connect.php';

try {
    if (!isset($conn) || $conn === null) {
        throw new Exception('Database connection failed');
    }

    // Query to find character sheets using this image path for the current user
    $stmt = $conn->prepare("
        SELECT id, name
        FROM character_sheets
        WHERE user_id = :user_id
        AND image_path = :image_path
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_STR);
    $stmt->bindParam(':image_path', $image_path, PDO::PARAM_STR);
    $stmt->execute();

    $characters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = count($characters);

    if ($count > 0) {
        $response['inUse'] = true;
        $response['count'] = $count;
        $response['characterNames'] = array_column($characters, 'name');
    }

    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Database Error (check_photo_usage): " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred while checking photo usage.']);
} catch (Exception $e) {
    error_log("General Error (check_photo_usage): " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An internal error occurred: ' . $e->getMessage()]);
} 