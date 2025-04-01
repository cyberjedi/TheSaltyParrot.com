<?php
/**
 * Update Firebase Session
 * 
 * Updates the PHP session with Firebase user data
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

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate required fields
if (!isset($data['uid']) || !isset($data['email']) || !isset($data['token'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Store user data in session
$_SESSION['firebase_user'] = [
    'uid' => $data['uid'],
    'email' => $data['email'],
    'displayName' => $data['displayName'] ?? null,
    'photoURL' => $data['photoURL'] ?? null
];

$_SESSION['firebase_token'] = $data['token'];

// Return success response
echo json_encode(['success' => true]); 