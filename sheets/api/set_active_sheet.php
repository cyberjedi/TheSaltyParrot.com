<?php
/**
 * Set Active Character Sheet API
 * 
 * Sets a character sheet as the active one for the current user
 * Only one character can be active at a time for each user
 */

// Start the session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set headers for JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['uid'])) {
    echo json_encode([
        'success' => false,
        'error' => 'User not authenticated'
    ]);
    exit;
}

// Get the user ID
$user_id = $_SESSION['uid'];

// Check if sheet ID was provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Sheet ID is required'
    ]);
    exit;
}

// Get the sheet ID
$sheet_id = (int)$_GET['id'];

// Include database connection
require_once '../../config/db_connect.php';

try {
    // Start a transaction
    $conn->beginTransaction();
    
    // First, check if the sheet exists and belongs to the user
    $stmt = $conn->prepare("SELECT id FROM character_sheets WHERE id = ? AND user_id = ?");
    $stmt->execute([$sheet_id, $user_id]);
    $sheet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sheet) {
        throw new Exception('Sheet not found or does not belong to you');
    }
    
    // Reset all active sheets for this user
    $stmt = $conn->prepare("UPDATE character_sheets SET is_active = 0 WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // Set the selected sheet as active
    $stmt = $conn->prepare("UPDATE character_sheets SET is_active = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$sheet_id, $user_id]);
    
    // Commit the transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Sheet set as active'
    ]);
} catch (Exception $e) {
    // Rollback the transaction if something went wrong
    if ($conn) {
        $conn->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 