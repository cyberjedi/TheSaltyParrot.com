<?php
/**
 * Redirect from old character_sheet.php to new sheets.php
 * 
 * This file maintains backward compatibility by redirecting users 
 * from the old character sheet page to the new character sheets page.
 */

// Start the session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// If there's a specific character ID, we could potentially redirect to a specific sheet
// in the future when we implement direct sheet viewing
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    // For now, just redirect to the main sheets page
    // In the future, this could be: header("Location: sheets.php?sheet=$id");
}

// Redirect to the new sheets page
header("Location: sheets.php");
exit;
