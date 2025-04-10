<?php
/**
 * Temporary debugging script
 * Delete after confirming Firebase constants are correct
 */

require_once 'firebase-config.php';

// Output Firebase constants
echo "<pre>";
echo "FIREBASE_API_KEY: " . (defined('FIREBASE_API_KEY') ? substr(FIREBASE_API_KEY, 0, 5) . "..." : "NOT DEFINED") . "\n";
echo "FIREBASE_AUTH_DOMAIN: " . (defined('FIREBASE_AUTH_DOMAIN') ? FIREBASE_AUTH_DOMAIN : "NOT DEFINED") . "\n";
echo "FIREBASE_PROJECT_ID: " . (defined('FIREBASE_PROJECT_ID') ? FIREBASE_PROJECT_ID : "NOT DEFINED") . "\n";
echo "</pre>";
?> 