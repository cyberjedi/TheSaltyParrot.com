<?php
/**
 * Discord Webhook Service for New UI
 * 
 * Handles all Discord webhook operations for the new interface
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load dependencies with path handling
if (file_exists(__DIR__ . '/discord-config.php')) {
    require_once __DIR__ . '/discord-config.php';
} else {
    require_once 'discord/discord-config.php';
}

// Handle different directory contexts for database connection
if (file_exists(__DIR__ . '/../config/db_connect.php')) {
    require_once __DIR__ . '/../config/db_connect.php';
} else {
    require_once 'config/db_connect.php';
}

/**
 * WebhookService class for the new UI
 */
class WebhookService {
    private $conn;
    private $userId = null;
    private $discordId = null;

    /**
     * Constructor - initializes the service with database connection
     * 
     * @param PDO|null $conn Database connection
     */
    public function __construct($conn = null) {
        // Handle null connection
        if ($conn === null) {
            // Try to ensure we have the global database connection
            global $conn;
            
            // If still null, try to establish connection directly
            if ($conn === null) {
                // Try both possible include paths for the db_connect file
                if (file_exists(__DIR__ . '/../config/db_connect.php')) {
                    include_once __DIR__ . '/../config/db_connect.php';
                } elseif (file_exists(__DIR__ . '/../config/db_connect.php')) {
                    include_once __DIR__ . '/../config/db_connect.php';
                    // If only original connection is available, use it
                    if (isset($GLOBALS['conn']) && !isset($GLOBALS['conn'])) {
                        $GLOBALS['conn'] = $GLOBALS['conn'];
                    }
                }
                
                $conn = $GLOBALS['conn'] ?? null;
            } else {
                $conn = $conn;
            }
        }
        
        $this->conn = $conn;
        
        // Log connection status for debugging
        if ($this->conn === null) {
            error_log('WebhookService: Database connection is null');
        } else {
            error_log('WebhookService: Database connection established');
        }
        
        // Set Discord user ID if authenticated
        if (isset($_SESSION['discord_user']) && isset($_SESSION['discord_user']['id'])) {
            $this->discordId = $_SESSION['discord_user']['id'];
            $this->setUserId();
        }
    }

