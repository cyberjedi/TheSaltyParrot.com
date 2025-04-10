<?php
/**
 * Firebase Script Loader
 * 
 * Injects Firebase configuration from PHP into JavaScript
 */

// Include Firebase configuration if not already included
if (!defined('FIREBASE_API_KEY')) {
    require_once __DIR__ . '/../config/firebase-config.php';
}
?>
<!-- Firebase Configuration Loader -->
<script type="module" src="/js/firebase-config-loader.js" 
    data-api-key="<?php echo FIREBASE_API_KEY; ?>"
    data-auth-domain="<?php echo FIREBASE_AUTH_DOMAIN; ?>"
    data-project-id="<?php echo FIREBASE_PROJECT_ID; ?>"
    data-storage-bucket="<?php echo FIREBASE_STORAGE_BUCKET; ?>"
    data-messaging-sender-id="<?php echo FIREBASE_MESSAGING_SENDER_ID; ?>"
    data-app-id="<?php echo FIREBASE_APP_ID; ?>">
</script> 