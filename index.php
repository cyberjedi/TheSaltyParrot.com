<?php
// Set the current page
$current_page = 'dashboard';

// Discord integration - safely load if available
$discord_enabled = false;
$discord_authenticated = false;
$user_webhooks = [];

if (file_exists('discord/discord-config.php')) {
    // Try to include the Discord configuration
    try {
        require_once 'discord/discord-config.php';
        $discord_enabled = true;
        
        // Check if user is authenticated with Discord
        if (function_exists('is_discord_authenticated')) {
            $discord_authenticated = is_discord_authenticated();
        }
        
        // Get user ID and webhooks if authenticated
        if ($discord_authenticated && isset($conn)) {
            try {
                // Get user ID
                $discord_id = $_SESSION['discord_user']['id'];
                
                // Get user ID from database
                $stmt = $conn->prepare("SELECT id FROM discord_users WHERE discord_id = :discord_id");
                $stmt->bindParam(':discord_id', $discord_id);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Initialize empty array for webhooks
                $user_webhooks = [];
                
                if ($user) {
                    $user_id = $user['id'];
                    // Include the discord_service.php file to get the get_user_webhooks function
                    if (file_exists('discord/discord_service.php')) {
                        require_once 'discord/discord_service.php';
                        if (function_exists('get_user_webhooks')) {
                            $user_webhooks = get_user_webhooks($conn, $user_id);
                        } else {
                            error_log('get_user_webhooks function not found');
                        }
                    } else {
                        error_log('discord_service.php file not found');
                    }
                }
            } catch (Exception $e) {
                error_log('Discord user webhooks error: ' . $e->getMessage());
            }
        }
    } catch (Exception $e) {
        error_log('Discord integration error: ' . $e->getMessage());
        $discord_enabled = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Salty Parrot</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <?php if ($discord_enabled && file_exists('css/discord.css')): ?>
    <link rel="stylesheet" href="css/discord.css">
    <?php endif; ?>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <style>
        /* New Generator Page Styles */
        .generators-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 20px;
            height: calc(100vh - 180px);
        }
        
        .generators-sidebar {
            background-color: var(--dark);
            border-radius: 8px;
            padding: 20px;
            border: 1px solid rgba(191, 157, 97, 0.3);
            overflow-y: auto;
        }
        
        .generator-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .generator-btn {
            background-color: rgba(0, 0, 0, 0.3);
            color: var(--secondary);
            border: 1px solid var(--secondary);
            border-radius: 6px;
            padding: 12px 15px;
            text-align: left;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .generator-btn:hover, .generator-btn.active {
            background-color: rgba(191, 157, 97, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        
        .generator-btn i {
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }
        
        .output-box {
            grid-column: 2;
            background-color: var(--dark);
            border-radius: 8px;
            padding: 20px;
            border: 1px solid rgba(191, 157, 97, 0.3);
            overflow: auto;
            height: 100%;
        }
        
        .output-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 1px solid rgba(191, 157, 97, 0.3);
            padding-bottom: 10px;
        }
        
        .output-title {
            color: var(--secondary);
            margin: 0;
            font-size: 1.3rem;
        }
        
        .output-actions {
            display: flex;
            gap: 10px;
        }
        
        .output-action-btn {
            background: none;
            border: none;
            color: var(--secondary);
            cursor: pointer;
            font-size: 1.2rem;
            padding: 5px;
            transition: all 0.2s;
        }
        
        .output-action-btn:hover {
            color: #fff;
            transform: translateY(-2px);
        }
        
        .discord-send-btn {
            color: #7289DA; /* Discord blue */
        }
        
        @media (max-width: 1000px) {
            .generators-container {
                grid-template-columns: 1fr;
                grid-template-rows: auto 1fr;
            }
            
            .generators-sidebar {
                grid-row: 1;
                max-height: 200px;
            }
            
            .generator-buttons {
                flex-direction: row;
                flex-wrap: wrap;
            }
            
            .generator-btn {
                flex: 1 0 auto;
                min-width: 150px;
            }
            
            .output-box {
                grid-column: 1;
                grid-row: 2;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Include the sidebar -->
        <?php include 'components/sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <main class="main-content">
            <div class="generators-container">
                <!-- Generator Buttons Sidebar -->
                <div class="generators-sidebar">
                    <div class="generator-buttons">
                        <button id="ship-generator-btn" class="generator-btn">
                            <i class="fas fa-ship"></i>
                            <span>Ship Generator</span>
                        </button>
                        <button id="loot-generator-btn" class="generator-btn">
                            <i class="fas fa-coins"></i>
                            <span>Loot Generator</span>
                        </button>
                    </div>
                </div>
                
                <!-- Output Box -->
                <div class="output-box">
                    <div class="output-header">
                        <h3 class="output-title">Output</h3>
                        <div class="output-actions">
                            <button id="send-discord-btn" class="output-action-btn discord-send-btn" title="Send to Discord">
                                <i class="fab fa-discord"></i>
                            </button>
                            <button id="save-output-btn" class="output-action-btn" title="Save Output">
                                <i class="fas fa-save"></i>
                            </button>
                            <button id="print-output-btn" class="output-action-btn" title="Print Output">
                                <i class="fas fa-print"></i>
                            </button>
                            <button id="clear-output-btn" class="output-action-btn" title="Clear Output">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div id="output-display">
                        <div class="placeholder-display">
                            <i class="fas fa-dice"></i>
                            <p>Click on a generator button to the left<br>Results will appear here</p>
                        </div>
                    </div>
                    
                    <?php 
                    // Add debug info for Discord integration status
                    if (isset($_GET['debug']) && $_GET['debug'] == 1): ?>
                        <div style="margin-top: 20px; padding: 10px; background: rgba(0,0,0,0.2); border: 1px solid #333; font-size: 12px;">
                            <p>Discord enabled: <?php echo $discord_enabled ? 'Yes' : 'No'; ?></p>
                            <p>Discord authenticated: <?php echo $discord_authenticated ? 'Yes' : 'No'; ?></p>
                            <p>Webhook selector function: <?php echo function_exists('render_webhook_selector') ? 'Yes' : 'No'; ?></p>
                            <p>User webhooks count: <?php echo count($user_webhooks); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php
                    // CRITICAL FIX: Always render all containers but hide them.
                    // The actual display will be controlled by JavaScript based on API response
                    ?>
                    
                    <!-- Webhook not configured message - always present but hidden by default -->
                    <div id="webhook-not-configured" style="display: none; margin-top: 20px; text-align: center; padding: 15px; background: rgba(255,100,100,0.1); border: 1px solid #d66;">
                        <p>You need to set up a Discord webhook to send content. <a href="discord/webhooks.php" style="color: #7289DA;">Configure webhooks</a></p>
                    </div>
                    
                    <!-- Discord not connected message - always present but hidden by default -->
                    <div id="discord-not-connected" style="display: none; margin-top: 20px; text-align: center; padding: 15px; background: rgba(255,100,100,0.1); border: 1px solid #d66;">
                        <p>You need to connect your Discord account to send content to Discord.</p>
                    </div>
                    <?php // End of Discord container section ?>
                </div>
            </div>
        </main>
    </div>
    
    <footer>
        <p>The Salty Parrot is an independent production by Stuart Greenwell. It is not affiliated with Limithron LLC. It is published under the PIRATE BORG Third Party License. PIRATE BORG is ©2022 Limithron LLC.</p>
        <p>&copy; 2025 The Salty Parrot</p>
    </footer>

    <!-- Include the print helper component -->
    <?php include 'components/print_helper.php'; ?>
    
    <!-- Discord webhook modal - matches the inventory implementation -->
    <div id="discord-webhook-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h3>Send to Discord</h3>
            
            <div class="send-to-discord-content">
                <div id="webhook-content-preview">
                    <p>Content will be sent to your Discord channel.</p>
                </div>
                
                <div id="webhook-loading" style="text-align: center; display: none;">
                    <p><i class="fas fa-spinner fa-spin"></i> Loading webhooks...</p>
                </div>
                
                <div id="webhook-error" style="display: none; color: #d33; margin: 10px 0;">
                    <p><i class="fas fa-exclamation-triangle"></i> <span id="webhook-error-message"></span></p>
                </div>
            </div>
            
            <div class="form-buttons">
                <button type="button" class="btn btn-secondary close-modal-btn">Cancel</button>
                <button type="button" class="btn btn-discord" id="send-to-discord-btn" disabled>
                    <i class="fab fa-discord"></i> Send to Discord
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log("Generators page loaded");
            
            const outputDisplay = document.getElementById('output-display');
            const clearOutputBtn = document.getElementById('clear-output-btn');
            const printOutputBtn = document.getElementById('print-output-btn');
            const saveOutputBtn = document.getElementById('save-output-btn');
            const sendDiscordBtn = document.getElementById('send-discord-btn'); // This is the button in header
            const discordModal = document.getElementById('discord-webhook-modal'); // The modal element
            
            // IMPORTANT: Get the webhook elements by their IDs
            console.log("Looking for webhook containers...");
            const webhookSelectorContainer = document.getElementById('webhook-selector-container');
            console.log("webhook-selector-container found:", webhookSelectorContainer);
            
            const webhookNotConfigured = document.getElementById('webhook-not-configured');
            console.log("webhook-not-configured found:", webhookNotConfigured);
            
            const discordNotConnected = document.getElementById('discord-not-connected');
            console.log("discord-not-connected found:", discordNotConnected);
            
            // Generator buttons
            const shipGeneratorBtn = document.getElementById('ship-generator-btn');
            const lootGeneratorBtn = document.getElementById('loot-generator-btn');
            const diceGeneratorBtn = document.getElementById('dice-generator-btn');
            const npcGeneratorBtn = document.getElementById('npc-generator-btn');
            const treasureGeneratorBtn = document.getElementById('treasure-generator-btn');
            
            // Make discord send button initially disabled
            if (sendDiscordBtn) {
                sendDiscordBtn.style.opacity = '0.5';
                sendDiscordBtn.disabled = true;
            }
            
            // Function to clear active state from all buttons
            function clearActiveButtons() {
                document.querySelectorAll('.generator-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
            }
            
            // Function to close all modals
            function closeAllModals() {
                // Get all modals
                const discordModal = document.getElementById('discord-webhook-modal');
                
                // Hide modals - reset to original CSS values
                if (discordModal) {
                    discordModal.setAttribute('style', 'display: none !important; visibility: hidden !important;');
                }
            }
            
            // Clear output button
            if (clearOutputBtn) {
                clearOutputBtn.addEventListener('click', function() {
                    outputDisplay.innerHTML = `
                        <div class="placeholder-display">
                            <i class="fas fa-dice"></i>
                            <p>Click on a generator button to the left<br>Results will appear here</p>
                        </div>
                    `;
                    
                    // Hide all Discord-related containers
                    if (webhookSelectorContainer) {
                        webhookSelectorContainer.style.display = 'none';
                    }
                    if (webhookNotConfigured) {
                        webhookNotConfigured.style.display = 'none';
                    }
                    if (discordNotConnected) {
                        discordNotConnected.style.display = 'none';
                    }
                    
                    // Disable Discord send button
                    if (sendDiscordBtn) {
                        sendDiscordBtn.style.opacity = '0.5';
                        sendDiscordBtn.disabled = true;
                    }
                });
            }
            
            // Print output button
            if (printOutputBtn) {
                printOutputBtn.addEventListener('click', function() {
                    // Check if there's content to print
                    if (outputDisplay.querySelector('.placeholder-display')) {
                        alert("Nothing to print yet. Generate some content first!");
                        return;
                    }
                    
                    // Get content from output box
                    const content = outputDisplay.innerHTML;
                    
                    // Call the print helper function
                    <?php echo "generatePrintableContent(content);"; ?>
                });
            }
            
            // Save output button
            if (saveOutputBtn) {
                saveOutputBtn.addEventListener('click', function() {
                    // Check if there's content to save
                    if (outputDisplay.querySelector('.placeholder-display')) {
                        alert("Nothing to save yet. Generate some content first!");
                        return;
                    }
                    
                    // Simple alert for now
                    alert("Save functionality coming soon!");
                });
            }
            
            // Discord send button
            if (sendDiscordBtn) {
                sendDiscordBtn.addEventListener('click', function() {
                    // Check if there's content to send
                    if (outputDisplay.querySelector('.placeholder-display')) {
                        alert("Nothing to send yet. Generate some content first!");
                        return;
                    }
                    
                    // Get modal and reset its state
                    const discordModal = document.getElementById('discord-webhook-modal');
                    const webhookLoading = document.getElementById('webhook-loading');
                    const webhookError = document.getElementById('webhook-error');
                    const webhookErrorMessage = document.getElementById('webhook-error-message');
                    const sendBtn = document.getElementById('send-to-discord-btn');
                    
                    // Reset modal state
                    if (sendBtn) sendBtn.disabled = true;
                    if (webhookLoading) webhookLoading.style.display = 'block';
                    if (webhookError) webhookError.style.display = 'none';
                    
                    // Show the modal - override the CSS !important rule
                    if (discordModal) {
                        discordModal.setAttribute('style', 'display: block !important; visibility: visible !important;');
                    }
                    
                    // Get base URL from window location
                    const baseUrl = window.location.href.split('index.php')[0] || './';
                    const webhookUrl = baseUrl + 'discord/webhooks.php?action=get_default_webhook&format=json';
                    
                    // Fetch default webhook from the server
                    console.log('Fetching default webhook from:', webhookUrl);
                    
                    fetch(webhookUrl)
                        .then(response => {
                            console.log('Webhook response status:', response.status);
                            if (!response.ok) {
                                throw new Error(`Server error: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            console.log('Webhook response data:', data);
                            
                            // Hide loading indicator
                            if (webhookLoading) webhookLoading.style.display = 'none';
                            
                            if (data.status === 'success' && data.webhook) {
                                console.log("Webhook found:", data.webhook);
                                
                                // Update content preview
                                const previewDiv = document.getElementById('webhook-content-preview');
                                if (previewDiv) {
                                    previewDiv.innerHTML = `
                                        <p>Content will be sent to <strong>${data.webhook.webhook_name}</strong> 
                                        channel <strong>#${data.webhook.channel_name}</strong></p>
                                    `;
                                }
                                
                                // Enable send button and attach webhook ID
                                if (sendBtn) {
                                    sendBtn.disabled = false;
                                    sendBtn.dataset.webhookId = data.webhook.id;
                                    
                                    // Remove any previous event listeners
                                    const newSendBtn = sendBtn.cloneNode(true);
                                    sendBtn.parentNode.replaceChild(newSendBtn, sendBtn);
                                    
                                    // Add click handler
                                    newSendBtn.addEventListener('click', function() {
                                        const outputContent = document.getElementById('output-display').innerHTML;
                                        const webhookId = this.dataset.webhookId;
                                        const generatorType = sendDiscordBtn.getAttribute('data-generator-type') || '';
                                        
                                        // Show loading state
                                        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
                                        this.disabled = true;
                                        
                                        // Send to webhook
                                        fetch('discord/send_to_webhook.php', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                            },
                                            body: JSON.stringify({
                                                webhook_id: webhookId,
                                                content: outputContent,
                                                generator_type: generatorType
                                            })
                                        })
                                        .then(response => response.json())
                                        .then(result => {
                                            if (result.status === 'success') {
                                                this.innerHTML = '<i class="fas fa-check"></i> Sent!';
                                                
                                                // Close modal after success
                                                setTimeout(() => {
                                                    closeAllModals();
                                                    this.innerHTML = '<i class="fab fa-discord"></i> Send to Discord';
                                                    this.disabled = false;
                                                }, 1500);
                                            } else {
                                                if (webhookError) {
                                                    webhookError.style.display = 'block';
                                                    if (webhookErrorMessage) {
                                                        webhookErrorMessage.textContent = result.message || 'Error sending to Discord';
                                                    }
                                                }
                                                this.innerHTML = '<i class="fab fa-discord"></i> Send to Discord';
                                                this.disabled = false;
                                            }
                                        })
                                        .catch(error => {
                                            console.error('Error sending to Discord:', error);
                                            if (webhookError) {
                                                webhookError.style.display = 'block';
                                                if (webhookErrorMessage) {
                                                    webhookErrorMessage.textContent = error.message || 'Error sending to Discord';
                                                }
                                            }
                                            this.innerHTML = '<i class="fab fa-discord"></i> Send to Discord';
                                            this.disabled = false;
                                        });
                                    });
                                }
                            } else {
                                // Show error if no webhook found
                                if (webhookError) {
                                    webhookError.style.display = 'block';
                                    if (webhookErrorMessage) {
                                        webhookErrorMessage.textContent = data.message || 'No webhook configured';
                                    }
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching webhook:', error);
                            
                            // Hide loading and show error
                            if (webhookLoading) webhookLoading.style.display = 'none';
                            if (webhookError) {
                                webhookError.style.display = 'block';
                                if (webhookErrorMessage) {
                                    webhookErrorMessage.textContent = error.message || 'Error connecting to Discord';
                                }
                            }
                        });
                });
            }
            
            // Add event listeners for closing modals
            document.querySelectorAll('.close-modal, .close-modal-btn').forEach(btn => {
                btn.addEventListener('click', closeAllModals);
            });
            
            // Generator button event listeners
            if (shipGeneratorBtn) {
                shipGeneratorBtn.addEventListener('click', function() {
                    clearActiveButtons();
                    this.classList.add('active');
                    Generators.generateShip();
                });
            }
            
            if (lootGeneratorBtn) {
                lootGeneratorBtn.addEventListener('click', function() {
                    clearActiveButtons();
                    this.classList.add('active');
                    Generators.generateLoot();
                });
            }
            
            if (diceGeneratorBtn) {
                diceGeneratorBtn.addEventListener('click', function() {
                    clearActiveButtons();
                    this.classList.add('active');
                    Generators.diceRoller();
                });
            }
            
            if (npcGeneratorBtn) {
                npcGeneratorBtn.addEventListener('click', function() {
                    clearActiveButtons();
                    this.classList.add('active');
                    Generators.npcGenerator();
                });
            }
            
            if (treasureGeneratorBtn) {
                treasureGeneratorBtn.addEventListener('click', function() {
                    clearActiveButtons();
                    this.classList.add('active');
                    Generators.treasureGenerator();
                });
            }
            
            // Set up Generator API functions
            window.Generators = {
                // Ship Generator
                generateShip: function() {
                    // Show loading state
                    outputDisplay.innerHTML = `
                        <div class="loading-indicator">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p>Generating ship...</p>
                        </div>
                    `;
                    
                    // Make API request to generate_ship.php
                    fetch('api/generate_ship.php')
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! Status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.status === 'success') {
                                const ship = data.ship;
                                
                                // Create HTML for ship display
                                let shipHtml = `
                                    <h2 id="ship-name">${ship.ship_name}</h2>
                                    <div class="ship-details">
                                        <p><strong>Vessel Class:</strong> ${ship.vessel_class}</p>
                                        <p><strong>Armament:</strong> ${ship.armament}</p>
                                        <p><strong>Crew:</strong> The crew are ${ship.crew_quantity} and are ${ship.crew_quality}.</p>
                                        
                                        <h3>Cargo:</h3>
                                        <ul id="cargo-list">
                                `;
                                
                                // Add cargo items
                                if (ship.cargo && ship.cargo.length > 0) {
                                    ship.cargo.forEach(item => {
                                        shipHtml += `<li>${item}</li>`;
                                    });
                                } else {
                                    shipHtml += `<li>None</li>`;
                                }
                                
                                shipHtml += `
                                        </ul>
                                        
                                        <h3>Plot Twist (Optional):</h3>
                                        <p>${ship.plot_twist}</p>
                                    </div>
                                `;
                                
                                // Update output display
                                outputDisplay.innerHTML = shipHtml;
                                
                                // Enable Discord send button
                                if (sendDiscordBtn) {
                                    sendDiscordBtn.style.opacity = '1';
                                    sendDiscordBtn.disabled = false;
                                    sendDiscordBtn.setAttribute('data-generator-type', 'ship');
                                }
                                
                                // Log to console
                                console.log("Generated ship:", ship);
                            } else {
                                // Handle error
                                outputDisplay.innerHTML = `
                                    <div class="error-message">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <p>Error generating ship: ${data.message}</p>
                                    </div>
                                `;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            
                            // Show error in output display
                            outputDisplay.innerHTML = `
                                <div class="error-message">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <p>Error generating ship: ${error.message}</p>
                                    <p>Check the console for more details.</p>
                                </div>
                            `;
                        });
                },
                
                // Loot Generator
                generateLoot: function() {
                    // Show loading state
                    outputDisplay.innerHTML = `
                        <div class="loading-indicator">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p>Generating loot...</p>
                        </div>
                    `;
                    
                    // Make API request to generate_loot.php
                    fetch('api/generate_loot.php')
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! Status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.status === 'success') {
                                // Start building loot HTML
                                let lootHtml = `<h2>Loot Results</h2>`;
                                
                                // Add primary loot
                                data.loot.forEach(item => {
                                    lootHtml += `
                                        <div class="loot-card">
                                            <div class="loot-roll">Roll: ${item.roll}</div>
                                            <div class="loot-name">${item.name}</div>
                                    `;
                                    
                                    // Add badges if applicable
                                    if (item.is_ancient_relic) {
                                        lootHtml += `<span class="ancient-relic-badge">Ancient Relic</span>`;
                                    }
                                    
                                    if (item.is_thing_of_importance) {
                                        lootHtml += `<span class="thing-of-importance-badge">Thing of Importance</span>`;
                                    }
                                    
                                    // Add description and category
                                    lootHtml += `
                                            <div class="loot-description">${item.description}</div>
                                            <div class="loot-category">Category: ${item.category}</div>
                                        </div>
                                    `;
                                });
                                
                                // Add extra rolls if any
                                if (data.extra_rolls && data.extra_rolls.length > 0) {
                                    lootHtml += `
                                        <div class="extra-roll-divider">
                                            <span>Additional Rolls</span>
                                        </div>
                                    `;
                                    
                                    data.extra_rolls.forEach(item => {
                                        lootHtml += `
                                            <div class="loot-card">
                                                <div class="loot-roll">Roll: ${item.roll}</div>
                                                <div class="loot-name">${item.name}</div>
                                        `;
                                        
                                        // Add badges if applicable
                                        if (item.is_ancient_relic) {
                                            lootHtml += `<span class="ancient-relic-badge">Ancient Relic</span>`;
                                        }
                                        
                                        if (item.is_thing_of_importance) {
                                            lootHtml += `<span class="thing-of-importance-badge">Thing of Importance</span>`;
                                        }
                                        
                                        // Add description and category
                                        lootHtml += `
                                                <div class="loot-description">${item.description}</div>
                                                <div class="loot-category">Category: ${item.category}</div>
                                            </div>
                                        `;
                                    });
                                }
                                
                                // Update output display
                                outputDisplay.innerHTML = lootHtml;
                                
                                // Enable Discord send button
                                if (sendDiscordBtn) {
                                    sendDiscordBtn.style.opacity = '1';
                                    sendDiscordBtn.disabled = false;
                                    sendDiscordBtn.setAttribute('data-generator-type', 'loot');
                                }
                                
                                // Log to console
                                console.log("Generated loot:", data);
                            } else {
                                // Handle error
                                outputDisplay.innerHTML = `
                                    <div class="error-message">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <p>Error generating loot: ${data.message}</p>
                                    </div>
                                `;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            
                            // Show error in output display
                            outputDisplay.innerHTML = `
                                <div class="error-message">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <p>Error generating loot: ${error.message}</p>
                                    <p>Check the console for more details.</p>
                                </div>
                            `;
                        });
                },
                
                // Placeholder for future generators
                diceRoller: function() {
                    alert("Dice roller coming soon!");
                },
                
                npcGenerator: function() {
                    alert("NPC generator coming soon!");
                },
                
                treasureGenerator: function() {
                    alert("Treasure generator coming soon!");
                }
            };
        });
        
        // Check for URL parameters to run generators on page load
        const urlParams = new URLSearchParams(window.location.search);
        const generator = urlParams.get('generator');
        
        if (generator) {
            // Wait a moment for the page to fully load
            setTimeout(() => {
                // Find the corresponding button to highlight
                const buttonToHighlight = document.getElementById(`${generator}-generator-btn`);
                if (buttonToHighlight) {
                    document.querySelectorAll('.generator-btn').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    buttonToHighlight.classList.add('active');
                }
                
                // Run the appropriate generator based on the URL parameter
                switch(generator) {
                    case 'ship':
                        window.Generators.generateShip();
                        break;
                    case 'loot':
                        window.Generators.generateLoot();
                        break;
                    case 'dice':
                        window.Generators.diceRoller();
                        break;
                    case 'npc':
                        window.Generators.npcGenerator();
                        break;
                    case 'treasure':
                        window.Generators.treasureGenerator();
                        break;
                }
                
                // Clear the URL parameter to prevent re-running on refresh
                window.history.replaceState({}, document.title, window.location.pathname);
            }, 500);
        }
    </script>
</body>
</html>