    /**
     * Set user ID from Discord ID
     * 
     * @return bool Success status
     */
    private function setUserId() {
        try {
            if (!$this->discordId) return false;
            
            // Check if we have a valid database connection
            if (!$this->conn) {
                error_log('WebhookService - No database connection available for setting user ID');
                return false;
            }
            
            $stmt = $this->conn->prepare("SELECT id FROM discord_users WHERE discord_id = :discord_id");
            $stmt->bindParam(':discord_id', $this->discordId);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $this->userId = $user['id'];
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log('WebhookService - Error getting user ID: ' . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log('WebhookService - General error getting user ID: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user is authorized for webhook
     * 
     * @param int $webhookId ID of the webhook to check
     * @return bool Whether user is authorized
     */
    public function isAuthorizedForWebhook($webhookId) {
        try {
            if (!$this->userId) return false;
            
            $stmt = $this->conn->prepare("SELECT id FROM discord_webhooks WHERE id = :id AND user_id = :user_id");
            $stmt->bindParam(':id', $webhookId);
            $stmt->bindParam(':user_id', $this->userId);
            $stmt->execute();
            
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log('WebhookService - Authorization check failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user's webhooks
     * 
     * @return array List of webhooks
     */
    public function getUserWebhooks() {
        $webhooks = [];
        
        try {
            if (!$this->userId) return $webhooks;
            
            // Check if we have a valid database connection
            if (!$this->conn) {
                error_log('WebhookService - No database connection available for getting webhooks');
                return $webhooks;
            }
            
            $stmt = $this->conn->prepare("SELECT * FROM discord_webhooks 
                                         WHERE user_id = :user_id AND is_active = 1 
                                         ORDER BY is_default DESC, last_updated DESC");
            $stmt->bindParam(':user_id', $this->userId);
            $stmt->execute();
            $webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('WebhookService - Error fetching webhooks: ' . $e->getMessage());
        } catch (Exception $e) {
            error_log('WebhookService - General error fetching webhooks: ' . $e->getMessage());
        }
        
        return $webhooks;
    }

    /**
     * Get default webhook for user
     * 
     * @return array|null Webhook data or null if none found
     */
    public function getDefaultWebhook() {
        try {
            if (!$this->userId) return null;
            
            // Check if we have a valid database connection
            if (!$this->conn) {
                error_log('WebhookService - No database connection available for getting default webhook');
                return null;
            }
            
            // First try to get default webhook
            $stmt = $this->conn->prepare("SELECT id, webhook_name, channel_name, is_default FROM discord_webhooks 
                                        WHERE user_id = :user_id AND is_active = 1 AND is_default = 1 
                                        LIMIT 1");
            $stmt->bindParam(':user_id', $this->userId);
            $stmt->execute();
            $webhook = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If no default webhook is set, get the most recently updated one
            if (!$webhook) {
                $stmt = $this->conn->prepare("SELECT id, webhook_name, channel_name, is_default FROM discord_webhooks 
                                           WHERE user_id = :user_id AND is_active = 1 
                                           ORDER BY last_updated DESC LIMIT 1");
                $stmt->bindParam(':user_id', $this->userId);
                $stmt->execute();
                $webhook = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            return $webhook ?: null;
        } catch (PDOException $e) {
            error_log('WebhookService - Error getting default webhook: ' . $e->getMessage());
            return null;
        } catch (Exception $e) {
            error_log('WebhookService - General error getting default webhook: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get webhook by ID
     * 
     * @param int $webhookId The webhook ID
     * @return array|false Webhook data or false if not found
     */
    public function getWebhookById($webhookId) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM discord_webhooks WHERE id = :id");
            $stmt->bindParam(':id', $webhookId);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('WebhookService - Error getting webhook: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Add a new webhook
     * 
     * @param string $webhookUrl The Discord webhook URL
     * @param string $webhookName Custom name for the webhook
     * @param string $channelName Name of the Discord channel
     * @param string $description Optional description
     * @return array Response with status and message
     */
    public function addWebhook($webhookUrl, $webhookName, $channelName, $description = '') {
        try {
            if (!$this->userId) {
                return [
                    'status' => 'error',
                    'message' => 'Not authenticated with Discord'
                ];
            }
            
            // Validate the webhook URL format
            if (!preg_match('#https?://(?:canary\.|ptb\.)?discord(?:app)?\.com/api/webhooks/(\d+)/([a-zA-Z0-9_-]+)#', $webhookUrl, $matches)) {
                return [
                    'status' => 'error',
                    'message' => 'Invalid Discord webhook URL format'
                ];
            }
            
            $webhookId = $matches[1];
            $webhookToken = $matches[2];
            
            // Verify the webhook by making a GET request to Discord
            $url = "https://discord.com/api/webhooks/{$webhookId}/{$webhookToken}";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                return [
                    'status' => 'error',
                    'message' => 'Invalid webhook URL or webhook no longer exists'
                ];
            }
            
            $webhookData = json_decode($response, true);
            
            // Extract guild ID and channel ID if available
            $guildId = $webhookData['guild_id'] ?? '';
            $channelId = $webhookData['channel_id'] ?? '';
            
            // Generate a sharing code
            $sharingCode = substr(md5($webhookId . $webhookToken . time()), 0, 10);
            
            // Insert webhook into database
            $stmt = $this->conn->prepare("INSERT INTO discord_webhooks 
                (user_id, server_id, channel_id, channel_name, webhook_id, webhook_token, webhook_name, webhook_description, 
                 sharing_code, is_shared, is_active, created_at, last_updated) 
                VALUES 
                (:user_id, :server_id, :channel_id, :channel_name, :webhook_id, :webhook_token, :webhook_name, :webhook_description,
                 :sharing_code, 0, 1, NOW(), NOW())");
                
            $stmt->bindParam(':user_id', $this->userId);
            $stmt->bindParam(':server_id', $guildId);
            $stmt->bindParam(':channel_id', $channelId);
            $stmt->bindParam(':channel_name', $channelName);
            $stmt->bindParam(':webhook_id', $webhookId);
            $stmt->bindParam(':webhook_token', $webhookToken);
            $stmt->bindParam(':webhook_name', $webhookName);
            $stmt->bindParam(':webhook_description', $description);
            $stmt->bindParam(':sharing_code', $sharingCode);
            
            $stmt->execute();
            
            // Check if this is the user's first webhook and make it default
            $this->ensureDefaultWebhook();
            
            return [
                'status' => 'success',
                'message' => 'Webhook added successfully',
                'webhook_id' => $this->conn->lastInsertId()
            ];
        } catch (PDOException $e) {
            error_log('WebhookService - Error adding webhook: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Error saving webhook: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Import a shared webhook
     * 
     * @param string $sharingCode Code to identify the shared webhook
     * @return array Response with status and message
     */
    public function importSharedWebhook($sharingCode) {
        try {
            if (!$this->userId) {
                return [
                    'status' => 'error',
                    'message' => 'Not authenticated with Discord'
                ];
            }
            
            // Look up the webhook by sharing code
            $stmt = $this->conn->prepare("SELECT * FROM discord_webhooks WHERE sharing_code = :sharing_code");
            $stmt->bindParam(':sharing_code', $sharingCode);
            $stmt->execute();
            $sharedWebhook = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$sharedWebhook) {
                return [
                    'status' => 'error',
                    'message' => 'Invalid sharing code. The webhook could not be found.'
                ];
            }
            
            // Check if user already has this webhook
            $webhookId = $sharedWebhook['webhook_id'];
            $stmt = $this->conn->prepare("SELECT id FROM discord_webhooks WHERE user_id = :user_id AND webhook_id = :webhook_id");
            $stmt->bindParam(':user_id', $this->userId);
            $stmt->bindParam(':webhook_id', $webhookId);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return [
                    'status' => 'error',
                    'message' => 'You already have this webhook added to your account'
                ];
            }
            
            // Verify the webhook still exists
            $webhookToken = $sharedWebhook['webhook_token'];
            $url = "https://discord.com/api/webhooks/{$webhookId}/{$webhookToken}";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                return [
                    'status' => 'error',
                    'message' => 'This webhook no longer exists or is invalid'
                ];
            }
            
            // Clone the webhook for this user
            $stmt = $this->conn->prepare("INSERT INTO discord_webhooks 
                (user_id, server_id, channel_id, channel_name, webhook_id, webhook_token, webhook_name, webhook_description,
                 sharing_code, is_shared, is_active, created_at, last_updated) 
                VALUES 
                (:user_id, :server_id, :channel_id, :channel_name, :webhook_id, :webhook_token, :webhook_name, :webhook_description,
                 :sharing_code, 1, 1, NOW(), NOW())");
                
            $webhookName = $sharedWebhook['webhook_name'] . ' (Shared)';
            
            $stmt->bindParam(':user_id', $this->userId);
            $stmt->bindParam(':server_id', $sharedWebhook['server_id']);
            $stmt->bindParam(':channel_id', $sharedWebhook['channel_id']);
            $stmt->bindParam(':channel_name', $sharedWebhook['channel_name']);
            $stmt->bindParam(':webhook_id', $webhookId);
            $stmt->bindParam(':webhook_token', $webhookToken);
            $stmt->bindParam(':webhook_name', $webhookName);
            $stmt->bindParam(':webhook_description', $sharedWebhook['webhook_description']);
            $stmt->bindParam(':sharing_code', $sharingCode);
            
            $stmt->execute();
            
            // Check if this is the user's first webhook and make it default
            $this->ensureDefaultWebhook();
            
            return [
                'status' => 'success',
                'message' => 'Shared webhook imported successfully',
                'webhook_id' => $this->conn->lastInsertId()
            ];
        } catch (PDOException $e) {
            error_log('WebhookService - Error importing webhook: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Error importing webhook: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update a webhook
     * 
     * @param int $webhookId ID of the webhook to update
     * @param string $webhookName New name for the webhook
     * @param string $channelName New channel name
     * @param string $description New description
     * @return array Response with status and message
     */
    public function updateWebhook($webhookId, $webhookName, $channelName, $description = '') {
        try {
            if (!$this->userId) {
                return [
                    'status' => 'error',
                    'message' => 'Not authenticated with Discord'
                ];
            }
            
            // Check ownership
            if (!$this->isAuthorizedForWebhook($webhookId)) {
                return [
                    'status' => 'error',
                    'message' => 'You do not have permission to edit this webhook'
                ];
            }
            
            // Update webhook details
            $stmt = $this->conn->prepare("UPDATE discord_webhooks SET 
                webhook_name = :webhook_name, 
                webhook_description = :webhook_description, 
                channel_name = :channel_name,
                last_updated = NOW() 
                WHERE id = :id AND user_id = :user_id");
                
            $stmt->bindParam(':webhook_name', $webhookName);
            $stmt->bindParam(':webhook_description', $description);
            $stmt->bindParam(':channel_name', $channelName);
            $stmt->bindParam(':id', $webhookId);
            $stmt->bindParam(':user_id', $this->userId);
            
            $stmt->execute();
            
            return [
                'status' => 'success',
                'message' => 'Webhook updated successfully'
            ];
        } catch (PDOException $e) {
            error_log('WebhookService - Error updating webhook: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Error updating webhook: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete a webhook
     * 
     * @param int $webhookId ID of the webhook to delete
     * @return array Response with status and message
     */
    public function deleteWebhook($webhookId) {
        try {
            if (!$this->userId) {
                return [
                    'status' => 'error',
                    'message' => 'Not authenticated with Discord'
                ];
            }
            
            // Check ownership
            if (!$this->isAuthorizedForWebhook($webhookId)) {
                return [
                    'status' => 'error',
                    'message' => 'You do not have permission to delete this webhook'
                ];
            }
            
            // Get webhook details first
            $webhook = $this->getWebhookById($webhookId);
            if (!$webhook) {
                return [
                    'status' => 'error',
                    'message' => 'Webhook not found'
                ];
            }
            
            // Delete from Discord if not shared
            if (!$webhook['is_shared']) {
                $url = "https://discord.com/api/webhooks/{$webhook['webhook_id']}/{$webhook['webhook_token']}";
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                
                curl_exec($ch);
                curl_close($ch);
            }
            
            // Delete from database
            $stmt = $this->conn->prepare("DELETE FROM discord_webhooks WHERE id = :id AND user_id = :user_id");
            $stmt->bindParam(':id', $webhookId);
            $stmt->bindParam(':user_id', $this->userId);
            $stmt->execute();
            
            // Ensure another webhook is set as default if we deleted the default
            if ($webhook['is_default']) {
                $this->ensureDefaultWebhook();
            }
            
            return [
                'status' => 'success',
                'message' => 'Webhook deleted successfully'
            ];
        } catch (PDOException $e) {
            error_log('WebhookService - Error deleting webhook: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Error deleting webhook: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Set a webhook as default
     * 
     * @param int $webhookId ID of the webhook to set as default
     * @return array Response with status and message
     */
    public function setDefaultWebhook($webhookId) {
        try {
            if (!$this->userId) {
                return [
                    'status' => 'error',
                    'message' => 'Not authenticated with Discord'
                ];
            }
            
            // Check ownership
            if (!$this->isAuthorizedForWebhook($webhookId)) {
                return [
                    'status' => 'error',
                    'message' => 'You do not have permission to modify this webhook'
                ];
            }
            
            // First, unset all defaults for this user
            $stmt = $this->conn->prepare("UPDATE discord_webhooks SET is_default = 0 WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $this->userId);
            $stmt->execute();
            
            // Then set the selected webhook as default
            $stmt = $this->conn->prepare("UPDATE discord_webhooks SET is_default = 1 WHERE id = :id AND user_id = :user_id");
            $stmt->bindParam(':id', $webhookId);
            $stmt->bindParam(':user_id', $this->userId);
            $stmt->execute();
            
            // Get the updated webhook details and update the session
            $webhookStmt = $this->conn->prepare("SELECT id, webhook_name, channel_name, is_default, is_active, server_id 
                                                FROM discord_webhooks 
                                                WHERE id = :id");
            $webhookStmt->bindParam(':id', $webhookId);
            $webhookStmt->execute();
            $webhook = $webhookStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($webhook) {
                $_SESSION['active_webhook'] = $webhook;
            }
            
            return [
                'status' => 'success',
                'message' => 'Default webhook updated successfully'
            ];
        } catch (PDOException $e) {
            error_log('WebhookService - Error setting default webhook: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Error setting default webhook: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Ensure a user has a default webhook
     * Sets the first webhook as default if no default exists
     * 
     * @return bool Success status
     */
    private function ensureDefaultWebhook() {
        try {
            // Check if user has any webhooks with default already set
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM discord_webhooks 
                                        WHERE user_id = :user_id AND is_default = 1");
            $stmt->bindParam(':user_id', $this->userId);
            $stmt->execute();
            $hasDefault = ($stmt->fetchColumn() > 0);
            
            if (!$hasDefault) {
                // Get the first webhook and set it as default
                $stmt = $this->conn->prepare("SELECT id FROM discord_webhooks 
                                            WHERE user_id = :user_id 
                                            ORDER BY created_at ASC 
                                            LIMIT 1");
                $stmt->bindParam(':user_id', $this->userId);
                $stmt->execute();
                $webhook = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($webhook) {
                    $stmt = $this->conn->prepare("UPDATE discord_webhooks 
                                                SET is_default = 1 
                                                WHERE id = :id AND user_id = :user_id");
                    $stmt->bindParam(':id', $webhook['id']);
                    $stmt->bindParam(':user_id', $this->userId);
                    $stmt->execute();
                    return true;
                }
            }
            
            return false;
        } catch (PDOException $e) {
            error_log('WebhookService - Error ensuring default webhook: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send a test message to a webhook
     * 
     * @param int $webhookId ID of the webhook to test
     * @return array Response with status and message
     */
    public function sendTestMessage($webhookId) {
        try {
            if (!$this->userId) {
                return [
                    'status' => 'error',
                    'message' => 'Not authenticated with Discord'
                ];
            }
            
            // Check ownership
            if (!$this->isAuthorizedForWebhook($webhookId)) {
                return [
                    'status' => 'error',
                    'message' => 'You do not have permission to use this webhook'
                ];
            }
            
            // Get webhook details
            $webhook = $this->getWebhookById($webhookId);
            if (!$webhook) {
                return [
                    'status' => 'error',
                    'message' => 'Webhook not found'
                ];
            }
            
            // Create test message
            $message = [
                'content' => null,
                'embeds' => [
                    [
                        'title' => '🧪 Test Message from The Salty Parrot (New UI)',
                        'description' => 'This is a test message to verify your webhook is working correctly. You can now send generated content from The Salty Parrot to this Discord channel!',
                        'color' => 0xbf9d61, // Gold color
                        'footer' => [
                            'text' => 'The Salty Parrot - A Pirate Borg Toolbox'
                        ],
                        'timestamp' => date('c')
                    ]
                ]
            ];
            
            // Send message to webhook
            $url = "https://discord.com/api/webhooks/{$webhook['webhook_id']}/{$webhook['webhook_token']}";
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // Log webhook usage
            $this->logWebhookUsage($webhookId, 'test', 'Test message', $httpCode, 
                                  ($httpCode >= 200 && $httpCode < 300) ? 1 : 0,
                                  ($httpCode >= 200 && $httpCode < 300) ? null : $response);
            
            // Check response status
            if ($httpCode >= 200 && $httpCode < 300) {
                return [
                    'status' => 'success',
                    'message' => 'Test message sent successfully'
                ];
            } else {
                // Try to parse the error
                $errorData = json_decode($response, true);
                $errorMessage = isset($errorData['message']) ? $errorData['message'] : 'Unknown error';
                
                return [
                    'status' => 'error',
                    'message' => 'Discord API error: ' . $errorMessage
                ];
            }
        } catch (Exception $e) {
            error_log('WebhookService - Error sending test message: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Error sending test message: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Log webhook usage
     * 
     * @param int $webhookId Webhook ID
     * @param string $generatorType Generator type
     * @param string $contentSummary Content summary
     * @param int $statusCode HTTP status code
     * @param int $isSuccess Success status (1/0)
     * @param string $errorMessage Optional error message
     * @return bool Success status
     */
    private function logWebhookUsage($webhookId, $generatorType, $contentSummary, $statusCode, $isSuccess, $errorMessage = null) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO discord_webhook_logs 
                (webhook_id, user_id, generator_type, content_summary, status_code, is_success, error_message, request_timestamp, response_timestamp) 
                VALUES 
                (:webhook_id, :user_id, :generator_type, :content_summary, :status_code, :is_success, :error_message, NOW(), NOW())");
                
            $stmt->bindParam(':webhook_id', $webhookId);
            $stmt->bindParam(':user_id', $this->userId);
            $stmt->bindParam(':generator_type', $generatorType);
            $stmt->bindParam(':content_summary', $contentSummary);
            $stmt->bindParam(':status_code', $statusCode);
            $stmt->bindParam(':is_success', $isSuccess);
            $stmt->bindParam(':error_message', $errorMessage);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('WebhookService - Error logging webhook usage: ' . $e->getMessage());
            return false;
        }
    }
}

/**
 * Helper function to create a webhook service instance
 * 
 * @return WebhookService
 */
function createWebhookService() {
    global $conn, $conn;
    
    // Try to use new connection first
    if (isset($conn) && $conn !== null) {
        // Good, we have our new connection
        return new WebhookService($conn);
    }
    
    // Fall back to original connection if available
    if (isset($conn) && $conn !== null) {
        error_log('WebhookService: Using original database connection');
        return new WebhookService($conn);
    }
    
    // If no connection is available, try to establish one using original db_connect
    // This is more likely to work on the production server
    if (!isset($conn) && file_exists(__DIR__ . '/../config/db_connect.php')) {
        error_log('WebhookService: Including original db_connect.php');
        include_once __DIR__ . '/../config/db_connect.php';
        
        if (isset($conn) && $conn !== null) {
            error_log('WebhookService: Successfully got connection from original db_connect.php');
            return new WebhookService($conn);
        }
    }
    
    // Last resort - let the constructor try to establish a connection
    error_log('WebhookService: No existing connection found, creating new one');
    return new WebhookService();
}
?>