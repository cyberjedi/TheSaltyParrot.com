<?php
// File: components/discord_webhook_modal.php
// Reusable Discord webhook selection and sending component

require_once __DIR__ . '/../discord/webhook_service.php';

/**
 * Renders a Discord webhook selection and sending UI
 * 
 * @param string $contentSelector CSS selector for the content to send
 * @param string $source Source/generator type for logging
 * @param bool $includeExtraInputs Whether to include additional input fields
 * @param string $extraInputsHtml Custom HTML for additional inputs
 * @param array $options Additional options for the component
 */
function render_discord_webhook_modal($contentSelector = '#output-display', $source = 'custom', $includeExtraInputs = false, $extraInputsHtml = '', $options = []) {
    global $conn;
    
    // Initialize options with defaults
    $options = array_merge([
        'modal_title' => 'Send to Discord',
        'button_text' => 'Send to Discord',
        'button_icon' => 'fa-paper-plane',
        'button_class' => 'btn-primary',
        'modal_id' => 'discord-webhook-modal',
        'button_id' => 'open-discord-modal',
        'show_character_image' => false
    ], $options);
    
    // Check if user is logged in with Discord
    if (!function_exists('is_discord_authenticated') || !is_discord_authenticated()) {
        echo '<div class="discord-webhook-component">';
        echo '<button id="' . $options['button_id'] . '" class="btn ' . $options['button_class'] . '" disabled>';
        echo '<i class="fab fa-discord"></i> ' . $options['button_text'] . ' (Login Required)';
        echo '</button>';
        
        // Add login prompt
        echo '<div class="webhook-auth-prompt">';
        echo '<p>You need to log in with Discord to use this feature.</p>';
        if (file_exists(__DIR__ . '/../discord/discord_login_button.php')) {
            include_once __DIR__ . '/../discord/discord_login_button.php';
            render_discord_login_button();
        } else {
            echo '<a href="/discord/discord-login.php" class="btn btn-discord"><i class="fab fa-discord"></i> Login with Discord</a>';
        }
        echo '</div>';
        echo '</div>';
        return;
    }
    
    // Get webhooks for user
    $webhookService = new WebhookService($conn);
    $webhooks = $webhookService->getUserWebhooks();
    
    // Render button to open modal
    echo '<div class="discord-webhook-component">';
    echo '<button id="' . $options['button_id'] . '" class="btn ' . $options['button_class'] . '">';
    echo '<i class="fas ' . $options['button_icon'] . '"></i> ' . $options['button_text'];
    echo '</button>';
    echo '</div>';
    
    // Render modal (hidden by default)
    ?>
    <div id="<?php echo $options['modal_id']; ?>" class="discord-modal">
        <div class="discord-modal-content">
            <div class="discord-modal-header">
                <span class="discord-modal-close">&times;</span>
                <h3><?php echo $options['modal_title']; ?></h3>
            </div>
            
            <div class="discord-modal-body">
                <?php if (empty($webhooks)): ?>
                    <div class="webhook-empty-message">
                        <p>You have no Discord webhooks configured.</p>
                        <a href="/discord/webhooks.php" class="btn btn-secondary">
                            <i class="fas fa-cog"></i> Configure Webhooks
                        </a>
                    </div>
                <?php else: ?>
                    <div class="webhook-selector">
                        <label for="webhook-select">Select Discord Channel:</label>
                        <select id="webhook-select" class="webhook-dropdown">
                            <option value="">-- Select a channel --</option>
                            <?php foreach ($webhooks as $webhook): ?>
                                <option value="<?php echo $webhook['id']; ?>" <?php echo $webhook['is_default'] ? 'selected' : ''; ?>>
                                    #<?php echo htmlspecialchars($webhook['channel_name']); ?>
                                    <?php if ($webhook['is_default']): ?> (Default)<?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php if ($options['show_character_image']): ?>
                        <div class="webhook-character-image">
                            <label for="character-image-url">Character Image URL (optional):</label>
                            <input type="text" id="character-image-url" placeholder="https://example.com/image.jpg">
                            <p class="help-text">Will display alongside your message in Discord</p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($includeExtraInputs): ?>
                        <div class="webhook-extra-inputs">
                            <?php echo $extraInputsHtml; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="webhook-preview">
                        <div class="webhook-preview-header">
                            <h4>Content Preview</h4>
                            <span class="preview-note">(Content will be formatted for Discord)</span>
                        </div>
                        <div class="webhook-preview-content">
                            <div class="preview-spinner">
                                <i class="fas fa-spinner fa-spin"></i> Loading preview...
                            </div>
                            <div class="preview-content"></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="discord-modal-footer">
                <button class="btn btn-secondary discord-modal-cancel">Cancel</button>
                <?php if (!empty($webhooks)): ?>
                    <button class="btn btn-primary discord-modal-send">
                        <i class="fas fa-paper-plane"></i> Send
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get button and modal elements
            const openButton = document.getElementById('<?php echo $options['button_id']; ?>');
            const modal = document.getElementById('<?php echo $options['modal_id']; ?>');
            const closeButton = modal.querySelector('.discord-modal-close');
            const cancelButton = modal.querySelector('.discord-modal-cancel');
            const sendButton = modal.querySelector('.discord-modal-send');
            const webhookSelect = document.getElementById('webhook-select');
            
            // Content selector and source type
            const contentSelector = '<?php echo $contentSelector; ?>';
            const sourceType = '<?php echo $source; ?>';
            
            // Toggle modal visibility
            function openModal() {
                // Check if there's content to send first
                const contentElement = document.querySelector(contentSelector);
                if (!contentElement || !contentElement.innerHTML.trim() || 
                    contentElement.innerHTML.includes('placeholder-display')) {
                    alert('No content to send. Generate some content first!');
                    return;
                }
                
                modal.style.display = 'block';
                
                // Update preview content
                updatePreview();
            }
            
            function closeModal() {
                modal.style.display = 'none';
            }
            
            // Update content preview
            function updatePreview() {
                const previewContent = modal.querySelector('.preview-content');
                const previewSpinner = modal.querySelector('.preview-spinner');
                const contentElement = document.querySelector(contentSelector);
                
                // Show loading spinner
                if (previewSpinner) {
                    previewSpinner.style.display = 'block';
                }
                if (previewContent) {
                    previewContent.style.display = 'none';
                    
                    // Delay slightly to allow spinner to show
                    setTimeout(() => {
                        // Generate preview content
                        let previewHtml = '';
                        if (contentElement) {
                            // Get a simplified preview of the content
                            previewHtml = getSimplifiedPreview(contentElement.innerHTML);
                        }
                        
                        // Update preview
                        previewContent.innerHTML = previewHtml;
                        previewContent.style.display = 'block';
                        previewSpinner.style.display = 'none';
                    }, 300);
                }
            }
            
            // Generate a simplified preview of the content
            function getSimplifiedPreview(html) {
                let tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                
                // Source-specific preview formatting
                switch (sourceType) {
                    case 'ship':
                        // Extract ship name and key details
                        let shipName = tempDiv.querySelector('#ship-name');
                        let shipDetails = tempDiv.querySelector('.ship-details');
                        let cargoList = tempDiv.querySelector('#cargo-list');
                        
                        const shipPreviewHtml = '<div class="preview-item">' + 
                            (shipName ? '<h4>' + shipName.textContent + '</h4>' : '') +
                            (shipDetails ? '<div class="preview-details">' + shipDetails.innerHTML + '</div>' : '') +
                            (cargoList ? '<div class="preview-cargo"><strong>Cargo:</strong>' + cargoList.innerHTML + '</div>' : '') +
                            '</div>';
                            
                        return shipPreviewHtml;
                        
                    case 'loot':
                        // Extract loot cards
                        let lootCards = tempDiv.querySelectorAll('.loot-card');
                        let lootPreviewHtml = '';
                        
                        lootCards.forEach(card => {
                            let lootName = card.querySelector('.loot-name');
                            let lootDesc = card.querySelector('.loot-description');
                            let lootCategory = card.querySelector('.loot-category');
                            
                            lootPreviewHtml += '<div class="preview-item">';
                            lootPreviewHtml += lootName ? '<h4>' + lootName.textContent + '</h4>' : '';
                            lootPreviewHtml += lootDesc ? '<p>' + lootDesc.textContent + '</p>' : '';
                            lootPreviewHtml += lootCategory ? '<p><em>' + lootCategory.textContent + '</em></p>' : '';
                            lootPreviewHtml += '</div>';
                        });
                        
                        return lootPreviewHtml;
                        
                    default:
                        // Generic preview with limited HTML
                        // Remove scripts and iframes for security
                        let scripts = tempDiv.querySelectorAll('script, iframe');
                        scripts.forEach(script => script.remove());
                        
                        // Return a simplified version
                        let genericHTML = tempDiv.innerHTML.substring(0, 500);
                        return '<div class="preview-generic">' + 
                               genericHTML + 
                               (tempDiv.innerHTML.length > 500 ? '...' : '') +
                               '</div>';
                }
            }
            
            // Send content to Discord
            function sendToDiscord() {
                // Get selected webhook
                const webhookId = webhookSelect.value;
                if (!webhookId) {
                    alert('Please select a Discord channel first.');
                    return;
                }
                
                // Get content to send
                const contentElement = document.querySelector(contentSelector);
                if (!contentElement || !contentElement.innerHTML.trim()) {
                    alert('No content to send.');
                    return;
                }
                
                // Show sending state
                sendButton.disabled = true;
                sendButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
                
                // Prepare payload
                let payload = {
                    webhook_id: webhookId,
                    content: contentElement.innerHTML,
                    source: sourceType,
                    content_type: 'formatted'
                };
                
                // Add character image if applicable
                <?php if ($options['show_character_image']): ?>
                const characterImageUrl = document.getElementById('character-image-url');
                if (characterImageUrl && characterImageUrl.value.trim()) {
                    payload.character_image = characterImageUrl.value.trim();
                }
                <?php endif; ?>
                
                // Add any custom fields from extra inputs
                const extraInputs = modal.querySelectorAll('.webhook-extra-inputs input, .webhook-extra-inputs select, .webhook-extra-inputs textarea');
                extraInputs.forEach(input => {
                    if (input.name && input.value) {
                        payload[input.name] = input.value;
                    }
                });
                
                // Send to webhook
                fetch('/api/send_webhook.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Show success state briefly
                        sendButton.innerHTML = '<i class="fas fa-check"></i> Sent!';
                        setTimeout(() => {
                            // Close modal
                            closeModal();
                            
                            // Reset button state
                            sendButton.disabled = false;
                            sendButton.innerHTML = '<i class="fas fa-paper-plane"></i> Send';
                        }, 1500);
                    } else {
                        // Show error
                        alert('Error sending to Discord: ' + data.message);
                        sendButton.disabled = false;
                        sendButton.innerHTML = '<i class="fas fa-paper-plane"></i> Send';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error sending to Discord. Check console for details.');
                    sendButton.disabled = false;
                    sendButton.innerHTML = '<i class="fas fa-paper-plane"></i> Send';
                });
            }
            
            // Event listeners
            if (openButton) {
                openButton.addEventListener('click', openModal);
            }
            
            if (closeButton) {
                closeButton.addEventListener('click', closeModal);
            }
            
            if (cancelButton) {
                cancelButton.addEventListener('click', closeModal);
            }
            
            if (sendButton) {
                sendButton.addEventListener('click', sendToDiscord);
            }
            
            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    closeModal();
                }
            });
            
            // Handle webhook selection change
            if (webhookSelect) {
                webhookSelect.addEventListener('change', function() {
                    // Enable send button if webhook is selected
                    if (sendButton) {
                        sendButton.disabled = !webhookSelect.value;
                    }
                });
            }
        });
    </script>
    
    <style>
        /* Discord Modal Styles */
        .discord-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            overflow: auto;
        }
        
        .discord-modal-content {
            background-color: #36393f;
            color: #dcddde;
            margin: 10% auto;
            padding: 0;
            width: 80%;
            max-width: 600px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
            animation: discord-modal-in 0.3s ease-out;
        }
        
        @keyframes discord-modal-in {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .discord-modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #202225;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .discord-modal-header h3 {
            margin: 0;
            color: #fff;
            font-size: 18px;
        }
        
        .discord-modal-close {
            color: #72767d;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .discord-modal-close:hover {
            color: #fff;
        }
        
        .discord-modal-body {
            padding: 20px;
            max-height: 60vh;
            overflow-y: auto;
        }
        
        .discord-modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #202225;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .webhook-selector {
            margin-bottom: 20px;
        }
        
        .webhook-dropdown {
            width: 100%;
            padding: 10px;
            background-color: #2f3136;
            color: #dcddde;
            border: 1px solid #202225;
            border-radius: 4px;
            margin-top: 8px;
        }
        
        .webhook-character-image {
            margin-bottom: 20px;
        }
        
        .webhook-character-image input {
            width: 100%;
            padding: 10px;
            background-color: #2f3136;
            color: #dcddde;
            border: 1px solid #202225;
            border-radius: 4px;
            margin-top: 8px;
        }
        
        .webhook-preview {
            background-color: #2f3136;
            border: 1px solid #202225;
            border-radius: 4px;
            padding: 10px;
            margin-top: 20px;
        }
        
        .webhook-preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .webhook-preview-header h4 {
            margin: 0;
            color: #fff;
        }
        
        .preview-note {
            font-size: 12px;
            color: #72767d;
        }
        
        .webhook-preview-content {
            background-color: #36393f;
            border-radius: 4px;
            padding: 15px;
            min-height: 100px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .preview-spinner {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100px;
            color: #72767d;
        }
        
        .preview-item {
            border-left: 4px solid #7289da;
            padding-left: 10px;
            margin-bottom: 15px;
        }
        
        .preview-item h4 {
            margin-top: 0;
            color: #fff;
        }
        
        .preview-item p {
            margin: 5px 0;
        }
        
        .webhook-empty-message {
            text-align: center;
            padding: 20px;
        }
        
        .webhook-empty-message p {
            margin-bottom: 15px;
        }
        
        .webhook-extra-inputs {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #2f3136;
            border-radius: 4px;
        }
        
        .help-text {
            font-size: 12px;
            color: #72767d;
            margin-top: 5px;
        }
        
        .webhook-auth-prompt {
            margin-top: 10px;
            padding: 15px;
            background-color: #2f3136;
            border-radius: 4px;
            text-align: center;
        }
    </style>
    <?php
}
?>