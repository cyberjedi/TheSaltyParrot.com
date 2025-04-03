<?php
/**
 * Update Profile API
 * 
 * Handles user profile updates and syncs with the database
 */

// Start the session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set JSON content type
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode([
        'error' => 'Not authenticated',
        'session' => array_keys($_SESSION)
    ]);
    exit;
}

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Debug received data
$debug = [
    'received_data' => $data,
    'session_data' => [
        'uid' => $_SESSION['uid'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'displayName' => $_SESSION['displayName'] ?? null
    ]
];

// Validate input
if (!isset($data['displayName']) || trim($data['displayName']) === '') {
    http_response_code(400);
    echo json_encode([
        'error' => 'Display name is required',
        'debug' => $debug
    ]);
    exit;
}

// Clean and validate photo URL
$photoURL = null;
if (isset($data['photoURL']) && trim($data['photoURL']) !== '') {
    $photoURL = filter_var(trim($data['photoURL']), FILTER_VALIDATE_URL);
    if ($photoURL === false) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Invalid photo URL',
            'provided_url' => $data['photoURL'],
            'debug' => $debug
        ]);
        exit;
    }
}

try {
    // Include database connection
    require_once __DIR__ . '/../config/db_connect.php';
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Prepare the query
    $query = "
        UPDATE users 
        SET display_name = ?, 
            photo_url = ?
        WHERE uid = ?
    ";

    // Prepare and execute
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . print_r($conn->errorInfo(), true));
    }

    $params = [
        trim($data['displayName']),
        $photoURL,
        $_SESSION['uid']
    ];

    $result = $stmt->execute($params);
    
    if ($result) {
        // Check if any rows were affected
        if ($stmt->rowCount() === 0) {
            // No rows updated, try to insert
            $insertQuery = "
                INSERT INTO users (uid, display_name, photo_url, email)
                VALUES (?, ?, ?, ?)
            ";
            
            $stmt = $conn->prepare($insertQuery);
            if (!$stmt) {
                throw new Exception('Failed to prepare insert statement: ' . print_r($conn->errorInfo(), true));
            }
            
            $result = $stmt->execute([
                $_SESSION['uid'],
                trim($data['displayName']),
                $photoURL,
                $_SESSION['email'] ?? null
            ]);
            
            if (!$result) {
                throw new Exception('Failed to insert user: ' . print_r($stmt->errorInfo(), true));
            }
        }
        
        // Update session
        $_SESSION['displayName'] = trim($data['displayName']);
        $_SESSION['photoURL'] = $photoURL;
        
        // Return success
        echo json_encode([
            'success' => true,
            'user' => [
                'displayName' => $_SESSION['displayName'],
                'photoURL' => $_SESSION['photoURL']
            ],
            'debug' => $debug
        ]);
    } else {
        throw new Exception('Failed to execute statement: ' . print_r($stmt->errorInfo(), true));
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to update profile',
        'message' => $e->getMessage(),
        'debug' => array_merge($debug, [
            'error_trace' => $e->getTraceAsString(),
            'error_line' => $e->getLine(),
            'error_file' => $e->getFile()
        ])
    ]);
    exit;
} 