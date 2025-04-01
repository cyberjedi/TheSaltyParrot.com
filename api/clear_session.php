<?php
/**
 * Clear Firebase Session
 * 
 * Clears the PHP session of Firebase user data
 */

// Start the session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include Firebase configuration
require_once __DIR__ . '/../config/firebase-config.php';

// Set JSON content type
header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Clear Firebase session data
unset($_SESSION['firebase_user']);
unset($_SESSION['firebase_token']);

// Return success response
echo json_encode(['success' => true]); 