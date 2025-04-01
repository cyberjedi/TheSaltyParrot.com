<?php
require_once 'config/firebase-config.php';
require_once 'sync_user.php';

// Get the ID token from the POST request
$idToken = $_POST['idToken'] ?? null;

if (!$idToken) {
    http_response_code(400);
    echo json_encode(['error' => 'No ID token provided']);
    exit;
}

try {
    // Verify the ID token
    $verifiedIdToken = $auth->verifyIdToken($idToken);
    
    // Get the user's UID
    $uid = $verifiedIdToken->claims()->get('sub');
    
    // Get the user's data
    $user = $auth->getUser($uid);
    
    // Sync user data with MySQL
    syncUser(
        $uid,
        $user->email,
        $user->displayName,
        $user->photoUrl
    );
    
    // Set session variables
    $_SESSION['uid'] = $uid;
    $_SESSION['email'] = $user->email;
    $_SESSION['displayName'] = $user->displayName;
    $_SESSION['photoURL'] = $user->photoUrl;
    
    // Return success response
    echo json_encode([
        'success' => true,
        'user' => [
            'uid' => $uid,
            'email' => $user->email,
            'displayName' => $user->displayName,
            'photoURL' => $user->photoUrl
        ]
    ]);
} catch (Exception $e) {
    error_log("Firebase Auth Error: " . $e->getMessage());
    http_response_code(401);
    echo json_encode(['error' => 'Invalid ID token']);
} 