<?php
// File: discord/discord_service.php
// This file provides functions for integrating Discord webhooks with generators

/**
 * Fetch available webhooks for a user
 * 
 * @param PDO $conn Database connection
 * @param int $user_id User ID
 * @return array List of available webhooks
 */
function get_user_webhooks($conn, $user_id) {
    $webhooks = [];
    
    try {
        $stmt = $conn->prepare("SELECT * FROM discord_webhooks WHERE user_id = :user_id AND is_active = 1 ORDER BY last_updated DESC");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error fetching webhooks: ' . $e->getMessage());
    }
    
    return $webhooks;
}

/**
 * Renders webhook selector UI
 * 
 * @param array $webhooks List of available webhooks
 * @param string $generator_type The type of generator (ship, loot, etc.)
 * @return void Outputs HTML
 */
function render_webhook_selector($webhooks, $generator_type = '') {
    if (empty($webhooks)) {
        echo '<div class="webhook-selector-empty">';
        echo '<p>You have no Discord webhooks set up. <a href="discord/webhooks.php">Configure webhooks</a> to send content to Discord.</p>';
        echo '</div>';
        return;
    }
    
    echo '<div class="send-to-discord">';
    echo '<div class="send-to-discord-title">Send to Discord</div>';
    echo '<div class="webhook-selector">';
    
    foreach ($webhooks as $webhook) {
        echo '<div class="webhook-option" data-webhook-id="' . $webhook['id'] . '">';
        echo '<i class="fab fa-discord"></i> #' . htmlspecialchars($webhook['channel_name']);
        echo '</div>';
    }
    
    echo '</div>'; // End webhook-selector
    echo '<button id="send-to-discord-btn" class="btn btn-secondary" disabled>';
    echo '<i class="fas fa-paper-plane"></i> Send to Discord';
    echo '</button>';
    echo '</div>'; // End send-to-discord
    
    // Add JavaScript to handle selection and sending
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const webhookOptions = document.querySelectorAll('.webhook-option');
            const sendButton = document.getElementById('send-to-discord-btn');
            let selectedWebhookId = null;
            
            // Handle webhook selection
            webhookOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Remove selected class from all options
                    webhookOptions.forEach(opt => opt.classList.remove('selected'));
                    
                    // Add selected class to clicked option
                    this.classList.add('selected');
                    
                    // Store selected webhook ID
                    selectedWebhookId = this.dataset.webhookId;
                    
                    // Enable send button
                    sendButton.removeAttribute('disabled');
                });
            });
            
            // Handle send button click
            if (sendButton) {
                sendButton.addEventListener('click', function() {
                    if (!selectedWebhookId) {
                        alert('Please select a Discord channel first.');
                        return;
                    }
                    
                    // Get output content
                    const outputContent = document.getElementById('output-display').innerHTML;
                    if (!outputContent || outputContent.includes('placeholder-display')) {
                        alert('No content to send. Generate some content first!');
                        return;
                    }
                    
                    // Disable button and show loading state
                    sendButton.setAttribute('disabled', 'true');
                    sendButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
                    
                    // Send content to webhook
                    fetch('discord/send_to_webhook.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            webhook_id: selectedWebhookId,
                            content: outputContent,
                            generator_type: '<?php echo $generator_type; ?>'
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            sendButton.innerHTML = '<i class="fas fa-check"></i> Sent!';
                            setTimeout(() => {
                                sendButton.innerHTML = '<i class="fas fa-paper-plane"></i> Send to Discord';
                                sendButton.removeAttribute('disabled');
                            }, 2000);
                        } else {
                            alert('Error sending to Discord: ' + data.message);
                            sendButton.innerHTML = '<i class="fas fa-paper-plane"></i> Send to Discord';
                            sendButton.removeAttribute('disabled');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error sending to Discord. Check console for details.');
                        sendButton.innerHTML = '<i class="fas fa-paper-plane"></i> Send to Discord';
                        sendButton.removeAttribute('disabled');
                    });
                });
            }
        });
    </script>
    <?php
}

/**
 * Send content to a Discord webhook
 * 
 * @param PDO $conn Database connection
 * @param int $webhook_id Webhook ID
 * @param string $content HTML content to send
 * @param string $generator_type Generator type (ship, loot, etc.)
 * @return array Status and message
 */
