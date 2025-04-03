<?php
/**
 * Firebase Configuration
 * 
 * Contains Firebase configuration and authentication settings
 */

// Firebase configuration
define('FIREBASE_API_KEY', 'AIzaSyDzSPll8gZKWBhmD6o-QAAnT89TWucFkr0');
define('FIREBASE_AUTH_DOMAIN', 'salty-parrot.firebaseapp.com');
define('FIREBASE_PROJECT_ID', 'salty-parrot');
define('FIREBASE_STORAGE_BUCKET', 'salty-parrot.firebasestorage.app');
define('FIREBASE_MESSAGING_SENDER_ID', '598113689428');
define('FIREBASE_APP_ID', '1:598113689428:web:fb57b75af8efc6e051f2c1');

/**
 * Check if user is authenticated with Firebase
 * 
 * @return bool True if authenticated
 */
function is_firebase_authenticated() {
    return isset($_SESSION['uid']) && 
           isset($_SESSION['firebase_token']);
}

/**
 * Get Firebase user information
 * 
 * @return array|null User data or null if not authenticated
 */
function get_firebase_user() {
    if (!is_firebase_authenticated()) {
        return null;
    }
    
    return [
        'uid' => $_SESSION['uid'],
        'email' => $_SESSION['email'],
        'displayName' => $_SESSION['displayName'],
        'photoURL' => $_SESSION['photoURL']
    ];
}

/**
 * Get Firebase user ID
 * 
 * @return string|null User ID or null if not authenticated
 */
function get_firebase_user_id() {
    return is_firebase_authenticated() ? $_SESSION['uid'] : null;
}

/**
 * Get Firebase user email
 * 
 * @return string|null User email or null if not authenticated
 */
function get_firebase_user_email() {
    return is_firebase_authenticated() ? $_SESSION['email'] : null;
}

/**
 * Get Firebase user display name
 * 
 * @return string|null User display name or null if not authenticated
 */
function get_firebase_user_display_name() {
    return is_firebase_authenticated() ? $_SESSION['displayName'] : null;
}

/**
 * Get Firebase user photo URL
 * 
 * @return string|null User photo URL or null if not authenticated
 */
function get_firebase_user_photo_url() {
    return is_firebase_authenticated() ? $_SESSION['photoURL'] : null;
} 