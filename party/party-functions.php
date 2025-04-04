<?php
require_once __DIR__ . '/../config/db_connect.php';

/**
 * Generate a random 6-digit party code
 */
function generatePartyCode() {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    
    do {
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        // Check if code already exists
        global $conn;
        $stmt = $conn->prepare("SELECT id FROM parties WHERE code = ?");
        $stmt->execute([$code]);
    } while ($stmt->fetch());
    
    return $code;
}

/**
 * Create a new party
 */
function createParty($name, $creatorId) {
    global $conn;
    
    try {
        if (!$conn) {
            error_log("Database connection is null");
            return ['success' => false, 'error' => 'Database connection failed'];
        }

        $code = generatePartyCode();
        
        // Create party
        $stmt = $conn->prepare("INSERT INTO parties (name, code, creator_id) VALUES (?, ?, ?)");
        if (!$stmt) {
            error_log("Failed to prepare party insert statement");
            return ['success' => false, 'error' => 'Failed to prepare statement'];
        }
        
        $stmt->execute([$name, $code, $creatorId]);
        $partyId = $conn->lastInsertId();
        
        // Add creator as member
        $stmt = $conn->prepare("INSERT INTO party_members (party_id, user_id) VALUES (?, ?)");
        if (!$stmt) {
            error_log("Failed to prepare party_members insert statement");
            return ['success' => false, 'error' => 'Failed to prepare statement'];
        }
        
        $stmt->execute([$partyId, $creatorId]);
        
        return [
            'success' => true,
            'party' => [
                'id' => $partyId,
                'name' => $name,
                'code' => $code
            ]
        ];
    } catch (PDOException $e) {
        error_log("Error creating party: " . $e->getMessage());
        error_log("SQL State: " . $e->getCode());
        error_log("Stack trace: " . $e->getTraceAsString());
        return ['success' => false, 'error' => 'Failed to create party: ' . $e->getMessage()];
    }
}

/**
 * Join a party using code
 */
function joinParty($code, $userId) {
    global $conn;
    
    try {
        if (!$conn) {
            error_log("Database connection is null");
            return ['success' => false, 'error' => 'Database connection failed'];
        }

        // Get party by code
        $stmt = $conn->prepare("SELECT * FROM parties WHERE code = ?");
        $stmt->execute([$code]);
        $party = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$party) {
            return ['success' => false, 'error' => 'Invalid party code'];
        }
        
        // Check if already a member
        $stmt = $conn->prepare("SELECT id FROM party_members WHERE party_id = ? AND user_id = ?");
        $stmt->execute([$party['id'], $userId]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Already a member of this party'];
        }
        
        // Add as member
        $stmt = $conn->prepare("INSERT INTO party_members (party_id, user_id) VALUES (?, ?)");
        $stmt->execute([$party['id'], $userId]);
        
        return [
            'success' => true,
            'party' => $party
        ];
    } catch (PDOException $e) {
        error_log("Error joining party: " . $e->getMessage());
        error_log("SQL State: " . $e->getCode());
        error_log("Stack trace: " . $e->getTraceAsString());
        return ['success' => false, 'error' => 'Failed to join party: ' . $e->getMessage()];
    }
}

/**
 * Get party members
 */
function getPartyMembers($partyId) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT u.*, pm.joined_at 
            FROM party_members pm 
            JOIN users u ON pm.user_id = u.uid 
            WHERE pm.party_id = ?
            ORDER BY pm.joined_at ASC
        ");
        $stmt->execute([$partyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting party members: " . $e->getMessage());
        return [];
    }
}

/**
 * Get user's party
 */
function getUserParty($userId) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT p.*, pm.joined_at, (p.creator_id = ?) as is_creator
            FROM party_members pm 
            JOIN parties p ON pm.party_id = p.id 
            WHERE pm.user_id = ?
        ");
        $stmt->execute([$userId, $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting user party: " . $e->getMessage());
        return null;
    }
}

/**
 * Remove member from party
 */
function removeMember($partyId, $userId, $requesterId) {
    global $conn;
    
    try {
        // Check if requester is party creator
        $stmt = $conn->prepare("SELECT creator_id FROM parties WHERE id = ?");
        $stmt->execute([$partyId]);
        $party = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$party || $party['creator_id'] !== $requesterId) {
            return ['success' => false, 'error' => 'Not authorized to remove members'];
        }
        
        // Remove member
        $stmt = $conn->prepare("DELETE FROM party_members WHERE party_id = ? AND user_id = ?");
        $stmt->execute([$partyId, $userId]);
        
        return ['success' => true];
    } catch (PDOException $e) {
        error_log("Error removing party member: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to remove member'];
    }
}

/**
 * Set Game Master for a party
 */
function setGameMaster($partyId, $gmUserId, $requesterId) {
    global $conn;
    
    try {
        // Check if requester is party creator
        $stmt = $conn->prepare("SELECT creator_id FROM parties WHERE id = ?");
        $stmt->execute([$partyId]);
        $party = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$party || $party['creator_id'] !== $requesterId) {
            return ['success' => false, 'error' => 'Not authorized to set game master'];
        }
        
        // Check if gmUserId is a member of the party
        $stmt = $conn->prepare("SELECT id FROM party_members WHERE party_id = ? AND user_id = ?");
        $stmt->execute([$partyId, $gmUserId]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'error' => 'User is not a member of this party'];
        }
        
        // Update the party with the new game master
        $stmt = $conn->prepare("UPDATE parties SET game_master_id = ? WHERE id = ?");
        $stmt->execute([$gmUserId, $partyId]);
        
        return ['success' => true];
    } catch (PDOException $e) {
        error_log("Error setting game master: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to set game master'];
    }
} 