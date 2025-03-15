<?php
// File: discord/webhook_service.php
// Centralized service for handling all Discord webhook operations

require_once 'discord-config.php';
require_once '../config/db_connect.php';

/**
 * WebhookService class - Handles all Discord webhook operations
 */
class WebhookService {
    private $conn;
    private $userId = null;
    private $discordId = null;

    /**
     * Constructor - initializes the service with database connection
     * 
     * @param PDO $conn Database connection
     */
    public function __construct($conn) {
        $this->conn = $conn;
        
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
            
            $stmt = $this->conn->prepare("SELECT * FROM discord_webhooks 
                                         WHERE user_id = :user_id AND is_active = 1 
                                         ORDER BY is_default DESC, last_updated DESC");
            $stmt->bindParam(':user_id', $this->userId);
            $stmt->execute();
            $webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('WebhookService - Error fetching webhooks: ' . $e->getMessage());
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
     * Send simple message to webhook
     * 
     * @param int $webhookId The webhook ID
     * @param string $message Plain text message to send
     * @param string $source Source/generator type for logging
     * @return array Response with status and message
     */
    public function sendMessage($webhookId, $message, $source = 'custom') {
        try {
            // Validate permissions
            if (!$this->isAuthorizedForWebhook($webhookId)) {
                return [
                    'status' => 'error',
                    'message' => 'Unauthorized to use this webhook'
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
            
            // Prepare webhook payload
            $payload = [
                'content' => $message
            ];
            
            // Send to Discord
            return $this->sendToDiscord($webhook, $payload, $source, $message);
        } catch (Exception $e) {
            error_log('WebhookService - Error sending message: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Error sending message: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send embed to webhook
     * 
     * @param int $webhookId The webhook ID
     * @param array $embed Embed data (title, description, color, etc.)
     * @param string $source Source/generator type for logging
     * @return array Response with status and message
     */
    public function sendEmbed($webhookId, $embed, $source = 'custom') {
        try {
            // Validate permissions
            if (!$this->isAuthorizedForWebhook($webhookId)) {
                return [
                    'status' => 'error',
                    'message' => 'Unauthorized to use this webhook'
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
            
            // Ensure embed has required fields
            if (!isset($embed['title'])) {
                $embed['title'] = '🏴‍☠️ The Salty Parrot';
            }
            
            if (!isset($embed['color'])) {
                $embed['color'] = 0xbf9d61; // Gold color
            }
            
            // Prepare webhook payload
            $payload = [
                'embeds' => [$embed]
            ];
            
            // Create summary for logging
            $summary = isset($embed['title']) ? $embed['title'] : 'Embed message';
            
            // Send to Discord
            return $this->sendToDiscord($webhook, $payload, $source, $summary);
        } catch (Exception $e) {
            error_log('WebhookService - Error sending embed: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Error sending embed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send formatted content (HTML) to webhook
     * 
     * @param int $webhookId The webhook ID
     * @param string $content HTML content to send
     * @param string $source Source/generator type for logging
     * @param string $characterImage Optional character image URL
     * @return array Response with status and message
     */
    public function sendFormattedContent($webhookId, $content, $source = 'generator', $characterImage = null) {
        try {
            // Validate permissions
            if (!$this->isAuthorizedForWebhook($webhookId)) {
                return [
                    'status' => 'error',
                    'message' => 'Unauthorized to use this webhook'
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
            
            // Process the HTML content to extract relevant parts for Discord
            $embeds = [];
            $contentSummary = '';
            
            // Different formatting based on source/generator type
            switch ($source) {
                case 'ship':
                    list($embeds, $contentSummary) = $this->formatShipContent($content);
                    break;
                    
                case 'loot':
                    list($embeds, $contentSummary) = $this->formatLootContent($content);
                    break;
                
                case 'attribute_roll':
                    list($embeds, $contentSummary) = $this->formatAttributeRollContent($content, $characterImage);
                    break;
                    
                case 'item_use':
                    list($embeds, $contentSummary) = $this->formatItemUseContent($content, $characterImage);
                    break;
                    
                default:
                    // Generic handling for other generator types
                    $embeds[] = [
                        'title' => '🏴‍☠️ The Salty Parrot - Generated Content',
                        'description' => 'Content generated with The Salty Parrot. Visit the app to see the full details!',
                        'color' => 0xbf9d61
                    ];
                    
                    $contentSummary = "Content from " . ucfirst($source) . " generator";
            }
            
            // Prepare webhook message
            $payload = [
                'content' => null,
                'embeds' => $embeds
            ];
            
            // Send to Discord
            return $this->sendToDiscord($webhook, $payload, $source, $contentSummary);
        } catch (Exception $e) {
            error_log('WebhookService - Error sending formatted content: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Error sending content: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Send custom payload to webhook
     * 
     * @param int $webhookId The webhook ID
     * @param array $payload Complete webhook payload
     * @param string $source Source/generator type for logging
     * @param string $summary Summary for logging
     * @return array Response with status and message
     */
    public function sendCustomPayload($webhookId, $payload, $source = 'custom', $summary = 'Custom payload') {
        try {
            // Validate permissions
            if (!$this->isAuthorizedForWebhook($webhookId)) {
                return [
                    'status' => 'error',
                    'message' => 'Unauthorized to use this webhook'
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
            
            // Send to Discord
            return $this->sendToDiscord($webhook, $payload, $source, $summary);
        } catch (Exception $e) {
            error_log('WebhookService - Error sending custom payload: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Error sending payload: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Send payload to Discord
     * 
     * @param array $webhook Webhook data from database
     * @param array $payload Discord webhook payload
     * @param string $source Source/generator type for logging
     * @param string $summary Summary for logging
     * @return array Response with status and message
     */
    private function sendToDiscord($webhook, $payload, $source, $summary) {
        try {
            // Send message to webhook
            $url = "https://discord.com/api/webhooks/{$webhook['webhook_id']}/{$webhook['webhook_token']}";
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $responseText = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // Log webhook usage
            $this->logWebhookUsage(
                $webhook['id'], 
                $this->userId, 
                $source, 
                $summary, 
                $httpCode, 
                ($httpCode >= 200 && $httpCode < 300) ? 1 : 0,
                ($httpCode >= 200 && $httpCode < 300) ? null : $responseText
            );
            
            // Check response status
            if ($httpCode >= 200 && $httpCode < 300) {
                return [
                    'status' => 'success',
                    'message' => 'Content sent to Discord successfully'
                ];
            } else {
                // Log error message
                $errorData = json_decode($responseText, true);
                $errorMessage = isset($errorData['message']) ? $errorData['message'] : 'Unknown error';
                
                return [
                    'status' => 'error',
                    'message' => 'Discord API error: ' . $errorMessage
                ];
            }
        } catch (Exception $e) {
            error_log('WebhookService - Discord send error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Error sending to Discord: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Log webhook usage
     * 
     * @param int $webhookId Webhook ID
     * @param int $userId User ID
     * @param string $generatorType Generator type
     * @param string $contentSummary Content summary
     * @param int $statusCode HTTP status code
     * @param int $isSuccess Success status (1/0)
     * @param string $errorMessage Optional error message
     * @return bool Success status
     */
    private function logWebhookUsage($webhookId, $userId, $generatorType, $contentSummary, $statusCode, $isSuccess, $errorMessage = null) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO discord_webhook_logs 
                (webhook_id, user_id, generator_type, content_summary, status_code, is_success, error_message, request_timestamp, response_timestamp) 
                VALUES 
                (:webhook_id, :user_id, :generator_type, :content_summary, :status_code, :is_success, :error_message, NOW(), NOW())");
                
            $stmt->bindParam(':webhook_id', $webhookId);
            $stmt->bindParam(':user_id', $userId);
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
    
    /**
     * Format ship content for Discord
     * 
     * @param string $content HTML content
     * @return array Array containing [embeds, contentSummary]
     */
    private function formatShipContent($content) {
        $embeds = [];
        
        // Extract ship name and details
        preg_match('/<h2 id="ship-name">(.*?)<\/h2>/i', $content, $shipNameMatches);
        $shipName = isset($shipNameMatches[1]) ? strip_tags($shipNameMatches[1]) : 'Ship';
        
        // Extract ship details
        preg_match('/<div class="ship-details">(.*?)<\/div>/is', $content, $detailsMatches);
        $details = isset($detailsMatches[1]) ? $detailsMatches[1] : '';
        
        // Clean up HTML
        $details = strip_tags(str_replace(['<h3>', '</h3>', '<strong>', '</strong>', '<br>'], ["\n\n**", "**\n", '**', '**', "\n"], $details));
        
        // Extract cargo items
        preg_match('/<ul id="cargo-list">(.*?)<\/ul>/is', $content, $cargoMatches);
        $cargo = '';
        if (isset($cargoMatches[1])) {
            preg_match_all('/<li>(.*?)<\/li>/is', $cargoMatches[1], $cargoItems);
            if (isset($cargoItems[1]) && !empty($cargoItems[1])) {
                $cargo = "\n\n**Cargo:**\n";
                foreach ($cargoItems[1] as $item) {
                    $cargo .= "• " . strip_tags($item) . "\n";
                }
            }
        }
        
        // Extract plot twist
        $plotTwist = '';
        if (preg_match('/<h3>Plot Twist \(Optional\):<\/h3>\s*<p>(.*?)<\/p>/is', $content, $plotMatches)) {
            $plotTwist = "\n\n**Plot Twist:**\n" . strip_tags($plotMatches[1]);
        }
        
        // Create embed
        $embeds[] = [
            'title' => '🚢 ' . $shipName,
            'description' => $details . $cargo . $plotTwist,
            'color' => 0xbf9d61 // The Salty Parrot gold
        ];
        
        return [$embeds, "Ship: $shipName"];
    }
    
    /**
     * Format loot content for Discord
     * 
     * @param string $content HTML content
     * @return array Array containing [embeds, contentSummary]
     */
    private function formatLootContent($content) {
        $embeds = [];
        $contentSummary = '';
        
        // Extract all loot cards
        preg_match_all('/<div class="loot-card">(.*?)<\/div>/is', $content, $lootCards);
        
        if (isset($lootCards[1]) && !empty($lootCards[1])) {
            foreach ($lootCards[1] as $index => $card) {
                // Extract loot info
                preg_match('/<div class="loot-roll">Roll: (.*?)<\/div>/i', $card, $rollMatches);
                preg_match('/<div class="loot-name">(.*?)<\/div>/i', $card, $nameMatches);
                preg_match('/<div class="loot-description">(.*?)<\/div>/i', $card, $descMatches);
                preg_match('/<div class="loot-category">Category: (.*?)<\/div>/i', $card, $catMatches);
                
                $roll = isset($rollMatches[1]) ? strip_tags($rollMatches[1]) : '';
                $name = isset($nameMatches[1]) ? strip_tags($nameMatches[1]) : 'Loot item';
                $desc = isset($descMatches[1]) ? strip_tags($descMatches[1]) : '';
                $category = isset($catMatches[1]) ? strip_tags($catMatches[1]) : '';
                
                // Check for badges
                $isAncientRelic = strpos($card, 'ancient-relic-badge') !== false;
                $isThingOfImportance = strpos($card, 'thing-of-importance-badge') !== false;
                
                // Add badges to title
                $title = $name;
                if ($isAncientRelic) {
                    $title .= ' 🔮 Ancient Relic';
                }
                if ($isThingOfImportance) {
                    $title .= ' 📜 Thing of Importance';
                }
                
                // Create embed
                $embeds[] = [
                    'title' => '💰 ' . $title,
                    'description' => "**Roll:** $roll\n\n$desc\n\n**Category:** $category",
                    'color' => $index === 0 ? 0xbf9d61 : 0x805d2c // Different color for additional rolls
                ];
                
                // Add to content summary
                if ($index === 0) {
                    $contentSummary = "Loot: $name";
                }
            }
        }
        
        return [$embeds, $contentSummary];
    }
    
    /**
     * Format attribute roll content for Discord
     * 
     * @param string $content HTML content
     * @param string $characterImage Optional character image URL
     * @return array Array containing [embeds, contentSummary]
     */
    private function formatAttributeRollContent($content, $characterImage = null) {
        $embeds = [];
        
        // Extract roll details
        preg_match('/<h3>(.*?) - (.*?) Check<\/h3>/i', $content, $characterMatches);
        $characterName = isset($characterMatches[1]) ? strip_tags($characterMatches[1]) : 'Character';
        $attributeName = isset($characterMatches[2]) ? strip_tags($characterMatches[2]) : 'Attribute';
        
        // Extract roll values
        preg_match('/Dice Roll: (\d+)/i', $content, $diceMatches);
        preg_match('/' . $attributeName . ' Bonus: ([+-]?\d+)/i', $content, $bonusMatches);
        preg_match('/Total: (\d+)/i', $content, $totalMatches);
        
        $diceValue = isset($diceMatches[1]) ? $diceMatches[1] : '?';
        $attributeBonus = isset($bonusMatches[1]) ? $bonusMatches[1] : '?';
        $totalValue = isset($totalMatches[1]) ? $totalMatches[1] : '?';
        
        // Create embed
        $embed = [
            'title' => '🎲 ' . $attributeName . ' Check',
            'description' => "**Dice Roll (d20):** " . $diceValue . "\n**" . $attributeName . " Bonus:** " . $attributeBonus . "\n**Total:** " . $totalValue,
            'color' => 0x5765F2 // Discord blue color
        ];
        
        // Add character image if provided - use author with icon_url for left-side image
        if ($characterImage && filter_var($characterImage, FILTER_VALIDATE_URL)) {
            $embed['author'] = [
                'name' => $characterName,
                'icon_url' => $characterImage
            ];
        }
        
        $embeds[] = $embed;
        
        $contentSummary = $characterName . " - " . $attributeName . " Check: " . $totalValue;
        
        return [$embeds, $contentSummary];
    }
    
    /**
     * Format item use content for Discord
     * 
     * @param string $content HTML content
     * @param string $characterImage Optional character image URL
     * @return array Array containing [embeds, contentSummary]
     */
    private function formatItemUseContent($content, $characterImage = null) {
        $embeds = [];
        
        // Extract item usage details
        preg_match('/<h3>(.*?) uses an item<\/h3>/i', $content, $characterMatches);
        $characterName = isset($characterMatches[1]) ? strip_tags($characterMatches[1]) : 'Character';
        
        // Extract item name
        preg_match('/<strong>Item:<\/strong>\s*(.*?)<\/p>/i', $content, $itemMatches);
        $itemName = isset($itemMatches[1]) ? strip_tags($itemMatches[1]) : 'Unknown Item';
        
        // Extract notes if any
        $notes = '';
        if (preg_match('/<strong>Notes:<\/strong>\s*(.*?)<\/p>/i', $content, $notesMatches)) {
            $notes = "\n\n**Notes:** " . strip_tags($notesMatches[1]);
        }
        
        // Create embed
        $embed = [
            'title' => '✋ Using: ' . $itemName,
            'description' => "**Item:** " . $itemName . $notes,
            'color' => 0x7289DA // Discord blurple color
        ];
        
        // Add character image if provided - use author with icon_url for left-side image
        if ($characterImage && filter_var($characterImage, FILTER_VALIDATE_URL)) {
            $embed['author'] = [
                'name' => $characterName,
                'icon_url' => $characterImage
            ];
        }
        
        $embeds[] = $embed;
        
        $contentSummary = $characterName . " uses " . $itemName;
        
        return [$embeds, $contentSummary];
    }
}

/**
 * Helper function to create a webhook service instance
 * 
 * @param PDO $conn Optional database connection
 * @return WebhookService
 */
function createWebhookService($conn = null) {
    global $conn as $globalConn;
    $dbConn = $conn ?: $globalConn;
    
    return new WebhookService($dbConn);
}
?>