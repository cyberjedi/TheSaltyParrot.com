<?php
// Show all PHP errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once 'config/db_connect_new.php';

// Start session if not already started
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

echo "<h1>Webhook Debug Tool</h1>";

// Check connection
if (!isset($conn_new) || $conn_new === null) {
    echo "<p style='color:red'>Database connection failed!</p>";
    exit;
}

// Show table structure
echo "<h2>Discord Webhooks Table Structure</h2>";
try {
    $stmt = $conn_new->query("DESCRIBE discord_webhooks");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        foreach ($column as $key => $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} catch (PDOException $e) {
    echo "<p style='color:red'>Error getting table structure: " . $e->getMessage() . "</p>";
}

// Show available webhooks
echo "<h2>Available Webhooks</h2>";
try {
    $stmt = $conn_new->query("SELECT * FROM discord_webhooks LIMIT 10");
    $webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($webhooks)) {
        echo "<p>No webhooks found in database.</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";
        foreach (array_keys($webhooks[0]) as $header) {
            echo "<th>" . htmlspecialchars($header) . "</th>";
        }
        echo "</tr>";
        
        foreach ($webhooks as $webhook) {
            echo "<tr>";
            foreach ($webhook as $key => $value) {
                // Truncate long values (like tokens) for display
                if ($key === 'webhook_token' && $value) {
                    $value = substr($value, 0, 10) . '...';
                }
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>Error fetching webhooks: " . $e->getMessage() . "</p>";
}

// Debug discord session data
echo "<h2>Discord Session Data</h2>";
if (isset($_SESSION['discord_user'])) {
    echo "<pre>" . htmlspecialchars(print_r($_SESSION['discord_user'], true)) . "</pre>";
} else {
    echo "<p>No Discord user data in session</p>";
}

// Test the get_default_webhook_new function directly
echo "<h2>Testing get_default_webhook_new()</h2>";
if (function_exists('get_default_webhook_new')) {
    require_once 'discord/discord_service_new.php';
    $webhook = get_default_webhook_new();
    
    if ($webhook) {
        echo "<p style='color:green'>Default webhook found:</p>";
        echo "<pre>" . htmlspecialchars(print_r($webhook, true)) . "</pre>";
    } else {
        echo "<p style='color:red'>No default webhook found using get_default_webhook_new()</p>";
        
        // Try a more direct query
        echo "<h3>Trying direct query</h3>";
        try {
            $discord_id = $_SESSION['discord_user']['id'] ?? null;
            if ($discord_id) {
                // First get user ID
                $userStmt = $conn_new->prepare("SELECT id FROM discord_users WHERE discord_id = :discord_id");
                $userStmt->bindParam(':discord_id', $discord_id);
                $userStmt->execute();
                $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($userData && isset($userData['id'])) {
                    $user_id = $userData['id'];
                    echo "<p>Found user ID: " . htmlspecialchars($user_id) . "</p>";
                    
                    // Get webhook
                    $stmt = $conn_new->prepare("SELECT * FROM discord_webhooks WHERE user_id = :user_id AND is_active = 1 ORDER BY is_default DESC, last_updated DESC LIMIT 1");
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    $webhook = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($webhook) {
                        echo "<p style='color:green'>Found webhook using direct query:</p>";
                        echo "<pre>" . htmlspecialchars(print_r($webhook, true)) . "</pre>";
                    } else {
                        echo "<p style='color:red'>No webhooks found for user ID: " . htmlspecialchars($user_id) . "</p>";
                    }
                } else {
                    echo "<p style='color:red'>User not found in database for Discord ID: " . htmlspecialchars($discord_id) . "</p>";
                }
            } else {
                echo "<p style='color:red'>No Discord ID available in session</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color:red'>Error in direct query: " . $e->getMessage() . "</p>";
        }
    }
} else {
    echo "<p style='color:red'>Function get_default_webhook_new() not available</p>";
}
?>