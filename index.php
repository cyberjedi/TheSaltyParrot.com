<?php
// Set the current page
$current_page = 'dashboard';

// Discord integration - safely load if available
$discord_enabled = false;
if (file_exists('discord/discord-config.php')) {
    // Try to include the Discord configuration
    try {
        require_once 'discord/discord-config.php';
        $discord_enabled = true;
        
        // Get user ID and webhooks if authenticated
        $user_webhooks = [];
        if (function_exists('is_discord_authenticated') && is_discord_authenticated() && 
            isset($conn)) {
            
            try {
                // Get user ID
                $discord_id = $_SESSION['discord_user']['id'];
                
                // Get user ID from database
                $stmt = $conn->prepare("SELECT id FROM discord_users WHERE discord_id = :discord_id");
                $stmt->bindParam(':discord_id', $discord_id);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && function_exists('get_user_webhooks')) {
                    $user_id = $user['id'];
                    $user_webhooks = get_user_webhooks($conn, $user_id);
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
    <title>The Salty Parrot - A Pirate Borg Toolbox</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <?php if ($discord_enabled && file_exists('css/discord.css')): ?>
    <link rel="stylesheet" href="css/discord.css">
    <?php endif; ?>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
</head>
<body>
    <div class="app-container">
        <!-- Include the sidebar -->
        <?php include 'components/sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <main class="main-content">
            <div class="dashboard-container">
                <!-- Discord Connection Box -->
                <div class="discord-box">
                    <h3 class="box-title">Discord Status</h3>
                    
                    <?php if ($discord_enabled && function_exists('renderDiscordConnectionStatus')): ?>
                        <?php renderDiscordConnectionStatus(); ?>
                    <?php else: ?>
                        <div id="discord-status" class="placeholder-display">
                            <i class="fab fa-discord"></i>
                            <p>Discord integration coming soon! Use the sidebar button to connect when available.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Rest of your page content -->
                <!-- Character Display Box -->
                <div class="character-box">
                    <h3 class="box-title">
                        Current Character
                        <div class="actions">
                            <button id="edit-character-btn" title="Edit Character"><i class="fas fa-edit"></i></button>
                            <button id="character-menu-btn" title="Character Menu"><i class="fas fa-ellipsis-v"></i></button>
                        </div>
                    </h3>
                    
                    <div id="character-display" class="placeholder-display">
                        <i class="fas fa-user-slash"></i>
                        <p>No active character selected</p>
                        <button class="btn btn-outline" id="create-character-btn">
                            <i class="fas fa-plus"></i> Create Character
                        </button>
                    </div>
                </div>
                
                <!-- Ship Display Box -->
                <div class="ship-box">
                    <h3 class="box-title">
                        Current Ship
                        <div class="actions">
                            <button id="edit-ship-btn" title="Edit Ship"><i class="fas fa-edit"></i></button>
                            <button id="ship-menu-btn" title="Ship Menu"><i class="fas fa-ellipsis-v"></i></button>
                        </div>
                    </h3>
                    
                    <div id="ship-display" class="placeholder-display">
                        <i class="fas fa-ship"></i>
                        <p>No active ship selected</p>
                        <button class="btn btn-outline" id="create-ship-btn">
                            <i class="fas fa-plus"></i> Create Ship
                        </button>
                    </div>
                </div>
                
                <!-- Output Box (Right Side) -->
                <div class="output-box">
                    <h3 class="box-title">
                        Output
                        <div class="actions">
                            <button id="save-output-btn" title="Save Output"><i class="fas fa-save"></i></button>
                            <button id="print-output-btn" title="Print Output"><i class="fas fa-print"></i></button>
                            <button id="clear-output-btn" title="Clear Output"><i class="fas fa-trash"></i></button>
                        </div>
                    </h3>
                    
                    <div id="output-display">
                        <div class="placeholder-display">
                            <i class="fas fa-dice"></i>
                            <p>Use the sidebar tools to generate content<br>Results will appear here</p>
                        </div>
                    </div>
                    
                    <?php if ($discord_enabled && function_exists('is_discord_authenticated') && is_discord_authenticated() && 
                             function_exists('render_webhook_selector') && !empty($user_webhooks)): ?>
                        <div id="webhook-selector-container" style="display: none;">
                            <?php render_webhook_selector($user_webhooks, ''); ?>
                        </div>
                    <?php endif; ?>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log("Dashboard loaded");
            
            const outputDisplay = document.getElementById('output-display');
            const clearOutputBtn = document.getElementById('clear-output-btn');
            const printOutputBtn = document.getElementById('print-output-btn');
            const saveOutputBtn = document.getElementById('save-output-btn');
            const createCharacterBtn = document.getElementById('create-character-btn');
            const createShipBtn = document.getElementById('create-ship-btn');
            const webhookSelectorContainer = document.getElementById('webhook-selector-container');
            
            // Clear output button
            if (clearOutputBtn) {
                clearOutputBtn.addEventListener('click', function() {
                    outputDisplay.innerHTML = `
                        <div class="placeholder-display">
                            <i class="fas fa-dice"></i>
                            <p>Use the sidebar tools to generate content<br>Results will appear here</p>
                        </div>
                    `;
                    
                    // Hide webhook selector if visible
                    if (webhookSelectorContainer) {
                        webhookSelectorContainer.style.display = 'none';
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
            
            // Create Character button
            if (createCharacterBtn) {
                createCharacterBtn.addEventListener('click', function() {
                    alert("Character creation is coming soon!");
                });
            }
            
            // Create Ship button
            if (createShipBtn) {
                createShipBtn.addEventListener('click', function() {
                    alert("Ship creation is coming soon!");
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
                                
                                // Show webhook selector if available
                                if (webhookSelectorContainer) {
                                    webhookSelectorContainer.style.display = 'block';
                                    
                                    // Update generator type for webhook send
                                    const sendButton = document.getElementById('send-to-discord-btn');
                                    if (sendButton) {
                                        sendButton.setAttribute('data-generator-type', 'ship');
                                    }
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
                                
                                // Show webhook selector if available
                                if (webhookSelectorContainer) {
                                    webhookSelectorContainer.style.display = 'block';
                                    
                                    // Update generator type for webhook send
                                    const sendButton = document.getElementById('send-to-discord-btn');
                                    if (sendButton) {
                                        sendButton.setAttribute('data-generator-type', 'loot');
                                    }
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
