<?php
/**
 * API Endpoint: Get Inventory HTML
 * 
 * Fetches and renders only the HTML for the inventory section of a character sheet.
 * Used for dynamically updating the inventory display without reloading the whole sheet.
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include DB connection
require_once dirname(__DIR__) . '/config/db_connect.php';

// Get sheet_id from query parameters
$sheet_id = isset($_GET['sheet_id']) ? (int)$_GET['sheet_id'] : 0;

// Get user UID from session
$user_uid = $_SESSION['uid'] ?? null;

// --- Authorization Check --- 
if (!$user_uid) {
    http_response_code(401); // Unauthorized
    echo '<div class="alert alert-danger">Error: Not authenticated.</div>';
    exit;
}

if (!$sheet_id) {
    http_response_code(400); // Bad Request
    echo '<div class="alert alert-danger">Error: Sheet ID not provided.</div>';
    exit;
}

try {
    // Verify User Permissions: Check if the sheet belongs to the logged-in user
    $stmt_check_owner = $conn->prepare("SELECT 1 FROM character_sheets WHERE id = :sheet_id AND user_id = :user_uid");
    $stmt_check_owner->bindParam(':sheet_id', $sheet_id, PDO::PARAM_INT);
    $stmt_check_owner->bindParam(':user_uid', $user_uid, PDO::PARAM_STR);
    $stmt_check_owner->execute();

    if ($stmt_check_owner->rowCount() == 0) {
        http_response_code(403); // Forbidden
        echo '<div class="alert alert-danger">Error: You do not have permission to view this inventory or the sheet does not exist.</div>';
        exit;
    }

    // If authorized, include the inventory display component
    // inventory_display.php expects $sheet_id to be set in its scope
    ob_start(); // Start output buffering
    include 'inventory_display.php';
    $inventory_html = ob_get_clean(); // Get buffered output

    // Set content type to HTML
    header('Content-Type: text/html');
    echo $inventory_html;

} catch (PDOException $e) {
    error_log("Database Error (get_inventory_html for sheet {$sheet_id}): " . $e->getMessage());
    http_response_code(500);
    echo '<div class="alert alert-danger">Error: A database error occurred while loading inventory.</div>';
} catch (Exception $e) {
    error_log("General Error (get_inventory_html for sheet {$sheet_id}): " . $e->getMessage());
    http_response_code(500);
    echo '<div class="alert alert-danger">Error: An unexpected error occurred while loading inventory.</div>';
}

$conn = null; // Close connection
?> 