function send_to_discord_webhook($conn, $webhook_id, $content, $generator_type) {
    // Initialize response
    $response = [
        'status' => 'error',
        'message' => 'Unknown error'
    ];
    
    try {
        // Get webhook details
        $stmt = $conn->prepare("SELECT * FROM discord_webhooks WHERE id = :id");
        $stmt->bindParam(':id', $webhook_id);
        $stmt->execute();
        $webhook = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$webhook) {
            return [
                'status' => 'error',
                'message' => 'Webhook not found'
            ];
        }
        
        // Get user ID for logging
        $user_id = $webhook['user_id'];
        
        // Process the HTML content to extract relevant parts for Discord
        $content_summary = '';
        $embeds = [];
        
        // Different formatting based on generator type
        switch ($generator_type) {
            case 'ship':
                // Extract ship name and details
                preg_match('/<h2 id="ship-name">(.*?)<\/h2>/i', $content, $ship_name_matches);
                $ship_name = isset($ship_name_matches[1]) ? strip_tags($ship_name_matches[1]) : 'Ship';
                
                // Extract ship details
                preg_match('/<div class="ship-details">(.*?)<\/div>/is', $content, $details_matches);
                $details = isset($details_matches[1]) ? $details_matches[1] : '';
                
                // Clean up HTML
                $details = strip_tags(str_replace(['<h3>', '</h3>', '<strong>', '</strong>', '<br>'], ["\n\n**", "**\n", '**', '**', "\n"], $details));
                
                // Extract cargo items
                preg_match('/<ul id="cargo-list">(.*?)<\/ul>/is', $content, $cargo_matches);
                $cargo = '';
                if (isset($cargo_matches[1])) {
                    preg_match_all('/<li>(.*?)<\/li>/is', $cargo_matches[1], $cargo_items);
                    if (isset($cargo_items[1]) && !empty($cargo_items[1])) {
                        $cargo = "\n\n**Cargo:**\n";
                        foreach ($cargo_items[1] as $item) {
                            $cargo .= "• " . strip_tags($item) . "\n";
                        }
                    }
                }
                
                // Extract plot twist
                $plot_twist = '';
                if (preg_match('/<h3>Plot Twist \(Optional\):<\/h3>\s*<p>(.*?)<\/p>/is', $content, $plot_matches)) {
                    $plot_twist = "\n\n**Plot Twist:**\n" . strip_tags($plot_matches[1]);
                }
                
                // Create embed
                $embeds[] = [
                    'title' => '🚢 ' . $ship_name,
                    'description' => $details . $cargo . $plot_twist,
                    'color' => 0xbf9d61 // The Salty Parrot gold
                ];
                
                $content_summary = "Ship: $ship_name";
                break;
                
            case 'loot':
                // Extract all loot cards
                preg_match_all('/<div class="loot-card">(.*?)<\/div>/is', $content, $loot_cards);
                
                if (isset($loot_cards[1]) && !empty($loot_cards[1])) {
                    foreach ($loot_cards[1] as $index => $card) {
                        // Extract loot info
                        preg_match('/<div class="loot-roll">Roll: (.*?)<\/div>/i', $card, $roll_matches);
                        preg_match('/<div class="loot-name">(.*?)<\/div>/i', $card, $name_matches);
                        preg_match('/<div class="loot-description">(.*?)<\/div>/i', $card, $desc_matches);
                        preg_match('/<div class="loot-category">Category: (.*?)<\/div>/i', $card, $cat_matches);
                        
                        $roll = isset($roll_matches[1]) ? strip_tags($roll_matches[1]) : '';
                        $name = isset($name_matches[1]) ? strip_tags($name_matches[1]) : 'Loot item';
                        $desc = isset($desc_matches[1]) ? strip_tags($desc_matches[1]) : '';
                        $category = isset($cat_matches[1]) ? strip_tags($cat_matches[1]) : '';
                        
                        // Check for badges
                        $is_ancient_relic = strpos($card, 'ancient-relic-badge') !== false;
                        $is_thing_of_importance = strpos($card, 'thing-of-importance-badge') !== false;
                        
                        // Add badges to title
                        $title = $name;
                        if ($is_ancient_relic) {
                            $title .= ' 🔮 Ancient Relic';
                        }
                        if ($is_thing_of_importance) {
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
                            $content_summary = "Loot: $name";
                        }
                    }
                }
                break;
            
            case 'attribute_roll':
                // Extract roll details
                preg_match('/<h3>(.*?) - (.*?) Check<\/h3>/i', $content, $character_matches);
                $character_name = isset($character_matches[1]) ? strip_tags($character_matches[1]) : 'Character';
                $attribute_name = isset($character_matches[2]) ? strip_tags($character_matches[2]) : 'Attribute';
                
                // Extract roll values
                preg_match('/Dice Roll: (\d+)/i', $content, $dice_matches);
                preg_match('/' . $attribute_name . ' Bonus: ([+-]?\d+)/i', $content, $bonus_matches);
                preg_match('/Total: (\d+)/i', $content, $total_matches);
                
                $dice_value = isset($dice_matches[1]) ? $dice_matches[1] : '?';
                $attribute_bonus = isset($bonus_matches[1]) ? $bonus_matches[1] : '?';
                $total_value = isset($total_matches[1]) ? $total_matches[1] : '?';
                
                // Create embed
                $embeds[] = [
                    'title' => '🎲 ' . $character_name . ' - ' . $attribute_name . ' Check',
                    'description' => "**Dice Roll (d20):** " . $dice_value . "\n**" . $attribute_name . " Bonus:** " . $attribute_bonus . "\n**Total:** " . $total_value,
                    'color' => 0x5765F2 // Discord blue color
                ];
                
                $content_summary = $character_name . " - " . $attribute_name . " Check: " . $total_value;
                break;
                
            case 'item_use':
                // Extract item usage details
                preg_match('/<h3>(.*?) uses an item<\/h3>/i', $content, $character_matches);
                $character_name = isset($character_matches[1]) ? strip_tags($character_matches[1]) : 'Character';
                
                // Extract item name
                preg_match('/<strong>Item:<\/strong>\s*(.*?)<\/p>/i', $content, $item_matches);
                $item_name = isset($item_matches[1]) ? strip_tags($item_matches[1]) : 'Unknown Item';
                
                // Extract notes if any
                $notes = '';
                if (preg_match('/<strong>Notes:<\/strong>\s*(.*?)<\/p>/i', $content, $notes_matches)) {
                    $notes = "\n\n**Notes:** " . strip_tags($notes_matches[1]);
                }
                
                // Create embed
                $embeds[] = [
                    'title' => '✋ ' . $character_name . ' uses ' . $item_name,
                    'description' => "**Item:** " . $item_name . $notes,
                    'color' => 0x7289DA // Discord blurple color
                ];
                
                $content_summary = $character_name . " uses " . $item_name;
                break;
                
            default:
                // Generic handling for other generator types
                $embeds[] = [
                    'title' => '🏴‍☠️ The Salty Parrot - Generated Content',
                    'description' => 'Content generated with The Salty Parrot. Visit the app to see the full details!',
                    'color' => 0xbf9d61
                ];
                
                $content_summary = "Content from " . ucfirst($generator_type) . " generator";
        }
        
        // Prepare webhook message
        $message = [
            'content' => null,
            'embeds' => $embeds
        ];
        
        // Send message to webhook
        $url = "https://discord.com/api/webhooks/{$webhook['webhook_id']}/{$webhook['webhook_token']}";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response_text = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Log webhook usage
        $stmt = $conn->prepare("INSERT INTO discord_webhook_logs 
            (webhook_id, user_id, generator_type, content_summary, status_code, is_success, request_timestamp, response_timestamp) 
            VALUES 
            (:webhook_id, :user_id, :generator_type, :content_summary, :status_code, :is_success, NOW(), NOW())");
            
        $stmt->bindParam(':webhook_id', $webhook_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':generator_type', $generator_type);
        $stmt->bindParam(':content_summary', $content_summary);
        $stmt->bindParam(':status_code', $http_code);
        $is_success = ($http_code >= 200 && $http_code < 300) ? 1 : 0;
        $stmt->bindParam(':is_success', $is_success);
        
        $stmt->execute();
        
        // Check response status
        if ($http_code >= 200 && $http_code < 300) {
            $response = [
                'status' => 'success',
                'message' => 'Content sent to Discord successfully'
            ];
        } else {
            // Log error message
            $error_data = json_decode($response_text, true);
            $error_message = isset($error_data['message']) ? $error_data['message'] : 'Unknown error';
            
            // Update webhook log with error
            $stmt = $conn->prepare("UPDATE discord_webhook_logs SET error_message = :error_message WHERE webhook_id = :webhook_id AND user_id = :user_id ORDER BY id DESC LIMIT 1");
            $stmt->bindParam(':error_message', $error_message);
            $stmt->bindParam(':webhook_id', $webhook_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $response = [
                'status' => 'error',
                'message' => 'Discord API error: ' . $error_message
            ];
        }
        
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        $response = [
            'status' => 'error',
            'message' => 'Database error'
        ];
    } catch (Exception $e) {
        error_log('General error: ' . $e->getMessage());
        $response = [
            'status' => 'error',
            'message' => 'An unexpected error occurred'
        ];
    }
    
    return $response;
}
?>

