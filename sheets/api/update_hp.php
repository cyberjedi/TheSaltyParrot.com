<?php
/**
 * API: Update Current Hit Points
 *
 * Updates the current HP for a specific character sheet.
 */

// Start the session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set JSON content type
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['sheet_id']) || !isset($input['change'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters (sheet_id, change)']);
    exit;
}

$sheet_id = filter_var($input['sheet_id'], FILTER_VALIDATE_INT);
$change = filter_var($input['change'], FILTER_VALIDATE_INT); // Expecting +1 or -1
$user_id = $_SESSION['uid'];

if ($sheet_id === false || $change === false || ($change !== 1 && $change !== -1)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input parameters']);
    exit;
}

try {
    // Include database connection
    require_once '../../config/db_connect.php';

    if (!isset($conn) || $conn === null) {
        throw new Exception('Database connection failed');
    }

    // Use a transaction to ensure data consistency
    $conn->beginTransaction();

    // 1. Get the current and max HP, ensuring the sheet belongs to the user
    $stmt = $conn->prepare("SELECT cs.id, pbs.hp_current, pbs.hp_max
                            FROM character_sheets cs
                            JOIN pirate_borg_sheets pbs ON cs.id = pbs.sheet_id
                            WHERE cs.id = ? AND cs.user_id = ?");
    $stmt->execute([$sheet_id, $user_id]);
    $sheet_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sheet_data) {
        $conn->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'Sheet not found or access denied']);
        exit;
    }

    $current_hp = (int)$sheet_data['hp_current'];
    $max_hp = (int)$sheet_data['hp_max'];

    // 2. Calculate the new HP, ensuring it doesn't go below 0 or above max_hp
    $new_hp = $current_hp + $change;
    $new_hp = max(0, $new_hp); // Ensure HP doesn't go below 0
    $new_hp = min($max_hp, $new_hp); // Ensure HP doesn't exceed max_hp

    // 3. Update the database if the value actually changed
    if ($new_hp !== $current_hp) {
        $updateStmt = $conn->prepare("UPDATE pirate_borg_sheets SET hp_current = ? WHERE sheet_id = ?");
        $updateSuccess = $updateStmt->execute([$new_hp, $sheet_id]);

        if (!$updateSuccess) {
            throw new Exception('Failed to update HP');
        }

        // Also update the main sheet's updated_at timestamp
        $updateTimestampStmt = $conn->prepare("UPDATE character_sheets SET updated_at = NOW() WHERE id = ?");
        $updateTimestampStmt->execute([$sheet_id]);

    } else {
        // If HP didn't change (e.g., trying to heal past max), still report success but return the existing value.
        $new_hp = $current_hp;
    }


    // Commit the transaction
    $conn->commit();

    // Return the updated (or current if unchanged) HP value
    echo json_encode([
        'success' => true,
        'hp_current' => $new_hp,
        'hp_max' => $max_hp // Also return max HP for context if needed
    ]);

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    error_log("Database error updating HP: " . $e->getMessage()); // Log the detailed error
    echo json_encode(['error' => 'Database error processing request.']);
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    error_log("General error updating HP: " . $e->getMessage()); // Log the detailed error
    echo json_encode(['error' => 'An internal error occurred: ' . $e->getMessage()]);
}
?> 