<?php
/**
 * Delete Photo API (Modified)
 *
 * Removes the association of an image path from character sheets belonging to the user.
 * Does NOT delete the physical file.
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An error occurred.'];

// Check authentication
if (!isset($_SESSION['uid'])) {
    http_response_code(401);
    $response['message'] = 'User not logged in.';
    echo json_encode($response);
    exit;
}
$user_id = $_SESSION['uid'];

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

// Get image_path from request body
$input = json_decode(file_get_contents('php://input'), true);
$image_path = isset($input['image_path']) ? trim($input['image_path']) : null;

if (empty($image_path)) {
    http_response_code(400);
    $response['message'] = 'Image path not provided.';
    echo json_encode($response);
    exit;
}

require_once '../config/db_connect.php';

try {
    error_log("delete_photo.php: Attempting to remove association for path: {$image_path} for user: {$user_id}");

    if (!isset($conn) || $conn === null) {
        throw new Exception('Database connection failed');
    }

    // Update character sheets: set image_path to NULL for matching user and image_path
    $stmt = $conn->prepare("
        UPDATE character_sheets
        SET image_path = NULL
        WHERE user_id = :user_id
        AND image_path = :image_path
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_STR);
    $stmt->bindParam(':image_path', $image_path, PDO::PARAM_STR);
    
    $execute_success = $stmt->execute();
    
    if ($execute_success) {
        $affected_rows = $stmt->rowCount();
        error_log("delete_photo.php: Update executed. Rows affected: {$affected_rows}");
        
        // Now, attempt to delete the physical file
        $file_deleted = false;
        $file_path_relative_to_script = '../' . $image_path;
        if (file_exists($file_path_relative_to_script)) {
            if (unlink($file_path_relative_to_script)) {
                error_log("delete_photo.php: Successfully deleted file: {$file_path_relative_to_script}");
                $file_deleted = true;
            } else {
                error_log("delete_photo.php: FAILED to delete file: {$file_path_relative_to_script}");
                // Don't treat this as a fatal error for the response, but log it.
            }
        } else {
            error_log("delete_photo.php: File not found, skipping delete: {$file_path_relative_to_script}");
            $file_deleted = true; // Consider it success if file is already gone
        }
        
        // Update response message based on DB and file results
        if ($affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = "Photo association removed from {$affected_rows} character sheet(s).";
            if ($file_deleted) $response['message'] .= " File deleted.";
            else $response['message'] .= " File deletion failed.";
        } else {
            $response['success'] = true; // No DB rows updated
            $response['message'] = 'No character sheets found using this image for this user.';
            if ($file_deleted) $response['message'] .= " File deleted.";
            else $response['message'] .= " File not found or deletion failed.";
        }
    } else {
        error_log("delete_photo.php: UPDATE statement failed to execute.");
        throw new Exception('Database update failed.');
    }

} catch (PDOException $e) {
    error_log("Database Error (delete_photo): " . $e->getMessage());
    http_response_code(500);
    $response['message'] = 'Database error occurred while removing photo association.';
} catch (Exception $e) {
    error_log("General Error (delete_photo): " . $e->getMessage());
    http_response_code(500);
    $response['message'] = 'An internal error occurred: ' . $e->getMessage();
}

echo json_encode($response);
?> 