<?php
session_start();
require_once 'party-functions.php';

// Set JSON content type
header('Content-Type: application/json');

// Check if user is authenticated
if (!isset($_SESSION['firebase_token'])) {
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
        echo json_encode(createParty($name, $userId));
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

    case 'get_party':
        $party = getUserParty($userId);
        echo json_encode(['success' => true, 'party' => $party]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
} 