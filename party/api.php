<?php
session_start();
require_once 'party-functions.php';

// Set JSON content type
header('Content-Type: application/json');

// Check if user is authenticated
if (!isset($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['uid'];

// Get input data
$contentType = $_SERVER["CONTENT_TYPE"] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $data = json_decode(file_get_contents('php://input'), true);
} else {
    $data = $_POST;
}

$action = $data['action'] ?? '';

switch ($action) {
    case 'create':
        $name = $data['name'] ?? '';
        if (empty($name)) {
            echo json_encode(['success' => false, 'error' => 'Party name is required']);
            exit;
        }
        
        error_log("Creating party with name: " . $name . " for user: " . $userId);
        $result = createParty($name, $userId);
        error_log("Create party result: " . json_encode($result));
        echo json_encode($result);
        break;

    case 'join':
        $code = $data['code'] ?? '';
        if (empty($code)) {
            echo json_encode(['success' => false, 'error' => 'Party code is required']);
            exit;
        }
        echo json_encode(joinParty($code, $userId));
        break;

    case 'get_members':
        $partyId = $data['party_id'] ?? '';
        if (empty($partyId)) {
            echo json_encode(['success' => false, 'error' => 'Party ID is required']);
            exit;
        }
        $members = getPartyMembers($partyId);
        echo json_encode(['success' => true, 'members' => $members]);
        break;

    case 'remove_member':
        $partyId = $data['party_id'] ?? '';
        $memberId = $data['member_id'] ?? '';
        if (empty($partyId) || empty($memberId)) {
            echo json_encode(['success' => false, 'error' => 'Party ID and member ID are required']);
            exit;
        }
        echo json_encode(removeMember($partyId, $memberId, $userId));
        break;

    case 'set_game_master':
        $partyId = $data['party_id'] ?? '';
        $gmUserId = $data['gm_user_id'] ?? '';
        if (empty($partyId) || empty($gmUserId)) {
            echo json_encode(['success' => false, 'error' => 'Party ID and Game Master ID are required']);
            exit;
        }
        echo json_encode(setGameMaster($partyId, $gmUserId, $userId));
        break;

    case 'get_party':
        $party = getUserParty($userId);
        echo json_encode(['success' => true, 'party' => $party]);
        break;

    case 'leave_party':
        if (!isset($_POST['party_id'])) {
            echo json_encode(['success' => false, 'error' => 'Party ID is required']);
            exit;
        }

        try {
            // Start transaction
            $conn->beginTransaction();

            // Remove user from party_members
            $stmt = $conn->prepare("DELETE FROM party_members WHERE party_id = ? AND user_id = ?");
            $stmt->execute([$_POST['party_id'], $_SESSION['uid']]);

            // Check if party is empty
            $stmt = $conn->prepare("SELECT COUNT(*) FROM party_members WHERE party_id = ?");
            $stmt->execute([$_POST['party_id']]);
            $memberCount = $stmt->fetchColumn();

            // If no members left, delete the party
            if ($memberCount == 0) {
                $stmt = $conn->prepare("DELETE FROM parties WHERE id = ?");
                $stmt->execute([$_POST['party_id']]);
            }

            // Commit transaction
            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            // Rollback transaction on error
            $conn->rollBack();
            error_log("Error leaving party: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Failed to leave party']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
} 